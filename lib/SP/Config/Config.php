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

namespace SP\Config;

use DI\Container;
use ReflectionObject;
use SP\Core\Context\ContextInterface;
use SP\Core\Exceptions\ConfigException;
use SP\Services\Config\ConfigBackupService;
use SP\Storage\File\FileException;
use SP\Storage\File\XmlFileStorageInterface;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Esta clase es responsable de leer y escribir la configuración del archivo config.php
 */
final class Config
{
    /**
     * @var int
     */
    private static $timeUpdated;
    /**
     * @var bool
     */
    private $configLoaded = false;
    /**
     * @var ConfigData
     */
    private $configData;
    /**
     * @var \SP\Storage\File\XmlFileStorageInterface
     */
    private $fileStorage;
    /**
     * @var ContextInterface
     */
    private $context;
    /**
     * @var Container
     */
    private $dic;

    /**
     * Config constructor.
     *
     * @param \SP\Storage\File\XmlFileStorageInterface $fileStorage
     * @param ContextInterface                         $session
     * @param Container                                $dic
     *
     * @throws ConfigException
     */
    public function __construct(XmlFileStorageInterface $fileStorage, ContextInterface $session, Container $dic)
    {
        $this->context = $session;
        $this->fileStorage = $fileStorage;
        $this->dic = $dic;

        $this->initialize();
    }

    /**
     * @throws ConfigException
     */
    private function initialize()
    {
        if (!$this->configLoaded) {
            try {

                $this->configData = $this->loadConfigFromFile();
            } catch (FileException $e) {
                processException($e);

                $this->configData = new ConfigData();

                $this->saveConfig($this->configData, false);
            } catch (\Exception $e) {
                processException($e);

                throw new ConfigException($e->getMessage(), ConfigException::CRITICAL, null, $e->getCode(), $e);
            }

            self::$timeUpdated = $this->configData->getConfigDate();
            $this->configLoaded = true;

            logger('Config loaded');
        }
    }

    /**
     * Cargar el archivo de configuración
     *
     * @return ConfigData
     * @throws ConfigException
     * @throws FileException
     */
    public function loadConfigFromFile()
    {
        ConfigUtil::checkConfigDir();

        $configData = new ConfigData();

        // Mapear el array de elementos de configuración con las propiedades de la clase configData
        $items = $this->fileStorage->load('config')->getItems();
        $reflectionObject = new ReflectionObject($configData);

        foreach ($reflectionObject->getProperties() as $property) {
            $property->setAccessible(true);

            if (isset($items[$property->getName()])) {
                $property->setValue($configData, $items[$property->getName()]);
            }

            $property->setAccessible(false);
        }

        return $configData;
    }

    /**
     * Guardar la configuración
     *
     * @param ConfigData $configData
     * @param bool       $backup
     *
     * @return Config
     */
    public function saveConfig(ConfigData $configData, $backup = true)
    {
        try {
            if ($backup) {
                $this->dic->get(ConfigBackupService::class)
                    ->backup($configData);
            }

            $configData->setConfigDate(time());
            $configData->setConfigSaver($this->context->getUserData()->getLogin());
            $configData->setConfigHash();

            $this->fileStorage->save($configData, 'config');

            $this->configData = $configData;
        } catch (\Exception $e) {
            processException($e);
        }

        return $this;
    }

    /**
     * @return int
     */
    public static function getTimeUpdated()
    {
        return self::$timeUpdated;
    }

    /**
     * Commits a config data
     *
     * @param ConfigData $configData
     *
     * @return Config
     */
    public function updateConfig(ConfigData $configData)
    {
        $configData->setConfigDate(time());
        $configData->setConfigSaver($this->context->getUserData()->getLogin());
        $configData->setConfigHash();

        $this->configData = $configData;

        self::$timeUpdated = $configData->getConfigDate();

        return $this;
    }

    /**
     * Cargar la configuración desde el contexto
     *
     * @param ContextInterface $context
     * @param bool             $reload
     *
     * @return ConfigData
     */
    public function loadConfig(ContextInterface $context, $reload = false)
    {
        $configData = $context->getConfig();

        if ($reload === true
            || $configData === null
            || time() >= ($context->getConfigTime() + $configData->getSessionTimeout() / 2)
        ) {
            return $this->saveConfigInSession($context);
        }

        return $configData;
    }

    /**
     * Guardar la configuración en la sesión
     *
     * @param ContextInterface $context
     *
     * @return ConfigData
     */
    private function saveConfigInSession(ContextInterface $context)
    {
        $context->setConfig($this->configData);
        $context->setConfigTime(time());

        return $this->configData;
    }

    /**
     * @return ConfigData
     */
    public function getConfigData()
    {
        return clone $this->configData;
    }

    /**
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function generateUpgradeKey()
    {
        if (empty($this->configData->getUpgradeKey())) {
            logger('Generating upgrade key');

            return $this->saveConfig($this->configData->setUpgradeKey(Util::generateRandomBytes(16)), false);
        }

        return $this;
    }
}
