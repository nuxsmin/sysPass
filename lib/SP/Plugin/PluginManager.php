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
use SP\Bootstrap;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\PluginData;
use SP\Repositories\NoSuchItemException;
use SP\Services\Install\Installer;
use SP\Services\Plugin\PluginService;

/**
 * Class PluginUtil
 *
 * @package SP\Plugin
 */
final class PluginManager
{
    /**
     * @var array
     */
    private static $pluginsAvailable;
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

        self::$pluginsAvailable = self::getPlugins();
    }

    /**
     * Devuelve la lista de Plugins disponibles em el directorio
     *
     * @return array
     */
    public static function getPlugins()
    {
        $dir = dir(PLUGINS_PATH);
        $plugins = [];

        if ($dir) {
            while (false !== ($entry = $dir->read())) {
                $pluginDir = PLUGINS_PATH . DIRECTORY_SEPARATOR . $entry;
                $pluginFile = $pluginDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Plugin.php';

                if (strpos($entry, '.') === false
                    && is_dir($pluginDir)
                    && file_exists($pluginFile)
                ) {
                    logger(sprintf('Plugin found: %s', $pluginDir));

                    $plugins[$entry] = require $pluginDir . DIRECTORY_SEPARATOR . 'base.php';
                }
            }

            $dir->close();
        }

        return $plugins;
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
        if (isset(self::$pluginsAvailable[$name])) {
            return $this->loadPluginClass(
                $name,
                self::$pluginsAvailable[$name]['namespace']
            );
        }

        return null;
    }

    /**
     * Cargar un plugin
     *
     * @param string $name Nombre del plugin
     * @param string $namespace
     *
     * @return PluginInterface
     */
    public function loadPluginClass(string $name, string $namespace)
    {
        $name = ucfirst($name);

        if (isset($this->loadedPlugins[$name])) {
            return $this->loadedPlugins[$name];
        }

        try {
            $class = $namespace . 'Plugin';
            $reflectionClass = new ReflectionClass($class);

            /** @var PluginInterface $plugin */
            $plugin = $reflectionClass->newInstance(Bootstrap::getContainer());

            // Do not load plugin's data if not compatible.
            // Just return the plugin instance before disabling it
            if (self::checkCompatibility($plugin) === false) {
                $this->eventDispatcher->notifyEvent('plugin.load.error',
                    new Event($this, EventMessage::factory()
                        ->addDescription(sprintf(__('Versión de plugin no compatible (%s)'), implode('.', $plugin->getVersion()))))
                );

                $this->disabledPlugins[] = $name;

                return $plugin;
            }

            $pluginData = $this->pluginService->getByName($name);

            if ($pluginData->getEnabled() === 1) {
                $plugin->onLoadData($pluginData);
            } else {
                $this->disabledPlugins[] = $name;
            }

            return $plugin;
        } catch (\Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception',
                new Event($e, EventMessage::factory()
                    ->addDescription(sprintf(__('No es posible cargar el plugin "%s"'), $name))
                    ->addDescription($e->getMessage())
                    ->addDetail(__u('Plugin'), $name))
            );
        }

        return null;
    }

    /**
     * @param PluginInterface $plugin
     *
     * @return bool
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function checkCompatibility(PluginInterface $plugin)
    {
        $pluginVersion = implode('.', $plugin->getCompatibleVersion());
        $appVersion = implode('.', array_slice(Installer::VERSION, 0, 2));

        if (version_compare($pluginVersion, $appVersion) === -1) {
            $this->pluginService->toggleEnabledByName($plugin->getName(), false);

            $this->eventDispatcher->notifyEvent('edit.plugin.disable',
                new Event($this, EventMessage::factory()
                    ->addDetail(__u('Plugin deshabilitado'), $plugin->getName()))
            );

            return false;
        }

        return true;
    }

    /**
     * Loads the available and enabled plugins
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function loadPlugins()
    {
        $available = array_keys(self::$pluginsAvailable);
        $processed = [];

        // Process registered plugins in the database
        foreach ($this->pluginService->getAll() as $plugin) {
            $in = in_array($plugin->getName(), $available, true);

            if ($in === true) {
                if ($plugin->getEnabled() === 1) {
                    $this->load($plugin->getName());
                }

                if ($plugin->getAvailable() === 0) {
                    $this->pluginService->toggleAvailable($plugin->getId(), true);

                    $this->eventDispatcher->notifyEvent('edit.plugin.available',
                        new Event($this, EventMessage::factory()
                            ->addDetail(__u('Plugin disponible'), $plugin->getName()))
                    );

                    $this->load($plugin->getName());
                }
            } else {
                $this->pluginService->toggleAvailable($plugin->getId(), false);

                $this->eventDispatcher->notifyEvent('edit.plugin.unavailable',
                    new Event($this, EventMessage::factory()
                        ->addDetail(__u('Plugin no disponible'), $plugin->getName()))
                );
            }

            $processed[] = $plugin->getName();
        }

        // Search for available plugins and not registered in the database
        foreach (array_diff($available, $processed) as $plugin) {
            $this->registerPlugin($plugin);

            $this->load($plugin);
        }
    }

    /**
     * @param string $pluginName
     */
    private function load(string $pluginName)
    {
        $plugin = $this->loadPluginClass(
            $pluginName,
            self::$pluginsAvailable[$pluginName]['namespace']
        );

        if ($plugin !== null) {
            logger(sprintf('Plugin loaded: %s', $pluginName));

            $this->eventDispatcher->notifyEvent('plugin.load',
                new Event($this, EventMessage::factory()
                    ->addDetail(__u('Plugin cargado'), $pluginName))
            );

            $this->loadedPlugins[$pluginName] = $plugin;

            $this->eventDispatcher->attach($plugin);
        }
    }

    /**
     * @param string $name
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function registerPlugin(string $name)
    {
        $pluginData = new PluginData();
        $pluginData->setName($name);
        $pluginData->setEnabled(false);

        $this->pluginService->create($pluginData);

        $this->eventDispatcher->notifyEvent('create.plugin',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Nuevo Plugin'))
                ->addDetail(__u('Nombre'), $name)
            ));

        $this->disabledPlugins[] = $name;
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

                $this->eventDispatcher->notifyEvent('edit.plugin.unavailable',
                    new Event($this, EventMessage::factory()
                        ->addDetail(__u('Plugin deshabilitado'), $plugin->getName()))
                );
            }
        }
    }

    /**
     * Devolver los plugins habilitados
     *
     * @return array
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