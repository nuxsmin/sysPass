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


use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class AccountRequestHelper
 */
final class AccountRequestHelper extends AccountHelperBase
{
    /**
     * Sets account's view variables
     *
     * @param AccountEnrichedDto $accountDetailsResponse
     * @param  int  $actionId
     *
     * @return bool
     * @throws UnauthorizedPageException
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws UpdatedMasterPassException
     */
    public function setViewForRequest(
        AccountEnrichedDto $accountDetailsResponse,
        int $actionId
    ): bool {
        $this->accountId = $accountDetailsResponse->getAccountDataView()->getId();
        $this->actionId = $actionId;
        $this->accountAcl = new AccountAcl($actionId);

        $this->checkActionAccess();

        $accountData = $accountDetailsResponse->getAccountDataView();

        $this->view->assign('accountId', $accountData->getId());
        $this->view->assign('accountData', $accountDetailsResponse->getAccountDataView());
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
