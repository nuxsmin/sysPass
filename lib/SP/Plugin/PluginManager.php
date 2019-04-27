<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Exception;
use ReflectionClass;
use SP\Bootstrap;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Plugin\PluginModel;
use SP\Services\Install\Installer;
use SP\Services\Plugin\PluginDataService;
use SP\Services\Plugin\PluginService;
use SP\Util\VersionUtil;

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
     * @var PluginDataService
     */
    private $pluginDataService;

    /**
     * PluginManager constructor.
     *
     * @param PluginService     $pluginService
     * @param PluginDataService $pluginDataService
     * @param ContextInterface  $context
     * @param EventDispatcher   $eventDispatcher
     */
    public function __construct(PluginService $pluginService,
                                PluginDataService $pluginDataService,
                                ContextInterface $context,
                                EventDispatcher $eventDispatcher)
    {
        $this->pluginService = $pluginService;
        $this->pluginDataService = $pluginDataService;
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
     * @param bool   $initialize
     *
     * @return PluginInterface
     */
    public function getPlugin($name, bool $initialize = false)
    {
        if (isset(self::$pluginsAvailable[$name])) {
            $plugin = $this->loadPluginClass(
                $name,
                self::$pluginsAvailable[$name]['namespace']
            );

            if ($initialize) {
                $this->initPlugin($plugin);
                $plugin->onLoad();
            }

            return $plugin;
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
    private function loadPluginClass(string $name, string $namespace)
    {
        $pluginName = ucfirst($name);

        if (isset($this->loadedPlugins[$pluginName])) {
            return $this->loadedPlugins[$pluginName];
        }

        try {
            $class = $namespace . 'Plugin';
            $reflectionClass = new ReflectionClass($class);

            /** @var PluginInterface $plugin */
            $plugin = $reflectionClass->newInstance(
                Bootstrap::getContainer(),
                new PluginOperation($this->pluginDataService, $pluginName)
            );

            // Do not load plugin's data if not compatible.
            // Just return the plugin instance before disabling it
            if (self::checkCompatibility($plugin) === false) {
                $this->eventDispatcher->notifyEvent('plugin.load.error',
                    new Event($this, EventMessage::factory()
                        ->addDescription(sprintf(__('Plugin version not compatible (%s)'), implode('.', $plugin->getVersion()))))
                );

                $this->disabledPlugins[] = $pluginName;
            }

            return $plugin;
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception',
                new Event($e, EventMessage::factory()
                    ->addDescription(sprintf(__('Unable to load the "%s" plugin'), $pluginName))
                    ->addDescription($e->getMessage())
                    ->addDetail(__u('Plugin'), $pluginName))
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
                    ->addDetail(__u('Plugin disabled'), $plugin->getName()))
            );

            return false;
        }

        return true;
    }

    /**
     * @param PluginInterface $plugin
     *
     * @return bool
     */
    private function initPlugin(PluginInterface $plugin)
    {
        try {
            $pluginModel = $this->pluginService->getByName($plugin->getName());

            if ($pluginModel->getEnabled() !== 1) {
                $this->disabledPlugins[] = $plugin->getName();
            }

            return true;
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception',
                new Event($e, EventMessage::factory()
                    ->addDescription(sprintf(__('Unable to load the "%s" plugin'), $plugin->getName()))
                    ->addDescription($e->getMessage())
                    ->addDetail(__u('Plugin'), $plugin->getName()))
            );
        }

        return false;
    }

    /**
     * Loads the available and enabled plugins
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
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
                            ->addDetail(__u('Plugin available'), $plugin->getName()))
                    );

                    $this->load($plugin->getName());
                }
            } else {
                if ($plugin->getAvailable() === 1) {
                    $this->pluginService->toggleAvailable($plugin->getId(), false);

                    $this->eventDispatcher->notifyEvent('edit.plugin.unavailable',
                        new Event($this, EventMessage::factory()
                            ->addDetail(__u('Plugin unavailable'), $plugin->getName()))
                    );
                }
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

        if ($plugin !== null
            && $this->initPlugin($plugin)
        ) {
            logger(sprintf('Plugin loaded: %s', $pluginName));

            $this->eventDispatcher->notifyEvent('plugin.load',
                new Event($this, EventMessage::factory()
                    ->addDetail(__u('Plugin loaded'), $pluginName))
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
        $pluginData = new PluginModel();
        $pluginData->setName($name);
        $pluginData->setEnabled(false);

        $this->pluginService->create($pluginData);

        $this->eventDispatcher->notifyEvent('create.plugin',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('New Plugin'))
                ->addDetail(__u('Name'), $name)
            ));

        $this->disabledPlugins[] = $name;
    }

    /**
     * @param string $version
     */
    public function upgradePlugins(string $version)
    {
        $available = array_keys(self::$pluginsAvailable);

        foreach ($available as $pluginName) {
            $plugin = $this->loadPluginClass(
                $pluginName,
                self::$pluginsAvailable[$pluginName]['namespace']
            );

            try {
                $pluginModel = $this->pluginService->getByName($pluginName);

                if ($pluginModel->getVersionLevel() === null
                    || VersionUtil::checkVersion($pluginModel->getVersionLevel(), $version)
                ) {
                    $this->eventDispatcher->notifyEvent('upgrade.plugin.process',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Upgrading plugin'))
                            ->addDetail(__u('Name'), $pluginName))
                    );

                    $plugin->upgrade(
                        $version,
                        new PluginOperation($this->pluginDataService, $pluginName),
                        $pluginModel
                    );

                    $pluginModel->setData(null);
                    $pluginModel->setVersionLevel($version);

                    $this->pluginService->update($pluginModel);

                    $this->eventDispatcher->notifyEvent('upgrade.plugin.process',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Plugin upgraded'))
                            ->addDetail(__u('Name'), $pluginName))
                    );
                }
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception',
                    new Event($e, EventMessage::factory()
                        ->addDescription(sprintf(__('Unable to upgrade the "%s" plugin'), $pluginName))
                        ->addDescription($e->getMessage())
                        ->addDetail(__u('Plugin'), $pluginName))
                );
            }
        }
    }

    /**
     * Comprobar disponibilidad de plugins habilitados
     *
     * @throws SPException
     */
    public function checkEnabledPlugins()
    {
        foreach ($this->getEnabledPlugins() as $plugin) {
            if (!in_array($plugin, $this->loadedPlugins)) {
                $this->pluginService->toggleAvailableByName($plugin, false);

                $this->eventDispatcher->notifyEvent('edit.plugin.unavailable',
                    new Event($this, EventMessage::factory()
                        ->addDetail(__u('Plugin disabled'), $plugin->getName()))
                );
            }
        }
    }

    /**
     * Devolver los plugins habilitados
     *
     * @return array
     * @throws ConstraintException
     * @throws QueryException
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