<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Plugin;

use ReflectionClass;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\DataModel\PluginData;
use SP\Repositories\NoSuchItemException;
use SP\Services\Plugin\PluginService;

/**
 * Class PluginUtil
 *
 * @package SP\Plugin
 */
class PluginManager
{
    /**
     * @var array Plugins habilitados
     */
    private $enabledPlugins;
    /**
     * @var PluginInterface[] Plugins ya cargados
     */
    private $loadedPlugins = [];
    /**
     * @var array Plugins deshabilitados
     */
    private $disabledPlugins = [];
    /**
     * @var PluginService
     */
    private $pluginService;
    /**
     * @var ContextInterface
     */
    private $context;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * PluginManager constructor.
     *
     * @param PluginService    $pluginService
     * @param ContextInterface $context
     * @param EventDispatcher  $eventDispatcher
     */
    public function __construct(PluginService $pluginService, ContextInterface $context, EventDispatcher $eventDispatcher)
    {
        $this->pluginService = $pluginService;
        $this->context = $context;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Obtener la información de un plugin
     *
     * @param string $name Nombre del plugin
     *
     * @return PluginInterface
     */
    public function getPluginInfo($name)
    {
        $name = ucfirst($name);

        $pluginClass = 'Plugins\\' . $name . '\\' . $name . 'Plugin';

        if (isset($this->loadedPlugins[$name])) {
            return $this->loadedPlugins[$name];
        }

        try {
            $reflectionClass = new \ReflectionClass($pluginClass);

            /** @var PluginBase $plugin */
            $plugin = $reflectionClass->newInstance();

            $this->loadedPlugins[$name] = $plugin;

            return $plugin;
        } catch (\ReflectionException $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception',
                new Event($e, EventMessage::factory()
                    ->addDescription(sprintf(__('No es posible cargar el plugin "%s"'), $name)))
            );
        }

        return null;
    }

    /**
     * Comprobar disponibilidad de plugins habilitados
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkEnabledPlugins()
    {
        foreach ($this->getEnabledPlugins() as $plugin) {
            if (!in_array($plugin, $this->loadedPlugins)) {
                $this->pluginService->toggleAvailableByName($plugin, false);
            }
        }
    }

    /**
     * Devolver los plugins habilitados
     *
     * @return PluginInterface[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getEnabledPlugins()
    {
        if ($this->enabledPlugins !== null) {
            return $this->enabledPlugins;
        }

        $this->enabledPlugins = [];

        foreach ($this->pluginService->getEnabled() as $plugin) {
            $this->enabledPlugins[] = $plugin->getName();
        }

        return $this->enabledPlugins;
    }

    /**
     * Cargar los Plugins disponibles
     */
    public function loadPlugins()
    {
        $classLoader = new \SplClassLoader('Plugins', PLUGINS_PATH);
        $classLoader->register();

        foreach ($this->getPlugins() as $name) {
            if (($plugin = $this->loadPlugin($name)) !== null) {
                debugLog('Plugin loaded: ' . $name);

                $this->eventDispatcher->attach($plugin);
            }
        }
    }

    /**
     * Devuelve la lista de Plugins disponibles em el directorio
     *
     * @return array
     */
    public function getPlugins()
    {
        $pluginDirH = opendir(PLUGINS_PATH);
        $plugins = [];

        if ($pluginDirH) {
            while (false !== ($entry = readdir($pluginDirH))) {
                $pluginDir = PLUGINS_PATH . DIRECTORY_SEPARATOR . $entry;

                if (strpos($entry, '.') === false
                    && is_dir($pluginDir)
                    && file_exists($pluginDir . DIRECTORY_SEPARATOR . $entry . 'Plugin.php')
                ) {
                    $plugins[] = $entry;
                }
            }

            closedir($pluginDirH);
        }

        return $plugins;
    }

    /**
     * Cargar un plugin
     *
     * @param string $name Nombre del plugin
     *
     * @return PluginInterface
     */
    public function loadPlugin($name)
    {
        $name = ucfirst($name);

        if (isset($this->loadedPlugins[$name])) {
            return $this->loadedPlugins[$name];
        }

        try {
            $pluginClass = 'Plugins\\' . $name . '\\' . $name . 'Plugin';

            $reflectionClass = new ReflectionClass($pluginClass);

            /** @var PluginInterface $plugin */
            $plugin = $reflectionClass->newInstance();

            try {
                $pluginData = $this->pluginService->getByName($plugin);

                if ($pluginData->getEnabled() === 1) {
                    if (!empty($pluginData->getData())) {
                        $pluginData->setData(unserialize($pluginData->getData()));
                    }

                    return $plugin;
                } else {
                    $this->disabledPlugins[] = $name;
                }
            } catch (NoSuchItemException $noSuchItemException) {
                $pluginData = new PluginData();
                $pluginData->setName($name);
                $pluginData->setEnabled(0);

                $this->pluginService->create($pluginData);

                $this->eventDispatcher->notifyEvent('create.plugin',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Nuevo Plugin'))
                        ->addDetail(__u('Nombre'), $name)
                    ));

                $this->disabledPlugins[] = $name;
            }
        } catch (\Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception',
                new Event($e, EventMessage::factory()
                    ->addDescription(sprintf(__('No es posible cargar el plugin "%s"'), $name)))
            );
        }

        return null;
    }

    /**
     * Devolver los plugins cargados
     *
     * @return PluginInterface[]
     */
    public function getLoadedPlugins()
    {
        return $this->loadedPlugins;
    }

    /**
     * Devolver los plugins deshabilidatos
     *
     * @return string[]
     */
    public function getDisabledPlugins()
    {
        return $this->disabledPlugins;
    }
}