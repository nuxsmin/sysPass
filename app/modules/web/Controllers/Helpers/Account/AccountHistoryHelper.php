<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use SP\Account\AccountAcl;
use SP\Core\Acl\AccountPermissionException;
use SP\Core\Acl\Acl;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\Dto\AccountAclDto;
use SP\Mgmt\Users\UserPass;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Util\ErrorUtil;

/**
 * Class AccountHistoryHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
class AccountHistoryHelper extends HelperBase
{
    /**
     * @var Acl
     */
    protected $acl;
    /**
     * @var AccountHistoryService
     */
    protected $accountHistoryService;
    /**
     * @var int
     */
    protected $accountId;
    /**
     * @var int
     */
    protected $actionId;
    /**
     * @var int
     */
    protected $accountHistoryId;
    /**
     * @var AccountAcl
     */
    protected $accountAcl;

    /**
     * @param AccountHistoryData $accountHistoryData
     * @param int                $actionId
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \SP\Core\Dic\ContainerException
     * @throws AccountPermissionException
     */
    public function setView(AccountHistoryData $accountHistoryData, $actionId)
    {
        $this->actionId = $actionId;
        $this->accountHistoryId = $accountHistoryData->getId();
        $this->accountId = $accountHistoryData->getAccountId();
        $this->accountAcl = new AccountAcl($actionId, true);

        if (!$this->checkActionAccess() || !$this->checkAccess($accountHistoryData)) {
            throw new AccountPermissionException(SPException::INFO);
        }

        $this->view->assign('isView', true);
        $this->view->assign('accountIsHistory', true);
        $this->view->assign('accountData', $accountHistoryData);
        $this->view->assign('accountAcl', $this->accountAcl->getStoredAcl());
        $this->view->assign('actionId', $this->actionId);
        $this->view->assign('accountId', $this->accountId);
        $this->view->assign('accountHistoryId', $this->accountHistoryId);
        $this->view->assign('historyData', $this->accountHistoryService->getHistoryForAccount($this->accountId));
        $this->view->assign('accountPassDate', date('Y-m-d H:i:s', $accountHistoryData->getPassDate()));
        $this->view->assign('accountPassDateChange', date('Y-m-d', $accountHistoryData->getPassDateChange() ?: 0));
        $this->view->assign('categories', SelectItemAdapter::factory(CategoryService::getItemsBasic())->getItemsFromModelSelected([$accountHistoryData->getCategoryId()]));
        $this->view->assign('clients', SelectItemAdapter::factory(ClientService::getItemsBasic())->getItemsFromModelSelected([$accountHistoryData->getClientId()]));
        $this->view->assign('isModified', strtotime($accountHistoryData->getDateEdit()) !== false);

        $actions = $this->dic->get(AccountActionsHelper::class);

        $this->view->assign('actions', $actions->getActionsForAccount($this->accountAcl->getStoredAcl(), new AccountActionsDto($this->accountId, $this->accountHistoryId)));
    }

    /**
     * @return bool
     */
    protected function checkActionAccess()
    {
        if (!$this->acl->checkUserAccess($this->actionId)) {
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_PAGE_NO_PERMISSION);

            return false;
        }

        if (!UserPass::checkUserUpdateMPass($this->session->getUserData()->getId())) {
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_UPDATE_MPASS);

            return false;
        }

        return true;
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @param AccountHistoryData $accountHistoryData
     * @return bool
     */
    protected function checkAccess(AccountHistoryData $accountHistoryData)
    {
        $acccountAclDto = new AccountAclDto();
        $acccountAclDto->setAccountId($accountHistoryData->getAccountId());
        $acccountAclDto->setDateEdit(strtotime($accountHistoryData->getDateEdit()));
        $acccountAclDto->setUserId($accountHistoryData->getUserId());
        $acccountAclDto->setUserGroupId($accountHistoryData->getUserGroupId());
        $acccountAclDto->setUsersId($this->accountHistoryService->getUsersByAccountId($this->accountId));
        $acccountAclDto->setUserGroupsId($this->accountHistoryService->getUserGroupsByAccountId($this->accountId));

        if (!$this->accountAcl->getAcl($acccountAclDto)->getStoredAcl()->checkAccountAccess()) {
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_ACCOUNT_NO_PERMISSION);

            return false;
        }

        return true;
    }

    /**
     * Initialize class
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->acl = $this->dic->get(Acl::class);
        $this->accountHistoryService = $this->dic->get(AccountHistoryService::class);;

        $this->view->assign('sk', $this->session->generateSecurityKey());
    }
}