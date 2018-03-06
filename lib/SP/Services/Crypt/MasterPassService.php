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

namespace SP\Services\Crypt;

use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Services\Account\AccountCryptService;
use SP\Services\Config\ConfigService;
use SP\Services\CustomField\CustomFieldCryptService;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database;
use SP\Storage\DbWrapper;

/**
 * Class MasterPassService
 *
 * @package SP\Services\Crypt
 */
class MasterPassService extends Service
{
    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var AccountCryptService
     */
    protected $accountCryptService;
    /**
     * @var CustomFieldCryptService
     */
    protected $customFieldCryptService;

    /**
     * @param int $userMPassTime
     * @return bool
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    public function checkUserUpdateMPass($userMPassTime)
    {
        $lastUpdateMPass = $this->configService->getByParam('lastupdatempass');

        return $userMPassTime >= $lastUpdateMPass;

    }

    /**
     * @param string $masterPassword
     * @return bool
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    public function checkMasterPassword($masterPassword)
    {
        return Hash::checkHashKey($masterPassword, $this->configService->getByParam('masterPwd'));
    }

    /**
     * @param UpdateMasterPassRequest $request
     * @throws \Exception
     */
    public function changeMasterPassword(UpdateMasterPassRequest $request)
    {
        $db = $this->dic->get(Database::class);

        try {
            if (!DbWrapper::beginTransaction($db)) {
                throw new ServiceException(__u('No es posible iniciar una transacción'), ServiceException::ERROR);
            }

            $this->accountCryptService->updateMasterPassword($request);

            $this->accountCryptService->updateHistoryMasterPassword($request);

            $this->customFieldCryptService->updateMasterPassword($request);

            if (!DbWrapper::endTransaction($db)) {
                throw new ServiceException(__u('No es posible finalizar una transacción'), ServiceException::ERROR);
            }
        } catch (\Exception $e) {
            if (DbWrapper::rollbackTransaction($db)) {
                $this->eventDispatcher->notifyEvent('update.masterPassword.rollback',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Rollback')))
                );

                debugLog('Rollback');
            }

            throw $e;
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->configService = $this->dic->get(ConfigService::class);
        $this->accountCryptService = $this->dic->get(AccountCryptService::class);
        $this->customFieldCryptService = $this->dic->get(CustomFieldCryptService::class);
    }
}