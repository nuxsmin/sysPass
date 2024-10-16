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

namespace SP\Domain\Account\Services;

use SP\Core\Application;
use SP\Domain\Account\Dtos\AccountDto;
use SP\Domain\Account\Ports\AccountPresetService;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\ItemPreset\Models\AccountPermission;
use SP\Domain\ItemPreset\Models\Password;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Mvc\Controller\Validators\PasswordValidator;

/**
 * Class AccountPreset
 */
final class AccountPreset extends Service implements AccountPresetService
{
    public function __construct(
        Application                                   $application,
        private readonly ItemPresetService $itemPresetService,
        private readonly AccountToUserGroupRepository $accountToUserGroupRepository,
        private readonly AccountToUserRepository      $accountToUserRepository,
        private readonly ConfigDataInterface          $configData,
        private readonly PasswordValidator $passwordValidator
    ) {
        parent::__construct($application);
    }

    /**
     * @param AccountDto $accountDto
     * @return AccountDto
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function checkPasswordPreset(AccountDto $accountDto): AccountDto
    {
        $itemPreset = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD);

        if ($itemPreset !== null && $itemPreset->getFixed() === 1) {
            $passwordPreset = $itemPreset->hydrate(Password::class);

            $this->passwordValidator->validate($passwordPreset, $accountDto->pass);

            if ($this->configData->isAccountExpireEnabled()) {
                $expireTimePreset = $passwordPreset->getExpireTime();

                if ($expireTimePreset > 0
                    && ($accountDto->passDateChange === 0
                        || $accountDto->passDateChange < time() + $expireTimePreset)
                ) {
                    return $accountDto->withPassDateChange(time() + $expireTimePreset);
                }
            }
        }

        return $accountDto;
    }

    /**
     * @param int $accountId
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function addPresetPermissions(int $accountId): void
    {
        $itemPresetData =
            $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION);

        if ($itemPresetData?->getFixed()) {
            $userData = $this->context->getUserData();
            $accountPermission = $itemPresetData->hydrate(AccountPermission::class);

            $usersView = array_diff($accountPermission->getUsersView(), [$userData->id]);
            $usersEdit = array_diff($accountPermission->getUsersEdit(), [$userData->id]);
            $userGroupsView = array_diff($accountPermission->getUserGroupsView(), [$userData->userGroupId]);
            $userGroupsEdit = array_diff($accountPermission->getUserGroupsEdit(), [$userData->userGroupId]);

            if (count($usersView) > 0) {
                $this->accountToUserRepository->addByType($accountId, $usersView);
            }

            if (count($usersEdit) > 0) {
                $this->accountToUserRepository->addByType($accountId, $usersEdit, true);
            }

            if (count($userGroupsView) > 0) {
                $this->accountToUserGroupRepository->addByType($accountId, $userGroupsView);
            }

            if (count($userGroupsEdit) > 0) {
                $this->accountToUserGroupRepository->addByType($accountId, $userGroupsEdit, true);
            }
        }
    }
}
