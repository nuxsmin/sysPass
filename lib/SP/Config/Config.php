<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

use ReflectionObject;
use SP\Core\Exceptions\ConfigException;
use SP\Core\Session\Session;
use SP\Core\Traits\InjectableTrait;
use SP\Storage\XmlFileStorageInterface;

defined('APP_ROOT') || die();

/**
 * Esta clase es responsable de leer y escribir la configuración del archivo config.php
 */
class Config
{
    use InjectableTrait;

    /**
     * @var bool
     */
    private static $configLoaded = false;
    /**
     * @var ConfigData
     */
    private $configData;
    /**
     * @var XmlFileStorageInterface
     */
    private $fileStorage;
    /**
     * @var Session
     */
    private $session;

    /**
     * Config constructor.
     *
     * @param XmlFileStorageInterface $fileStorage
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct(XmlFileStorageInterface $fileStorage)
    {
        $this->injectDependencies();

        $this->fileStorage = $fileStorage;

        if (!self::$configLoaded) {
            $this->configData = new ConfigData();

            $this->loadConfigFile();

            self::$configLoaded = true;
        }
    }

    /**
     * Cargar el archivo de configuración
     *
     * @return ConfigData
     * @throws \SP\Core\Exceptions\ConfigException
     */
    public function loadConfigFile()
    {
        ConfigUtil::checkConfigDir();

        try {
            // Mapear el array de elementos de configuración con las propieades de la clase configData
            $items = $this->fileStorage->load('config')->getItems();
            $Reflection = new ReflectionObject($this->configData);

            foreach ($Reflection->getProperties() as $property) {
                $property->setAccessible(true);

                if (isset($items[$property->getName()])) {
                    $property->setValue($this->configData, $items[$property->getName()]);
                }

                $property->setAccessible(false);
            }
        } catch (\Exception $e) {
            debugLog($e->getMessage());

            throw new ConfigException(ConfigException::CRITICAL, $e->getMessage(), '', $e->getCode(), $e);
        }

        return $this->configData;
    }

    /**
     * Obtener la configuración o devolver una nueva
     *
     * @return void
     * @deprecated
     */
    public static function getConfig()
    {
    }

    /**
     * Cargar la configuración desde el archivo
     *
     * @param bool $reload
     * @return ConfigData
     */
    public function loadConfig($reload = false)
    {
        $configData = $this->session->getConfig();

        if ($reload === true
            || $configData === null
            || time() >= ($this->session->getConfigTime() + $configData->getSessionTimeout() / 2)
        ) {
            $this->saveConfigInSession();
        }

        return $this->configData;
    }

    /**
     * Guardar la configuración en la sesión
     */
    private function saveConfigInSession()
    {
        $this->session->setConfig($this->configData);
        $this->session->setConfigTime(time());
    }

    /**
     * Guardar la configuración
     *
     * @param ConfigData $Config
     * @param bool       $backup
     */
    public function saveConfig(ConfigData $Config = null, $backup = true)
    {
        $ConfigData = null === $Config ? $this->configData : $Config;
        $ConfigData->setConfigDate(time());
        $ConfigData->setConfigSaver($this->session->getUserData()->getLogin());
        $ConfigData->setConfigHash();

        $this->fileStorage->setItems($ConfigData);
        $this->fileStorage->save('config');

        if ($backup) {
            $this->backupToDB();
        }
    }

    /**
     * Realizar un backup de la configuración en la BD
     */
    private function backupToDB()
    {
        ConfigDB::setValue('config_backup', json_encode($this->configData), true, true);
        ConfigDB::setValue('config_backupdate', time());
    }

    /**
     * @return ConfigData
     */
    public function getConfigData()
    {
        return $this->configData;
    }

    /**
     * @param Session $session
     */
    public function inject(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Restaurar la configuración desde la BD
     *
     * @return array
     */
    private function restoreBackupFromDB()
    {
        $configBackup = ConfigDB::getValue('config_backup');

        return json_decode($configBackup);
    }
}
