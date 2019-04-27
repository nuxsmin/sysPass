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

namespace SP\Services\Account;

use SP\Config\ConfigData;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ItemPreset\Password;
use SP\Mvc\Controller\Validators\PasswordValidator;
use SP\Services\ItemPreset\ItemPresetInterface;
use SP\Services\ItemPreset\ItemPresetService;


/**
 * Class AccountPreset
 *
 * @package SP\Services\Account
 */
final class AccountPresetService
{
    /**
     * @var ItemPresetService
     */
    private $itemPresetService;
    /**
     * @var ConfigData
     */
    private $configData;

    /**
     * AccountPreset constructor.
     *
     * @param ItemPresetService $itemPresetService
     * @param ConfigData        $configData
     */
    public function __construct(ItemPresetService $itemPresetService, ConfigData $configData)
    {
        $this->itemPresetService = $itemPresetService;
        $this->configData = $configData;
    }

    /**
     * @param AccountRequest $accountRequest
     *
     * @throws ValidationException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     */
    public function checkPasswordPreset(AccountRequest $accountRequest)
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