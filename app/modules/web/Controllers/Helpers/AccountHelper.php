<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Account\AccountAcl;
use SP\Account\AccountUtil;
use SP\Account\UserAccounts;
use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\SessionUtil;
use SP\DataModel\AccountExtData;
use SP\DataModel\CustomFieldData;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Groups\GroupAccountsUtil;
use SP\Mgmt\Tags\Tag;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserUtil;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Account\AccountService;
use SP\Services\CustomField\CustomFieldService;
use SP\Services\PublicLink\PublicLinkService;
use SP\Util\ErrorUtil;
use SP\Util\Json;

/**
 * Class AccountHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
class AccountHelper extends HelperBase
{
    use ItemTrait;

    /** @var  Acl */
    protected $acl;
    /**
     * @var string
     */
    private $actionId;
    /**
     * @var AccountAcl
     */
    private $accountAcl;
    /**
     * @var AccountService
     */
    private $accountService;
    /**
     * @var int con el Id de la cuenta
     */
    private $accountId;
    /**
     * @var int el Id de la cuenta en el histórico
     */
    private $accountHistoryId;
    /**
     * @var AccountExtData
     */
    private $accountData;
    /**
     * @var bool
     */
    private $isHistory;

    /**
     * @param Acl $acl
     */
    public function inject(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * Establecer las variables que contienen la información de la cuenta en una fecha concreta.
     *
     * @param $accountHistoryId
     * @param $actionId
     */
    public function setAccountDataHistory($accountHistoryId, $actionId)
    {
        $this->accountHistoryId = $accountHistoryId;
        $this->actionId = $actionId;
        $this->isHistory = true;

        $this->accountService = new AccountHistoryService();
        $this->accountData = $this->accountService->getById($accountHistoryId);
        $this->accountId = $this->accountData->getAccountId();

        $this->view->assign('accountId', $this->accountId);
        $this->view->assign('accountData', $this->accountData);
        $this->view->assign('gotData', $this->isGotData());
        $this->view->assign('accountHistoryId', $accountHistoryId);
    }

    /**
     * @return boolean
     */
    private function isGotData()
    {
        return $this->accountData !== null;
    }

    /**
     * @return AccountAcl
     */
    public function getAccountAcl()
    {
        return $this->accountAcl;
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * Establecer variables comunes del formulario para todos los interfaces
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function setCommonData()
    {
        $userProfileData = $this->session->getUserProfile();

        if ($this->isGotData()) {
            $accountHistoryService = new AccountHistoryService();

            $this->view->assign('accountIsHistory', $this->isHistory);
            $this->view->assign('accountOtherUsers', UserAccounts::getUsersInfoForAccount($this->accountId));
            $this->view->assign('accountOtherGroups', GroupAccountsUtil::getGroupsInfoForAccount($this->accountId));
            $this->view->assign('accountTagsJson', Json::getJson(array_keys($this->accountData->getTags())));
            $this->view->assign('historyData', $accountHistoryService->getHistoryForAccount($this->accountId));
            $this->view->assign('isModified', strtotime($this->accountData->getAccountDateEdit()) !== false);
            $this->view->assign('maxFileSize', round($this->configData->getFilesAllowedSize() / 1024, 1));
            $this->view->assign('filesAllowedExts', implode(',', $this->configData->getFilesAllowedExts()));

            if ($this->configData->isPublinksEnabled() && $this->accountAcl->isShowLink()) {
                $publicLinkService = new PublicLinkService();
                $publicLinkData = $publicLinkService->getHashForItem($this->accountId);

                $publicLinkUrl = $publicLinkData ? PublicLinkService::getLinkForHash($publicLinkData->getPublicLinkHash()) : null;
                $this->view->assign('publicLinkUrl', $publicLinkUrl);
                $this->view->assign('publicLinkId', $publicLinkData ? $publicLinkData->getPublicLinkId() : 0);
                $this->view->assign('publicLinkShow', true);
            } else {
                $this->view->assign('publicLinkShow', false);
            }

            $this->view->assign('accountPassDate', date('Y-m-d H:i:s', $this->accountData->getAccountPassDate()));
            $this->view->assign('accountPassDateChange', date('Y-m-d', $this->accountData->getAccountPassDateChange() ?: 0));
        } else {
            $this->view->assign('accountPassDateChange', date('Y-m-d', time() + 7776000));
        }


        $this->view->assign('customFields', $this->getCustomFieldsForItem(ActionsInterface::ACCOUNT, $this->accountId));
        $this->view->assign('actionId', Acl::getActionRoute($this->actionId));
        $this->view->assign('categories', Category::getItem()->getItemsForSelect());
        $this->view->assign('customers', Customer::getItem()->getItemsForSelectByUser());
        $this->view->assign('otherUsers', UserUtil::getUsersLogin());
        $this->view->assign('otherUsersJson', Json::getJson($this->view->otherUsers));
        $this->view->assign('otherGroups', Group::getItem()->getItemsForSelect());
        $this->view->assign('otherGroupsJson', Json::getJson($this->view->otherGroups));
        $this->view->assign('tagsJson', Json::getJson(Tag::getItem()->getItemsForSelect()));
        $this->view->assign('allowPrivate', $userProfileData->isAccPrivate());
        $this->view->assign('allowPrivateGroup', $userProfileData->isAccPrivateGroup());
        $this->view->assign('mailRequestEnabled', $this->configData->isMailRequestsEnabled());
        $this->view->assign('passToImageEnabled', $this->configData->isAccountPassToImage());

        $this->view->assign('otherAccounts', AccountUtil::getAccountsForUser($this->session, $this->accountId));
        $this->view->assign('linkedAccounts', AccountUtil::getLinkedAccounts($this->accountId, $this->session));

        $this->view->assign('addCustomerEnabled', $this->acl->checkUserAccess(ActionsInterface::CLIENT));
        $this->view->assign('addCategoryEnabled', $this->acl->checkUserAccess(ActionsInterface::CATEGORY));

        $this->view->assign('disabled', $this->view->isView ? 'disabled' : '');
        $this->view->assign('readonly', $this->view->isView ? 'readonly' : '');

        $this->view->assign('showViewCustomPass', $this->accountAcl->isShowViewPass());
        $this->view->assign('AccountAcl', $this->accountAcl);
        $this->view->assign('actions', $this->getActions());
    }

    /**
     * Set icons for view
     */
    protected function getActions()
    {
        $actionsEnabled = [];

        $actions = new AccountActionsHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        $actionBack = $actions->getBackAction();

        if ($this->isHistory) {
            $actionBack->addData('item-id', $this->accountId);
            $actionBack->setName(__('Ver Actual'));
            $actionBack->setTitle(__('Ver Actual'));
        } else {
            $actionBack->setData([]);
            $actionBack->setClasses(['btn-back']);
        }

        $actionsEnabled[] = $actionBack;

        if ($this->accountAcl->isShowDelete()) {
            $actionsEnabled[] = $actions->getDeleteAction()->addData('item-id', $this->accountId);
        }

        if ($this->isHistory === false
            && $this->accountAcl->isShowLink()
            && $this->accountAcl->isShowViewPass()
            && $this->accountData->getAccountParentId() === 0
        ) {
            if (null === $this->view->publicLinkUrl) {
                $actionsEnabled[] = $actions->getPublicLinkAction();
            } else {
                $actionsEnabled[] = $actions->getPublicLinkRefreshAction();
            }
        }

        if ($this->accountAcl->isShowViewPass()) {
            $actionViewPass = $actions->getViewPassAction();
            $actionCopy = $actions->getCopyPassAction();

            $actionViewPass->addData('parent-id', $this->accountData->getAccountParentId());
            $actionCopy->addData('parent-id', $this->accountData->getAccountParentId());

            $actionViewPass->addData('history', (int)$this->isHistory);
            $actionCopy->addData('history', (int)$this->isHistory);

            if ($this->isHistory) {
                $actionViewPass->addData('item-id', $this->accountHistoryId);
                $actionCopy->addData('item-id', $this->accountHistoryId);
            } else {
                $actionViewPass->addData('item-id', $this->accountId);
                $actionCopy->addData('item-id', $this->accountId);
            }

            $actionsEnabled[] = $actionViewPass;
            $actionsEnabled[] = $actionCopy;
        }

        if ($this->accountAcl->isShowCopy()) {
            $actionsEnabled[] = $actions->getCopyAction()->addData('item-id', $this->accountId);
        }

        if ($this->accountAcl->isShowEditPass()) {
            $actionsEnabled[] = $actions->getEditPassAction()->addData('item-id', $this->accountId);
        }

        if ($this->accountAcl->isShowEdit()) {
            $actionsEnabled[] = $actions->getEditAction()->addData('item-id', $this->accountId);
        }

        if ($this->actionId === ActionsInterface::ACCOUNT_VIEW
            && !$this->accountAcl->isShowEdit()
            && $this->configData->isMailRequestsEnabled()
        ) {
            $actionsEnabled[] = $actions->getRequestAction()->addData('item-id', $this->accountId);
        }

        if ($this->accountAcl->isShowRestore()) {
            $actionRestore = $actions->getRestoreAction();
            $actionRestore->addData('item-id', $this->accountId);
            $actionRestore->addData('history-id', $this->accountHistoryId);

            $actionsEnabled[] = $actionRestore;
        }

        if ($this->accountAcl->isShowSave()) {
            $actionsEnabled[] = $actions->getSaveAction()->addAttribute('form', 'frmAccount');
        }

        return $actionsEnabled;
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @param ControllerBase $controller
     * @return bool
     */
    public function checkAccess(ControllerBase $controller)
    {
        $this->view->assign('showLogo', false);

        $acl = new AccountAcl($this->actionId, $this->accountData, $this->isHistory);
        $this->accountAcl = $acl;

        if (!$this->acl->checkUserAccess($this->actionId)) {
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_PAGE_NO_PERMISSION);

            return false;
        }

        if (!UserPass::checkUserUpdateMPass($this->session->getUserData()->getUserId())) {
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_UPDATE_MPASS);

            return false;
        }

        if ($this->accountId > 0) {
            $this->accountAcl = $acl->getAcl();

            if (!$this->accountAcl->checkAccountAccess()) {
                ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_ACCOUNT_NO_PERMISSION);

                return false;
            }
        }

        return true;
    }

    /**
     * @param string $actionId
     */
    public function setActionId($actionId)
    {
        $this->actionId = $actionId;
    }

    /**
     * @return AccountService
     */
    public function getAccountService()
    {
        return $this->accountService;
    }

    /**
     * @return AccountExtData
     */
    public function getAccountData()
    {
        return $this->accountData;
    }

    /**
     * Establecer las variables que contienen la información de la cuenta.
     *
     * @param $accountId
     * @param $actionId
     * @throws \SP\Core\Exceptions\SPException
     */
    public function setAccountData($accountId, $actionId)
    {
        $this->accountId = $accountId;
        $this->actionId = $actionId;
        $this->isHistory = false;

        $this->accountService = new AccountService();
        $this->accountData = $this->accountService->getById($accountId);

        $this->view->assign('accountId', $accountId);
        $this->view->assign('accountData', $this->accountData);
        $this->view->assign('gotData', $this->isGotData());
    }

    /**
     * @return int
     */
    public function getAccountHistoryId()
    {
        return $this->accountHistoryId;
    }

    /**
     * Initialize
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function initialize()
    {
        $this->view->assign('changesHash');
        $this->view->assign('chkUserEdit');
        $this->view->assign('chkGroupEdit');
        $this->view->assign('gotData', $this->isGotData());
        $this->view->assign('isView', false);
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
    }
}