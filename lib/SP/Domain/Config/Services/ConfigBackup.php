<?php
/*
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

use Exception;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigBackupService;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\File\FileException;

use function SP\__u;
use function SP\processException;

/**
 * Class ConfigBackup
 */
readonly class ConfigBackup implements ConfigBackupService
{

    public function __construct(private ConfigService $configService)
    {
    }

    /**
     * @throws SPException
     */
    public static function configToJson(string $configData): string
    {
        return Serde::serializeJson(Serde::deserialize($configData, ConfigData::class), JSON_PRETTY_PRINT);
    }

    /**
     * Backs up the config data into the database
     */
    public function backup(ConfigDataInterface $configData): void
    {
        try {
            $this->configService->save('config_backup', $this->packConfigData($configData));
            $this->configService->save('config_backup_date', time());
        } catch (Exception $e) {
            processException($e);
        }
    }

    private function packConfigData(ConfigDataInterface $configData): string
    {
        return bin2hex(gzcompress(Serde::serialize($configData)));
    }

    /**
     * @throws FileException
     * @throws ServiceException
     * @throws SPException
     */
    public function restore(ConfigFileService $configFile): ConfigDataInterface
    {
        return $configFile->save(Serde::deserialize($this->getBackup(), ConfigData::class))->getConfigData();
    }

    /**
     * @throws ServiceException
     */
    public function getBackup(): string
    {
        try {
            $data = $this->configService->getByParam('config_backup');

            if ($data === null) {
                throw new ServiceException(__u('Unable to restore the configuration'));
            }

            return gzuncompress(hex2bin($data));
        } catch (NoSuchItemException $e) {
            processException($e);

            throw new ServiceException(__u('Unable to restore the configuration'));
        }
    }
}
