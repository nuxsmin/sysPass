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

namespace SP\Services\Config;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use RuntimeException;
use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\Http\Json;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\File\FileException;
use SP\Util\Util;

/**
 * Class ConfigBackupService
 *
 * @package SP\Services\Config
 */
final class ConfigBackupService extends Service
{
    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * @param string $configData
     *
     * @return string
     * @throws SPException
     */
    public static function configToJson(string $configData): string
    {
        return Json::getJson(Util::unserialize(ConfigData::class, $configData), JSON_PRETTY_PRINT);
    }

    /**
     * @param string $configData
     */
    public static function configToXml(string $configData)
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Backs up the config data into the database
     *
     * @param ConfigData $configData
     */
    public function backup(ConfigData $configData)
    {
        try {
            $this->configService->save('config_backup', $this->packConfigData($configData));
            $this->configService->save('config_backup_date', time());
        } catch (Exception $e) {
            processException($e);
        }
    }

    /**
     * @param ConfigData $configData
     *
     * @return string
     */
    private function packConfigData(ConfigData $configData)
    {
        return bin2hex(gzcompress(serialize($configData)));
    }

    /**
     * @return ConfigData
     * @throws ServiceException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws FileException
     */
    public function restore()
    {
        return $this->config->saveConfig(Util::unserialize(
            ConfigData::class,
            $this->getBackup())
        )->getConfigData();
    }

    /**
     * @return ConfigData
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

    protected function initialize()
    {
        $this->configService = $this->dic->get(ConfigService::class);
    }
}