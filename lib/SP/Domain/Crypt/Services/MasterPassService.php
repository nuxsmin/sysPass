<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Crypt\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Domain\Account\AccountCryptServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\ConfigServiceInterface;
use SP\Domain\Config\Services\ConfigService;
use SP\Domain\Crypt\MasterPassServiceInterface;
use SP\Domain\CustomField\CustomFieldCryptServiceInterface;
use SP\Domain\CustomField\Services\CustomFieldCryptService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class MasterPassService
 *
 * @package SP\Domain\Crypt\Services
 */
final class MasterPassService extends Service implements MasterPassServiceInterface
{
    public const PARAM_MASTER_PASS_TIME = 'lastupdatempass';
    public const PARAM_MASTER_PASS_HASH = 'masterPwd';

    protected ConfigService                $configService;
    protected AccountCryptServiceInterface $accountCryptService;
    protected CustomFieldCryptService      $customFieldCryptService;

    public function __construct(
        Application $application,
        ConfigServiceInterface $configService,
        AccountCryptServiceInterface $accountCryptService,
        CustomFieldCryptServiceInterface $customFieldCryptService
    ) {
        parent::__construct($application);

        $this->configService = $configService;
        $this->accountCryptService = $accountCryptService;
        $this->customFieldCryptService = $customFieldCryptService;
    }

    /**
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    public function checkUserUpdateMPass(int $userMPassTime): bool
    {
        return $userMPassTime >= $this->configService->getByParam(self::PARAM_MASTER_PASS_TIME, 0);

    }

    /**
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    public function checkMasterPassword(string $masterPassword): bool
    {
        return Hash::checkHashKey(
            $masterPassword,
            $this->configService->getByParam(self::PARAM_MASTER_PASS_HASH)
        );
    }

    /**
     * @throws Exception
     */
    public function changeMasterPassword(UpdateMasterPassRequest $request): void
    {
        $this->transactionAware(
            function () use ($request) {
                $this->accountCryptService->updateMasterPassword($request);

                $this->accountCryptService->updateHistoryMasterPassword($request);

                $this->customFieldCryptService->updateMasterPassword($request);

                $this->updateConfig($request->getHash());
            }
        );
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateConfig($hash): void
    {
        $this->configService->save(self::PARAM_MASTER_PASS_HASH, $hash);
        $this->configService->save(self::PARAM_MASTER_PASS_TIME, time());
    }
}