<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Config;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use SP\Core\AppInfoInterface;
use SP\Core\Context\ContextInterface;
use SP\Core\Exceptions\ConfigException;
use SP\Core\Exceptions\SPException;
use SP\Services\Config\ConfigBackupService;
use SP\Storage\File\FileCacheInterface;
use SP\Storage\File\FileException;
use SP\Storage\File\XmlFileStorageInterface;
use SP\Util\PasswordUtil;

defined('APP_ROOT') || die();

/**
 * Esta clase es responsable de leer y escribir la configuración del archivo config.php
 */
final class Config
{
    /**
     * Cache file name
     */
    public const CONFIG_CACHE_FILE = CACHE_PATH.DIRECTORY_SEPARATOR.'config.cache';
    private static int              $timeUpdated;
    private ContextInterface        $context;
    private bool                    $configLoaded = false;
    private ?ConfigDataInterface    $configData   = null;
    private XmlFileStorageInterface $fileStorage;
    private FileCacheInterface      $fileCache;
    private ConfigBackupService     $configBackupService;

    /**
     * @throws ConfigException
     */
    public function __construct(
        XmlFileStorageInterface $fileStorage,
        FileCacheInterface $fileCache,
        ContextInterface $context,
        ConfigBackupService $configBackupService
    ) {
        $this->fileCache = $fileCache;
        $this->fileStorage = $fileStorage;
        $this->context = $context;
        $this->configBackupService = $configBackupService;

        $this->initialize();
    }

    /**
     * @throws ConfigException
     */
    private function initialize(): void
    {
        if (!$this->configLoaded) {
            try {
                if ($this->fileCache->exists()
                    && !$this->isCacheExpired()
                ) {
                    $this->configData = $this->fileCache->load();

                    if ($this->configData->count() === 0) {
                        $this->fileCache->delete();
                        $this->initialize();

                        return;
                    }

                    logger('Config cache loaded');
                } else {
                    if (file_exists($this->fileStorage->getFileHandler()->getFile())) {
                        $this->configData = $this->loadConfigFromFile();
                        $this->fileCache->save($this->configData);
                    } else {
                        $configData = new ConfigData();

                        // Generate a random salt that is used to add more seed to some passwords
                        $configData->setPasswordSalt(PasswordUtil::generateRandomBytes(30));

                        $this->saveConfig($configData, false);

                        logger('Config file created', 'INFO');
                    }

                    logger('Config loaded');
                }

                self::$timeUpdated = $this->configData->getConfigDate();

                $this->configLoaded = true;
            } catch (Exception $e) {
                processException($e);

                throw new ConfigException(
                    $e->getMessage(),
                    SPException::CRITICAL,
                    null,
                    $e->getCode(),
                    $e
                );
            }
        }
    }

    private function isCacheExpired(): bool
    {
        try {
            return $this->fileCache->isExpiredDate($this->fileStorage->getFileHandler()->getFileTime());
        } catch (FileException $e) {
            return true;
        }
    }

    /**
     * Cargar el archivo de configuración
     *
     * @throws FileException
     */
    public function loadConfigFromFile(): ConfigDataInterface
    {
        return $this->configMapper($this->fileStorage->load('config')->getItems());
    }

    /**
     * Map the config array keys with ConfigData class setters
     */
    private function configMapper(array $items): ConfigDataInterface
    {
        $configData = new ConfigData();

        foreach ($items as $item => $value) {
            $methodName = 'set'.ucfirst($item);

            if (method_exists($configData, $methodName)) {
                $configData->$methodName($value);
            }

        }

        return $configData;
    }

    /**
     * Guardar la configuración
     *
     * @param  \SP\Config\ConfigDataInterface  $configData
     * @param  bool|null  $backup
     *
     * @return \SP\Config\Config
     * @throws \SP\Storage\File\FileException
     */
    public function saveConfig(
        ConfigDataInterface $configData,
        ?bool $backup = true
    ): Config {
        if ($backup) {
            $this->configBackupService->backup($configData);
        }

        $configSaver = $this->context->getUserData()->getLogin()
            ?: AppInfoInterface::APP_NAME;

        $configData->setConfigDate(time());
        $configData->setConfigSaver($configSaver);
        $configData->setConfigHash();

        // Save only attributes to avoid a parent attributes node within the XML
        $this->fileStorage->save($configData->getAttributes(), 'config');
        // Save the class object (serialized)
        $this->fileCache->save($configData);

        $this->configData = $configData;

        return $this;
    }

    public static function getTimeUpdated(): int
    {
        return self::$timeUpdated;
    }

    /**
     * Commits a config data
     */
    public function updateConfig(ConfigDataInterface $configData): Config
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
     */
    public function loadConfig(?bool $reload = false): ConfigDataInterface
    {
        try {
            $configData = $this->fileCache->load();

            if ($reload === true
                || $configData === null
                || $this->isCacheExpired()
            ) {
                $this->configData = $this->loadConfigFromFile();
                $this->fileCache->save($this->configData);

                return $this->configData;
            }

            return $configData;
        } catch (FileException $e) {
            processException($e);
        }

        return $this->configData;
    }

    /**
     * Returns a clone of the configuration data
     *
     * @return \SP\Config\ConfigDataInterface
     */
    public function getConfigData(): ConfigDataInterface
    {
        return clone $this->configData;
    }

    /**
     * @throws FileException
     * @throws EnvironmentIsBrokenException
     */
    public function generateUpgradeKey(): Config
    {
        if (empty($this->configData->getUpgradeKey())) {
            logger('Generating upgrade key');

            return $this->saveConfig($this->configData->setUpgradeKey(PasswordUtil::generateRandomBytes(16)), false);
        }

        return $this;
    }
}
