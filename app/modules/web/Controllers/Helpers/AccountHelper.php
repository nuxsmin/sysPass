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
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\SessionUtil;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\Dto\AccountAclDto;
use SP\DataModel\Dto\AccountDetailsResponse;
use SP\Html\DataGrid\DataGridAction;
use SP\Mgmt\Users\UserPass;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\PublicLink\PublicLinkService;
use SP\Services\Tag\TagService;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
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

    /**
     * @var  Acl
     */
    protected $acl;
    /**
     * @var AccountService
     */
    protected $accountService;
    /**
     * @var AccountHistoryService
     */
    protected $accountHistoryService;
    /**
     * @var string
     */
    private $actionId;
    /**
     * @var AccountAcl
     */
    private $accountAcl;
    /**
     * @var int con el Id de la cuenta
     */
    private $accountId;
    /**
     * @var int el Id de la cuenta en el histórico
     */
    private $accountHistoryId;
    /**
     * @var AccountDetailsResponse
     */
    private $accountDetailsResponse;
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
     * @param AccountHistoryService $accountHistoryService
     * @param AccountHistoryData    $accountHistoryData
     * @param int                   $actionId
     * @return bool
     * @throws \SP\Core\Dic\ContainerException
     */
    public function setAccountHistory(AccountHistoryService $accountHistoryService, AccountHistoryData $accountHistoryData, $actionId)
    {

        $this->actionId = $actionId;
        $this->isHistory = true;
        $this->accountHistoryId = $accountHistoryData->getId();
        $this->accountId = $accountHistoryData->getAccountId();
        $this->accountHistoryService = $accountHistoryService;

        if (!$this->checkAccessHistory($accountHistoryData)) {
            return false;
        }

        $this->view->assign('accountData', $accountHistoryData);
        $this->view->assign('accountAcl', $this->accountAcl);
        $this->view->assign('actionId', $this->actionId);
        $this->view->assign('accountId', $this->accountId);
        $this->view->assign('accountHistoryId', $this->accountHistoryId);
        $this->view->assign('historyData', $this->accountHistoryService->getHistoryForAccount($this->accountId));
        $this->view->assign('accountIsHistory', true);
        $this->view->assign('accountPassDate', date('Y-m-d H:i:s', $accountHistoryData->getPassDate()));
        $this->view->assign('accountPassDateChange', date('Y-m-d', $accountHistoryData->getPassDateChange() ?: 0));
        $this->view->assign('categories', (new SelectItemAdapter(CategoryService::getItemsBasic()))->getItemsFromModelSelected([$accountHistoryData->getCategoryId()]));
        $this->view->assign('clients', (new SelectItemAdapter(ClientService::getItemsBasic()))->getItemsFromModelSelected([$accountHistoryData->getClientId()]));
        $this->view->assign('isModified', strtotime($accountHistoryData->getDateEdit()) !== false);
        $this->view->assign('actions', $this->getActions($accountHistoryData->getParentId()));

        return true;
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @param AccountHistoryData $accountHistoryData
     * @return bool
     * @throws \SP\Core\Dic\ContainerException
     */
    public function checkAccessHistory(AccountHistoryData $accountHistoryData)
    {
        $this->view->assign('showLogo', false);

        if (!$this->acl->checkUserAccess($this->actionId)) {
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_PAGE_NO_PERMISSION);

            return false;
        }

        if (!UserPass::checkUserUpdateMPass($this->session->getUserData()->getId())) {
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_UPDATE_MPASS);

            return false;
        }

        if ($this->accountId > 0) {
            $acccountAclDto = new AccountAclDto();
            $acccountAclDto->setAccountId($accountHistoryData->getAccountId());
            $acccountAclDto->setDateEdit(strtotime($accountHistoryData->getDateEdit()));
            $acccountAclDto->setUserId($accountHistoryData->getUserId());
            $acccountAclDto->setUserGroupId($accountHistoryData->getUserGroupId());
            $acccountAclDto->setUsersId($this->accountHistoryService->getUsersByAccountId($this->accountId));
            $acccountAclDto->setUserGroupsId($this->accountHistoryService->getUserGroupsByAccountId($this->accountId));

            $this->accountAcl = (new AccountAcl($this->actionId, true))->getAcl($acccountAclDto);

            if (!$this->accountAcl->checkAccountAccess()) {
                ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_ACCOUNT_NO_PERMISSION);

                return false;
            }
        }

        return true;
    }

    /**
     * Set icons for view
     *
     * @param int $parentId
     * @return DataGridAction[]
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getActions($parentId = 0)
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
            && $parentId === 0
            && $this->accountAcl->isShowLink()
            && $this->accountAcl->isShowViewPass()
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

            $actionViewPass->addData('parent-id', $parentId);
            $actionCopy->addData('parent-id', $parentId);

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
     * @throws \SP\Core\Dic\ContainerException
     */
    public function setCommonData()
    {
        $userProfileData = $this->session->getUserProfile();

        if ($this->isGotData()) {
            $accountData = $this->accountDetailsResponse->getAccountVData();

            $this->view->assign('accountIsHistory', $this->isHistory);
            $this->view->assign('accountOtherUsers', $this->accountDetailsResponse->getUsers());
            $this->view->assign('accountOtherGroups', $this->accountDetailsResponse->getUserGroups());
            $this->view->assign('accountTags', $this->accountDetailsResponse->getTags());
            $this->view->assign('accountTagsJson', Json::getJson(array_keys($this->accountDetailsResponse->getTags())));

            $accountHistoryService = new AccountHistoryService();
            $this->view->assign('historyData', $accountHistoryService->getHistoryForAccount($this->accountId));

            $this->view->assign('isModified', strtotime($accountData->getDateEdit()) !== false);
            $this->view->assign('maxFileSize', round($this->configData->getFilesAllowedSize() / 1024, 1));
            $this->view->assign('filesAllowedExts', implode(',', $this->configData->getFilesAllowedExts()));

            if ($this->configData->isPublinksEnabled() && $this->accountAcl->isShowLink()) {
                $publicLinkService = new PublicLinkService();
                $publicLinkData = $publicLinkService->getHashForItem($this->accountId);

                $publicLinkUrl = $publicLinkData ? PublicLinkService::getLinkForHash($publicLinkData->getHash()) : null;
                $this->view->assign('publicLinkUrl', $publicLinkUrl);
                $this->view->assign('publicLinkId', $publicLinkData ? $publicLinkData->getId() : 0);
                $this->view->assign('publicLinkShow', true);
            } else {
                $this->view->assign('publicLinkShow', false);
            }

            $this->view->assign('accountPassDate', date('Y-m-d H:i:s', $accountData->getPassDate()));
            $this->view->assign('accountPassDateChange', date('Y-m-d', $accountData->getPassDateChange() ?: 0));
        } else {
            $this->view->assign('accountPassDateChange', date('Y-m-d', time() + 7776000));
        }

        $this->view->assign('customFields', $this->getCustomFieldsForItem(ActionsInterface::ACCOUNT, $this->accountId));
        $this->view->assign('categories', (new SelectItemAdapter(CategoryService::getItemsBasic()))->getItemsFromModel());
        $this->view->assign('clients', (new SelectItemAdapter(ClientService::getItemsBasic()))->getItemsFromModel());

        $userItemAdapter = new SelectItemAdapter(UserService::getItemsBasic());

        $this->view->assign('otherUsers', $userItemAdapter->getItemsFromModel());
        $this->view->assign('otherUsersJson', $userItemAdapter->getJsonItemsFromModel());

        $userGroupItemAdapter = new SelectItemAdapter(UserGroupService::getItemsBasic());

        $this->view->assign('otherGroups', $userGroupItemAdapter->getItemsFromModel());
        $this->view->assign('otherGroupsJson', $userGroupItemAdapter->getJsonItemsFromModel());

        $tagItemAdapter = new SelectItemAdapter(TagService::getItemsBasic());

        $this->view->assign('tagsJson', $tagItemAdapter->getJsonItemsFromModel());
        $this->view->assign('allowPrivate', $userProfileData->isAccPrivate());
        $this->view->assign('allowPrivateGroup', $userProfileData->isAccPrivateGroup());
        $this->view->assign('mailRequestEnabled', $this->configData->isMailRequestsEnabled());
        $this->view->assign('passToImageEnabled', $this->configData->isAccountPassToImage());

        $this->view->assign('otherAccounts', $this->accountService->getForUser($this->accountId));
        $this->view->assign('linkedAccounts', $this->accountService->getLinked($this->accountId));

        $this->view->assign('addClientEnabled', !$this->view->isView && $this->acl->checkUserAccess(ActionsInterface::CLIENT));
        $this->view->assign('addClientRoute', Acl::getActionRoute(ActionsInterface::CLIENT_CREATE));

        $this->view->assign('addCategoryEnabled', !$this->view->isView && $this->acl->checkUserAccess(ActionsInterface::CATEGORY));
        $this->view->assign('addCategoryRoute', Acl::getActionRoute(ActionsInterface::CATEGORY_CREATE));

        $this->view->assign('disabled', $this->view->isView ? 'disabled' : '');
        $this->view->assign('readonly', $this->view->isView ? 'readonly' : '');

        $this->view->assign('showViewCustomPass', $this->accountAcl->isShowViewPass());
        $this->view->assign('accountAcl', $this->accountAcl);

        $this->view->assign('actions', $this->getActions($this->isGotData() ? $this->accountDetailsResponse->getAccountVData()->getParentId() : 0));
    }

    /**
     * @return boolean
     */
    private function isGotData()
    {
        return $this->accountDetailsResponse !== null;
    }

    /**
     * @param string $actionId
     */
    public function setActionId($actionId)
    {
        $this->actionId = $actionId;
    }

    /**
     * @return AccountDetailsResponse
     */
    public function getAccountDetailsResponse()
    {
        return $this->accountDetailsResponse;
    }

    /**
     * Establecer las variables que contienen la información de la cuenta.
     *
     * @param AccountDetailsResponse $accountDetailsResponse
     * @param AccountService         $accountService
     * @param int                    $actionId
     * @return bool
     * @throws \SP\Core\Dic\ContainerException
     */
    public function setAccount(AccountDetailsResponse $accountDetailsResponse, AccountService $accountService, $actionId)
    {
        $this->accountDetailsResponse = $accountDetailsResponse;
        $this->accountService = $accountService;

        $this->accountId = $accountDetailsResponse->getAccountVData()->getId();
        $this->actionId = $actionId;
        $this->isHistory = false;

        if (!$this->checkAccess($accountDetailsResponse)) {
            return false;
        }

        $this->view->assign('actionId', $actionId);
        $this->view->assign('accountId', $this->accountId);
        $this->view->assign('accountData', $accountDetailsResponse->getAccountVData());
        $this->view->assign('gotData', $this->isGotData());

        return true;
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @param AccountDetailsResponse $accountDetailsResponse
     * @return bool
     * @throws \SP\Core\Dic\ContainerException
     */
    public function checkAccess(AccountDetailsResponse $accountDetailsResponse = null)
    {
        $this->view->assign('showLogo', false);

        if (!$this->acl->checkUserAccess($this->actionId)) {
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_PAGE_NO_PERMISSION);

            return false;
        }

        if (!UserPass::checkUserUpdateMPass($this->session->getUserData()->getId())) {
            ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_UPDATE_MPASS);

            return false;
        }

        if ($this->accountId > 0 && $accountDetailsResponse !== null) {
            $accountData = $accountDetailsResponse->getAccountVData();

            $acccountAclDto = new AccountAclDto();
            $acccountAclDto->setAccountId($accountData->getId());
            $acccountAclDto->setDateEdit(strtotime($accountData->getDateEdit()));
            $acccountAclDto->setUserId($accountData->getUserId());
            $acccountAclDto->setUserGroupId($accountData->getUserGroupId());
            $acccountAclDto->setUsersId($accountDetailsResponse->getUsers());
            $acccountAclDto->setUserGroupsId($accountDetailsResponse->getUserGroups());

            $this->accountAcl = (new AccountAcl($this->actionId, $this->isHistory))->getAcl($acccountAclDto);

            if (!$this->accountAcl->checkAccountAccess()) {
                ErrorUtil::showErrorInView($this->view, ErrorUtil::ERR_ACCOUNT_NO_PERMISSION);

                return false;
            }
        }

        return true;
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
        $this->view->assign('isView', false);
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
    }
}