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

use SP\Account\Account;
use SP\Account\AccountAcl;
use SP\Account\AccountHistory;
use SP\Account\AccountUtil;
use SP\Account\UserAccounts;
use SP\Controller\ControllerBase;
use SP\Core\Acl;
use SP\Core\ActionsInterface;
use SP\Core\Init;
use SP\Core\SessionUtil;
use SP\DataModel\AccountExtData;
use SP\DataModel\CustomFieldData;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Groups\GroupAccountsUtil;
use SP\Mgmt\PublicLinks\PublicLink;
use SP\Mgmt\Tags\Tag;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Json;

/**
 * Class AccountHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
class AccountHelper extends HelperBase
{
    /** @var  Acl */
    protected $acl;
    /**
     * @var string
     */
    private $actionId;
    /**
     * @var AccountAcl
     */
    private $AccountAcl;
    /**
     * @var Account|AccountHistory instancia para el manejo de datos de una cuenta
     */
    private $Account;
    /**
     * @var int con el id de la cuenta
     */
    private $id;
    /**
     * @var AccountExtData
     */
    private $AccountData;

    /**
     * @param Acl $acl
     */
    public function inject(Acl $acl)
    {
        $this->acl = $acl;
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
        $this->id = $accountId;
        $this->actionId = $actionId;

        $Account = new Account(new AccountExtData($accountId));
        $this->Account = $Account;
        $this->AccountData = $Account->getData();

        $this->view->assign('accountId', $accountId);
        $this->view->assign('accountData', $this->AccountData);
        $this->view->assign('gotData', $this->isGotData());
    }

    /**
     * @return boolean
     */
    private function isGotData()
    {
        return $this->AccountData !== null;
    }

    /**
     * Establecer las variables que contienen la información de la cuenta en una fecha concreta.
     *
     * @param $accountId
     * @param $actionId
     * @throws \SP\Core\Exceptions\SPException
     */
    public function setAccountDataHistory($accountId, $actionId)
    {
        $this->id = $accountId;
        $this->actionId = $actionId;

        $Account = new AccountHistory(new AccountExtData());
        $Account->setId($accountId);
        $this->Account = $Account;
        $this->AccountData = $Account->getData();

        $this->view->assign('accountId', $this->AccountData->getAccountId());
        $this->view->assign('accountData', $this->AccountData);
        $this->view->assign('gotData', $this->isGotData());
        $this->view->assign('accountHistoryId', $accountId);
    }

    /**
     * @return AccountAcl
     */
    public function getAccountAcl()
    {
        return $this->AccountAcl;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Establecer variables comunes del formulario para todos los interfaces
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function setCommonData()
    {
        $userProfileData = $this->session->getUserProfile();

        $this->getCustomFieldsForItem();

        if ($this->isGotData()) {
            $this->view->assign('accountIsHistory', $this->getAccount()->getAccountIsHistory());
            $this->view->assign('accountOtherUsers', UserAccounts::getUsersInfoForAccount($this->id));
            $this->view->assign('accountOtherGroups', GroupAccountsUtil::getGroupsInfoForAccount($this->id));
            $this->view->assign('accountTagsJson', Json::getJson(array_keys($this->getAccount()->getAccountData()->getTags())));
            $this->view->assign('historyData', AccountHistory::getAccountList($this->AccountData->getAccountId()));
            $this->view->assign('isModified', strtotime($this->AccountData->getAccountDateEdit()) !== false);
            $this->view->assign('maxFileSize', round($this->configData->getFilesAllowedSize() / 1024, 1));
            $this->view->assign('filesAllowedExts', implode(',', $this->configData->getFilesAllowedExts()));

            $PublicLinkData = PublicLink::getItem()->getHashForItem($this->id);

            $publicLinkUrl = ($this->configData->isPublinksEnabled() && $PublicLinkData ? Init::$WEBURI . '/index.php?h=' . $PublicLinkData->getPublicLinkHash() . '&a=link' : null);
            $this->view->assign('publicLinkUrl', $publicLinkUrl);
            $this->view->assign('publicLinkId', $PublicLinkData ? $PublicLinkData->getPublicLinkId() : 0);

            $this->view->assign('accountPassDate', date('Y-m-d H:i:s', $this->AccountData->getAccountPassDate()));
            $this->view->assign('accountPassDateChange', date('Y-m-d', $this->AccountData->getAccountPassDateChange() ?: 0));
        } else {
            $this->view->assign('accountPassDateChange', date('Y-m-d', time() + 7776000));
        }


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

        $this->view->assign('otherAccounts', AccountUtil::getAccountsForUser($this->session, $this->id));
        $this->view->assign('linkedAccounts', AccountUtil::getLinkedAccounts($this->id, $this->session));

        $this->view->assign('addCustomerEnabled', $this->acl->checkUserAccess(ActionsInterface::ACTION_MGM_CUSTOMERS));
        $this->view->assign('addCategoryEnabled', $this->acl->checkUserAccess(ActionsInterface::ACTION_MGM_CATEGORIES));

        $this->view->assign('disabled', $this->view->isView ? 'disabled' : '');
        $this->view->assign('readonly', $this->view->isView ? 'readonly' : '');

        $this->view->assign('showViewCustomPass', $this->AccountAcl->isShowViewPass());
        $this->view->assign('AccountAcl', $this->AccountAcl);
        $this->view->assign('actions', $this->getActions());
    }

    /**
     * Obtener la lista de campos personalizados y sus valores
     */
    private function getCustomFieldsForItem()
    {
        $this->view->assign('customFields', CustomField::getItem(new CustomFieldData(ActionsInterface::ACTION_ACC))->getById($this->id));
    }

    /**
     * @return Account|AccountHistory
     */
    public function getAccount()
    {
        return $this->Account ?: new Account(new AccountExtData());
    }

    /**
     * Set icons for view
     */
    protected function getActions()
    {
        $actionsEnabled = [];

        $actions = new AccountActionsHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
        $account = $this->getAccount();

        /** @var AccountExtData $accountData */
        $accountData = $account->getAccountData();

        if ($this->AccountAcl->isShowDelete()) {
            $actionsEnabled[] = $actions->getDeleteAction()->addData('item-id', $accountData->getAccountId());
        }

        if ($this->AccountAcl->isShowLink()
            && $this->AccountAcl->isShowViewPass()
            && $account->getAccountParentId() === 0
            && $account->getAccountIsHistory() !== 1
        ) {
            if (null === $this->view->publicLinkUrl) {
                $actionsEnabled[] = $actions->getPublicLinkAction();
            } else {
                $actionsEnabled[] = $actions->getPublicLinkRefreshAction();
            }
        }

        if ($this->AccountAcl->isShowViewPass()) {
            $action = $actions->getViewPassAction();
            $actionCopy = $actions->getCopyPassAction();

            $action->addData('parent-id', $accountData->getAccountParentId());
            $actionCopy->addData('parent-id', $accountData->getAccountParentId());

            $action->addData('history', $account->getAccountIsHistory());
            $actionCopy->addData('history', $account->getAccountIsHistory());

            if ($account->getAccountIsHistory()) {
                $action->addData('item-id', $this->id);
                $actionCopy->addData('item-id', $this->id);
            } else {
                $action->addData('item-id', $accountData->getAccountId());
                $actionCopy->addData('item-id', $accountData->getAccountId());
            }

            $actionsEnabled[] = $action;
            $actionsEnabled[] = $actionCopy;
        }

        if ($this->AccountAcl->isShowCopy()) {
            $actionsEnabled[] = $actions->getCopyAction()->addData('item-id', $accountData->getAccountId());
        }

        if ($this->AccountAcl->isShowEditPass()) {
            $actionsEnabled[] = $actions->getEditPassAction()->addData('item-id', $accountData->getAccountId());
        }

        if ($this->AccountAcl->isShowEdit()) {
            $actionsEnabled[] = $actions->getEditAction()->addData('item-id', $accountData->getAccountId());
        }

        if ($this->actionId === ActionsInterface::ACTION_ACC_VIEW
            && !$this->AccountAcl->isShowEdit()
            && $this->configData->isMailRequestsEnabled()
        ) {
            $actionsEnabled[] = $actions->getRequestAction()->addData('item-id', $accountData->getAccountId());
        }

        if ($this->AccountAcl->isShowRestore()) {
            $action = $actions->getRestoreAction();
            $action->addData('item-id', $accountData->getAccountId());
            $action->addData('history-id', $this->id);

            $actionsEnabled[] = $action;
        }

        if ($this->AccountAcl->isShowSave()) {
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

        $Acl = new AccountAcl($this->Account, $this->actionId);
        $this->AccountAcl = $Acl;

        if (!$this->acl->checkUserAccess($this->actionId)) {
            $controller->showError(ControllerBase::ERR_PAGE_NO_PERMISSION);
            return false;
        }

        if (!UserPass::checkUserUpdateMPass($this->session->getUserData()->getUserId())) {
            $controller->showError(ControllerBase::ERR_UPDATE_MPASS);
            return false;
        }

        if ($this->id > 0) {
            $this->AccountAcl = $Acl->getAcl();

            if (!$this->AccountAcl->checkAccountAccess()) {
                $controller->showError(ControllerBase::ERR_ACCOUNT_NO_PERMISSION);
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
     * Initialize
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