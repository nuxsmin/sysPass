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

namespace SP\Services\Config;

use SP\Services\Service;
use SP\Services\ServiceException;

/**
 * Class ConfigBackupService
 *
 * @package SP\Services\Config
 */
class ConfigBackupService extends Service
{
    /**
     * @var ConfigService
     */
    protected $configService;

    /**
     * Backups the config data into the database
     */
    public function backup()
    {
        try {
            $this->configService->save('config_backup', bin2hex(gzcompress(serialize($this->config->getConfigData()))));
            $this->configService->save('config_backup_date', time());
        } catch (\Exception $e) {
            processException($e);
        }
    }

    /**
     * @throws ServiceException
     */
    public function restore()
    {
        throw new ServiceException('Not implemented');
    }

    protected function initialize()
    {
        $this->configService = $this->dic->get(ConfigService::class);
    }
}