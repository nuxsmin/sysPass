<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Config\Services;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use SP\Domain\Common\Providers\Password;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Exceptions\ConfigException;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Domain\Storage\Ports\XmlFileStorageService;
use SP\Infrastructure\File\FileException;

use function SP\logger;
use function SP\processException;

/**
 * Class ConfigFile
 */
class ConfigFile implements ConfigFileService
{
    /**
     * @throws ConfigException
     */
    public function __construct(
        private readonly XmlFileStorageService $fileStorage,
        private readonly FileCacheService      $fileCache,
        private readonly Context $context,
        private ?ConfigDataInterface           $configData = null
    ) {
        $this->configData = $configData ?? $this->initialize();
    }

    /**
     * @throws ConfigException
     */
    private function initialize(): ConfigDataInterface
    {
        try {
            $configData = $this->loadFromCache() ?? $this->loadFromFile() ?? $this->generateNewConfig();

            logger('Config loaded');

            return $configData;
        } catch (Exception $e) {
            processException($e);

            throw ConfigException::critical($e->getMessage(), null, $e->getCode(), $e);
        }
    }

    /**
     * @throws FileException
     */
    private function loadFromCache(): ConfigDataInterface|null
    {
        if (!$this->fileCache->exists() || $this->isCacheExpired()) {
            return null;
        }

        $configData = $this->fileCache->loadWith(ConfigData::class);

        if ($configData->countAttributes() === 0) {
            $this->fileCache->delete();

            return null;
        }

        logger('Config cache loaded');

        return $configData;
    }

    private function isCacheExpired(): bool
    {
        try {
            return $this->fileCache->isExpiredDate($this->fileStorage->getFileTime());
        } catch (FileException) {
            return true;
        }
    }

    private function loadFromFile(): ?ConfigDataInterface
    {
        try {
            $configData = $this->configMapper($this->fileStorage->load('config'));
            $this->fileCache->save($configData);

            return $configData;
        } catch (ReflectionException|FileException $e) {
            processException($e);
        }

        return null;
    }

    /**
     * Map the config array keys with ConfigData class setters
     * @throws ReflectionException
     */
    private function configMapper(array $items): ConfigDataInterface
    {
        $configData = new ConfigData();

        $reflectionObject = new ReflectionObject($configData);

        $methods = array_filter(
            $reflectionObject->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn(ReflectionMethod $method) => str_starts_with($method->getName(), 'set')
        );

        foreach ($methods as $method) {
            $propertyName = lcfirst(substr_replace($method->getName(), '', 0, 3));

            if (array_key_exists($propertyName, $items)) {
                foreach ($method->getParameters() as $parameter) {
                    $type = $parameter->getType();

                    if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                        $value = match ($type->getName()) {
                            'int' => (int)$items[$propertyName],
                            'bool' => (bool)$items[$propertyName],
                            'array' => (array)$items[$propertyName],
                            default => (string)$items[$propertyName]
                        };
                        $method->invoke($configData, $value);
                    }
                }
            }
        }

        return $configData;
    }

    /**
     * Guardar la configuración
     *
     * @param ConfigDataInterface $configData
     * @param bool|null $commit
     * @return ConfigFileService
     * @throws FileException
     */
    public function save(
        ConfigDataInterface $configData,
        ?bool               $commit = true
    ): ConfigFileService {
        $configSaver = $this->context->getUserData()->getLogin() ?: AppInfoInterface::APP_NAME;

        $configData->setConfigDate(time());
        $configData->setConfigSaver($configSaver);
        $configData->setConfigHash();

        if ($commit) {
            // Save only attributes to avoid a parent attributes node within the XML
            $this->fileStorage->save($configData->getAttributes(), 'config');
            $this->fileCache->save($configData);
        }

        $this->configData = $configData;

        return $this;
    }

    /**
     * @return ConfigData
     * @throws EnvironmentIsBrokenException
     * @throws FileException
     */
    private function generateNewConfig(): ConfigData
    {
        $configData = new ConfigData();

        // Generate a random salt that is used to add more seed to some passwords
        $configData->setPasswordSalt(Password::generateRandomBytes());

        $this->save($configData);

        logger('Config file created', 'INFO');

        return $configData;
    }

    /**
     * Cargar la configuración desde el contexto
     *
     * @throws ConfigException
     */
    public function reload(): ConfigDataInterface
    {
        $this->initialize();

        return clone $this->configData;
    }

    /**
     * Returns a clone of the configuration data
     *
     * @return ConfigDataInterface
     */
    public function getConfigData(): ConfigDataInterface
    {
        return clone $this->configData;
    }

    /**
     * @throws FileException
     * @throws EnvironmentIsBrokenException
     */
    public function generateUpgradeKey(): ConfigFileService
    {
        if (empty($this->configData->getUpgradeKey())) {
            logger('Generating upgrade key');

            return $this->save($this->configData->setUpgradeKey(Password::generateRandomBytes(16)));
        }

        return $this;
    }
}
