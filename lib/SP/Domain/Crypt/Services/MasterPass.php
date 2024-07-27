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

namespace SP\Domain\Crypt\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\processException;

/**
 * Class MasterPass
 */
final class MasterPass extends Service implements MasterPassService
{
    public const PARAM_MASTER_PASS_TIME = 'lastupdatempass';
    public const PARAM_MASTER_PASS_HASH = 'masterPwd';

    public function __construct(
        Application                                   $application,
        private readonly ConfigService                $configService,
        private readonly AccountMasterPasswordService $accountMasterPasswordService,
        private readonly CustomFieldCryptService      $customFieldCryptService,
        private readonly Repository                   $repository
    ) {
        parent::__construct($application);
    }

    /**
     * @inheritDoc
     */
    public function checkUserUpdateMPass(int $userMPassTime): bool
    {
        try {
            return $userMPassTime >= (int)$this->configService->getByParam(self::PARAM_MASTER_PASS_TIME, 0);
        } catch (ServiceException|NoSuchItemException $e) {
            processException($e);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function checkMasterPassword(string $masterPassword): bool
    {
        try {
            return Hash::checkHashKey(
                $masterPassword,
                $this->configService->getByParam(self::PARAM_MASTER_PASS_HASH)
            );
        } catch (ServiceException|NoSuchItemException $e) {
            processException($e);
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function changeMasterPassword(UpdateMasterPassRequest $request): void
    {
        $this->repository->transactionAware(
            function () use ($request) {
                $this->accountMasterPasswordService->updateMasterPassword($request);
                $this->accountMasterPasswordService->updateHistoryMasterPassword($request);
                $this->customFieldCryptService->updateMasterPassword($request);
            },
            $this
        );

        $this->updateConfig($request->getHash());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateConfig($hash): void
    {
        $this->configService->save(self::PARAM_MASTER_PASS_HASH, $hash);
        $this->configService->save(self::PARAM_MASTER_PASS_TIME, (string)time());
    }
}
