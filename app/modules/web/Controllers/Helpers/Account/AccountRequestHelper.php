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

namespace SP\Modules\Web\Controllers\Helpers\Account;


use SP\DataModel\Dto\AccountDetailsResponse;
use SP\Domain\Account\Services\AccountAcl;

/**
 * Class AccountRequestHelper
 */
final class AccountRequestHelper extends AccountHelperBase
{
    /**
     * Sets account's view variables
     *
     * @param  AccountDetailsResponse  $accountDetailsResponse
     * @param  int  $actionId
     *
     * @return bool
     * @throws \SP\Core\Acl\UnauthorizedPageException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Domain\User\Services\UpdatedMasterPassException
     */
    public function setViewForRequest(
        AccountDetailsResponse $accountDetailsResponse,
        int $actionId
    ): bool {
        $this->accountId = $accountDetailsResponse->getAccountVData()->getId();
        $this->actionId = $actionId;
        $this->accountAcl = new AccountAcl($actionId);

        $this->checkActionAccess();

        $accountData = $accountDetailsResponse->getAccountVData();

        $this->view->assign('accountId', $accountData->getId());
        $this->view->assign('accountData', $accountDetailsResponse->getAccountVData());
        $this->view->assign(
            'accountActions',
            $this->accountActionsHelper->getActionsForAccount(
                $this->accountAcl,
                new AccountActionsDto(
                    $this->accountId,
                    null,
                    $accountData->getParentId()
                )
            )
        );

        return true;
    }
}