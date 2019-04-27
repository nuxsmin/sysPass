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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Bootstrap;
use SP\Core\Acl\AccountPermissionException;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\Dto\AccountAclDto;
use SP\DataModel\Dto\AccountDetailsResponse;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\Http\Uri;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountAcl;
use SP\Services\Account\AccountAclService;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Crypt\MasterPassService;
use SP\Services\ItemPreset\ItemPresetInterface;
use SP\Services\ItemPreset\ItemPresetService;
use SP\Services\PublicLink\PublicLinkService;
use SP\Services\ServiceException;
use SP\Services\Tag\TagService;
use SP\Services\User\UpdatedMasterPassException;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;

/**
 * Class AccountHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class AccountHelper extends HelperBase
{
    use ItemTrait;

    /**
     * @var  Acl
     */
    private $acl;
    /**
     * @var AccountService
     */
    private $accountService;
    /**
     * @var AccountHistoryService
     */
    private $accountHistoryService;
    /**
     * @var PublicLinkService
     */
    private $publicLinkService;
    /**
     * @var ItemPresetService
     */
    private $itemPresetService;
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
     * @var bool
     */
    private $isView = false;

    /**
     * Sets account's view variables
     *
     * @param AccountDetailsResponse $accountDetailsResponse
     * @param int                    $actionId
     *
     * @throws AccountPermissionException
     * @throws SPException
     * @throws UnauthorizedPageException
     * @throws UpdatedMasterPassException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setViewForAccount(AccountDetailsResponse $accountDetailsResponse, $actionId)
    {
        $this->accountId = $accountDetailsResponse->getAccountVData()->getId();
        $this->actionId = $actionId;

        $this->checkActionAccess();
        $this->accountAcl = $this->checkAccess($accountDetailsResponse);

        $accountData = $accountDetailsResponse->getAccountVData();

        $accountActionsDto = new AccountActionsDto($this->accountId, null, $accountData->getParentId());

        $selectUsers = SelectItemAdapter::factory(UserService::getItemsBasic());
        $selectUserGroups = SelectItemAdapter::factory(UserGroupService::getItemsBasic());
        $selectTags = SelectItemAdapter::factory(TagService::getItemsBasic());

        $usersView = SelectItemAdapter::getIdFromArrayOfObjects(
            array_filter($accountDetailsResponse->getUsers(), function ($value) {
                return (int)$value->isEdit === 0;
            }));

        $usersEdit = SelectItemAdapter::getIdFromArrayOfObjects(
            array_filter($accountDetailsResponse->getUsers(), function ($value) {
                return (int)$value->isEdit === 1;
            }));

        $userGroupsView = SelectItemAdapter::getIdFromArrayOfObjects(
            array_filter($accountDetailsResponse->getUserGroups(), function ($value) {
                return (int)$value->isEdit === 0;
            }));

        $userGroupsEdit = SelectItemAdapter::getIdFromArrayOfObjects(
            array_filter($accountDetailsResponse->getUserGroups(), function ($value) {
                return (int)$value->isEdit === 1;
            }));

        $this->view->assign('otherUsersView', $selectUsers->getItemsFromModelSelected($usersView));
        $this->view->assign('otherUsersEdit', $selectUsers->getItemsFromModelSelected($usersEdit));
        $this->view->assign('otherUserGroupsView', $selectUserGroups->getItemsFromModelSelected($userGroupsView));
        $this->view->assign('otherUserGroupsEdit', $selectUserGroups->getItemsFromModelSelected($userGroupsEdit));

        $this->view->assign('users', $selectUsers->getItemsFromModelSelected([$accountData->getUserId()]));
        $this->view->assign('userGroups', $selectUserGroups->getItemsFromModelSelected([$accountData->getUserGroupId()]));

        $this->view->assign('tags',
            $selectTags->getItemsFromModelSelected(SelectItemAdapter::getIdFromArrayOfObjects($accountDetailsResponse->getTags())));

        $this->view->assign('historyData', SelectItemAdapter::factory(
            $this->accountHistoryService->getHistoryForAccount($this->accountId))
            ->getItemsFromArray());

        $this->view->assign('isModified', strtotime($accountData->getDateEdit()) !== false);
        $this->view->assign('maxFileSize', round($this->configData->getFilesAllowedSize() / 1024, 1));
        $this->view->assign('filesAllowedExts', implode(',', $this->configData->getFilesAllowedExts()));

        if ($this->configData->isPublinksEnabled() && $this->accountAcl->isShowLink()) {
            try {
                $publicLinkData = $this->publicLinkService->getHashForItem($this->accountId);
                $accountActionsDto->setPublicLinkId($publicLinkData->getId());
                $accountActionsDto->setPublicLinkCreatorId($publicLinkData->getUserId());

                $baseUrl = ($this->configData->getApplicationUrl() ?: Bootstrap::$WEBURI) . Bootstrap::$SUBURI;

                $this->view->assign('publicLinkUrl', PublicLinkService::getLinkForHash($baseUrl, $publicLinkData->getHash()));
                $this->view->assign('publicLinkId', $publicLinkData->getId());
            } catch (NoSuchItemException $e) {
                $this->view->assign('publicLinkId', 0);
                $this->view->assign('publicLinkUrl', null);
            }

            $this->view->assign('publicLinkShow', true);
        } else {
            $this->view->assign('publicLinkShow', false);
        }

        $userData = $this->context->getUserData();
        $userProfileData = $this->context->getUserProfile();

        $this->view->assign('allowPrivate',
            ($userProfileData->isAccPrivate()
                && $accountData->getUserId() === $userData->getId())
            || $userData->getIsAdminApp());

        $this->view->assign('allowPrivateGroup',
            ($userProfileData->isAccPrivateGroup()
                && $accountData->getUserGroupId() === $userData->getUserGroupId())
            || $userData->getIsAdminApp());

        $this->view->assign('accountPassDate', date('Y-m-d H:i:s', $accountData->getPassDate()));
        $this->view->assign('accountPassDateChange',
            $accountData->getPassDateChange() > 0 ? gmdate('Y-m-d', $accountData->getPassDateChange()) : 0);
        $this->view->assign('linkedAccounts', $this->accountService->getLinked($this->accountId));

        $this->view->assign('accountId', $accountData->getId());
        $this->view->assign('accountData', $accountData);
        $this->view->assign('gotData', true);

        $accountActionsHelper = $this->dic->get(AccountActionsHelper::class);

        $this->view->assign('accountActions', $accountActionsHelper->getActionsForAccount($this->accountAcl, $accountActionsDto));
        $this->view->assign('accountActionsMenu', $accountActionsHelper->getActionsGrouppedForAccount($this->accountAcl, $accountActionsDto));

        $this->setViewCommon();
    }

    /**
     * @throws NoSuchItemException
     * @throws UnauthorizedPageException
     * @throws UpdatedMasterPassException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ServiceException
     */
    public function checkActionAccess()
    {
        if (!$this->acl->checkUserAccess($this->actionId)) {
            throw new UnauthorizedPageException(UnauthorizedPageException::INFO);
        }

        if (!$this->dic->get(MasterPassService::class)
            ->checkUserUpdateMPass($this->context->getUserData()->getLastUpdateMPass())
        ) {
            throw new UpdatedMasterPassException(UpdatedMasterPassException::INFO);
        }
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @param AccountDetailsResponse $accountDetailsResponse
     *
     * @return AccountAcl
     * @throws AccountPermissionException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function checkAccess(AccountDetailsResponse $accountDetailsResponse)
    {
        $accountAcl = $this->dic->get(AccountAclService::class)
            ->getAcl($this->actionId, AccountAclDto::makeFromAccount($accountDetailsResponse));

        if ($accountAcl === null || $accountAcl->checkAccountAccess($this->actionId) === false) {
            throw new AccountPermissionException(AccountPermissionException::INFO);
        }

        return $accountAcl;
    }

    /**
     * Sets account's view common data
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    protected function setViewCommon()
    {
        $this->view->assign('isView', $this->isView);

        $this->view->assign('accountIsHistory', false);

        $this->view->assign('customFields', $this->getCustomFieldsForItem(ActionsInterface::ACCOUNT, $this->accountId));

        $this->view->assign('categories',
            SelectItemAdapter::factory($this->dic->get(CategoryService::class)
                ->getAllBasic())->getItemsFromModel());

        $this->view->assign('clients',
            SelectItemAdapter::factory($this->dic->get(ClientService::class)
                ->getAllForUser())->getItemsFromModel());

        $this->view->assign('mailRequestEnabled', $this->configData->isMailRequestsEnabled());
        $this->view->assign('passToImageEnabled', $this->configData->isAccountPassToImage());

        $this->view->assign('otherAccounts', $this->accountService->getForUser($this->accountId));

        $this->view->assign('addClientEnabled',
            !$this->isView && $this->acl->checkUserAccess(ActionsInterface::CLIENT));
        $this->view->assign('addClientRoute', Acl::getActionRoute(ActionsInterface::CLIENT_CREATE));

        $this->view->assign('addCategoryEnabled',
            !$this->isView && $this->acl->checkUserAccess(ActionsInterface::CATEGORY));

        $this->view->assign('addCategoryRoute', Acl::getActionRoute(ActionsInterface::CATEGORY_CREATE));

        $this->view->assign('addTagEnabled',
            !$this->isView && $this->acl->checkUserAccess(ActionsInterface::TAG));
        $this->view->assign('addTagRoute', Acl::getActionRoute(ActionsInterface::TAG_CREATE));

        $this->view->assign('fileListRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_LIST));
        $this->view->assign('fileUploadRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_UPLOAD));

        $this->view->assign('disabled', $this->isView ? 'disabled' : '');
        $this->view->assign('readonly', $this->isView ? 'readonly' : '');

        $this->view->assign('showViewCustomPass', $this->accountAcl->isShowViewPass());
        $this->view->assign('accountAcl', $this->accountAcl);

        $this->view->assign('deepLink', $this->getDeepLink());
    }

    /**
     * @return string
     */
    private function getDeepLink()
    {
        $route = Acl::getActionRoute($this->actionId) . ($this->accountId ? '/' . $this->accountId : '');

        $baseUrl = ($this->configData->getApplicationUrl() ?: Bootstrap::$WEBURI) . Bootstrap::$SUBURI;

        $uri = new Uri($baseUrl);
        $uri->addParam('r', $route);

        return $uri->getUriSigned($this->configData->getPasswordSalt());
    }

    /**
     * Sets account's view for a blank form
     *
     * @param $actionId
     *
     * @return void
     * @throws NoSuchItemException
     * @throws UnauthorizedPageException
     * @throws UpdatedMasterPassException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function setViewForBlank($actionId)
    {
        $this->actionId = $actionId;
        $this->accountAcl = new AccountAcl($actionId);

        $this->checkActionAccess();

        $userProfileData = $this->context->getUserProfile();
        $userData = $this->context->getUserData();

        $this->accountAcl->setShowPermission($userData->getIsAdminApp() || $userData->getIsAdminAcc() || $userProfileData->isAccPermission());

        $accountPrivate = new AccountPrivate();

        if ($itemPresetPrivate = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)) {
            $accountPrivate = $itemPresetPrivate->hydrate(AccountPrivate::class) ?: $accountPrivate;
        }

        $accountPermission = new AccountPermission();

        if ($itemPresetPermission = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION)) {
            $accountPermission = $itemPresetPermission->hydrate(AccountPermission::class) ?: $accountPermission;
        }

        $selectUsers = SelectItemAdapter::factory(UserService::getItemsBasic());
        $selectUserGroups = SelectItemAdapter::factory(UserGroupService::getItemsBasic());
        $selectTags = SelectItemAdapter::factory(TagService::getItemsBasic());

        $this->view->assign('accountPassDateChange', date('Y-m-d', time() + 7776000));
        $this->view->assign('otherUsersView', $selectUsers->getItemsFromModelSelected($accountPermission->getUsersView()));
        $this->view->assign('otherUsersEdit', $selectUsers->getItemsFromModelSelected($accountPermission->getUsersEdit()));
        $this->view->assign('otherUserGroupsView', $selectUserGroups->getItemsFromModelSelected($accountPermission->getUserGroupsView()));
        $this->view->assign('otherUserGroupsEdit', $selectUserGroups->getItemsFromModelSelected($accountPermission->getUserGroupsEdit()));

        $this->view->assign('users', $selectUsers->getItemsFromModel());
        $this->view->assign('userGroups', $selectUserGroups->getItemsFromModel());
        $this->view->assign('tags', $selectTags->getItemsFromModel());

        $this->view->assign('allowPrivate', $userProfileData->isAccPrivate() || $userData->getIsAdminApp());
        $this->view->assign('allowPrivateGroup', $userProfileData->isAccPrivateGroup() || $userData->getIsAdminApp());
        $this->view->assign('privateUserCheck', $accountPrivate->isPrivateUser());
        $this->view->assign('privateUserGroupCheck', $accountPrivate->isPrivateGroup());

        $this->view->assign('accountId', 0);
        $this->view->assign('gotData', false);

        $this->view->assign('accountActions',
            $this->dic->get(AccountActionsHelper::class)
                ->getActionsForAccount($this->accountAcl, new AccountActionsDto($this->accountId)));

        $this->setViewCommon();
    }

    /**
     * Sets account's view variables
     *
     * @param AccountDetailsResponse $accountDetailsResponse
     * @param int                    $actionId
     *
     * @return bool
     * @throws NoSuchItemException
     * @throws UnauthorizedPageException
     * @throws UpdatedMasterPassException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ServiceException
     */
    public function setViewForRequest(AccountDetailsResponse $accountDetailsResponse, $actionId)
    {
        $this->accountId = $accountDetailsResponse->getAccountVData()->getId();
        $this->actionId = $actionId;
        $this->accountAcl = new AccountAcl($actionId);

        $this->checkActionAccess();

        $accountData = $accountDetailsResponse->getAccountVData();

        $this->view->assign('accountId', $accountData->getId());
        $this->view->assign('accountData', $accountDetailsResponse->getAccountVData());

        $this->view->assign('accountActions',
            $this->dic->get(AccountActionsHelper::class)
                ->getActionsForAccount($this->accountAcl, new AccountActionsDto($this->accountId, null, $accountData->getParentId())));

        return true;
    }

    /**
     * @param bool $isView
     */
    public function setIsView($isView)
    {
        $this->isView = (bool)$isView;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->acl = $this->dic->get(Acl::class);
        $this->accountService = $this->dic->get(AccountService::class);
        $this->accountHistoryService = $this->dic->get(AccountHistoryService::class);
        $this->publicLinkService = $this->dic->get(PublicLinkService::class);
        $this->itemPresetService = $this->dic->get(ItemPresetService::class);

        $this->view->assign('changesHash', '');
        $this->view->assign('chkUserEdit', false);
        $this->view->assign('chkGroupEdit', false);
    }
}