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

namespace SP\Domain\Account\Services;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ItemPreset\Password;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Account\Ports\AccountPresetServiceInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetServiceInterface;
use SP\Mvc\Controller\Validators\PasswordValidator;

/**
 * Class AccountPreset
 *
 * @package SP\Domain\Account\Services
 */
final class AccountPresetService implements AccountPresetServiceInterface
{
    private ItemPresetServiceInterface $itemPresetService;
    private ConfigDataInterface        $configData;

    public function __construct(
        ItemPresetServiceInterface $itemPresetService,
        ConfigDataInterface $configData
    ) {
        $this->itemPresetService = $itemPresetService;
        $this->configData = $configData;
    }

    /**
     * @throws ValidationException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     */
    public function checkPasswordPreset(AccountRequest $accountRequest): void
    {
        $itemPreset = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD);

        if ($itemPreset !== null && $itemPreset->getFixed() === 1) {
            $passwordPreset = $itemPreset->hydrate(Password::class);

            PasswordValidator::factory($passwordPreset)->validate($accountRequest->pass);

            if ($this->configData->isAccountExpireEnabled()) {
                $expireTimePreset = $passwordPreset->getExpireTime();

                if ($expireTimePreset > 0
                    && ($accountRequest->passDateChange === 0
                        || $accountRequest->passDateChange < time() + $expireTimePreset)
                ) {
                    $accountRequest->passDateChange = time() + $expireTimePreset;
                }
            }
        }
    }
}
