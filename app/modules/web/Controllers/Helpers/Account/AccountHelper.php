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

use SP\Core\Acl\AccountPermissionException;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\Dto\AccountAclDto;
use SP\DataModel\Dto\AccountEnrichedDto;
use SP\DataModel\ItemPreset\AccountPermission;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ProfileData;
use SP\Domain\Account\AccountAclServiceInterface;
use SP\Domain\Account\AccountHistoryServiceInterface;
use SP\Domain\Account\AccountServiceInterface;
use SP\Domain\Account\PublicLinkServiceInterface;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Account\Services\PublicLinkService;
use SP\Domain\Category\CategoryServiceInterface;
use SP\Domain\Client\ClientServiceInterface;
use SP\Domain\Crypt\MasterPassServiceInterface;
use SP\Domain\CustomField\CustomFieldServiceInterface;
use SP\Domain\ItemPreset\ItemPresetInterface;
use SP\Domain\ItemPreset\ItemPresetServiceInterface;
use SP\Domain\Tag\TagServiceInterface;
use SP\Domain\User\UserGroupServiceInterface;
use SP\Domain\User\UserServiceInterface;
use SP\Http\RequestInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\TemplateInterface;
use SP\Util\Link;

/**
 * Class AccountHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class AccountHelper extends AccountHelperBase
{
    use ItemTrait;

    private AccountServiceInterface        $accountService;
    private AccountHistoryServiceInterface $accountHistoryService;
    private PublicLinkServiceInterface     $publicLinkService;
    private ItemPresetServiceInterface     $itemPresetService;
    private MasterPassServiceInterface     $masterPassService;
    private AccountAclServiceInterface     $accountAclService;
    private CategoryServiceInterface       $categoryService;
    private ClientServiceInterface         $clientService;
    private CustomFieldServiceInterface    $customFieldService;
    private ?AccountAcl                    $accountAcl = null;
    private ?int                           $accountId  = null;
    private UserServiceInterface           $userService;
    private UserGroupServiceInterface      $userGroupService;
    private TagServiceInterface            $tagService;

    public function __construct(
        Application $application,
        TemplateInterface $template,
        RequestInterface $request,
        Acl $acl,
        AccountServiceInterface $accountService,
        AccountHistoryServiceInterface $accountHistoryService,
        PublicLinkServiceInterface $publicLinkService,
        ItemPresetServiceInterface $itemPresetService,
        MasterPassServiceInterface $masterPassService,
        AccountActionsHelper $accountActionsHelper,
        AccountAclServiceInterface $accountAclService,
        CategoryServiceInterface $categoryService,
        ClientServiceInterface $clientService,
        CustomFieldServiceInterface $customFieldService,
        UserServiceInterface $userService,
        UserGroupServiceInterface $userGroupService,
        TagServiceInterface $tagService
    ) {
        parent::__construct($application, $template, $request, $acl, $accountActionsHelper, $masterPassService);

        $this->accountService = $accountService;
        $this->accountHistoryService = $accountHistoryService;
        $this->publicLinkService = $publicLinkService;
        $this->itemPresetService = $itemPresetService;
        $this->accountAclService = $accountAclService;
        $this->categoryService = $categoryService;
        $this->clientService = $clientService;
        $this->customFieldService = $customFieldService;
        $this->userService = $userService;
        $this->userGroupService = $userGroupService;
        $this->tagService = $tagService;

        $this->view->assign('changesHash');
        $this->view->assign('chkUserEdit', false);
        $this->view->assign('chkGroupEdit', false);
    }


    /**
     * Sets account's view variables
     *
     * @param  AccountEnrichedDto  $accountDetailsResponse
     * @param  int  $actionId
     *
     * @throws \SP\Core\Acl\AccountPermissionException
     * @throws \SP\Core\Acl\UnauthorizedPageException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Domain\User\Services\UpdatedMasterPassException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function setViewForAccount(
        AccountEnrichedDto $accountDetailsResponse,
        int $actionId
    ): void {
        $this->accountId = $accountDetailsResponse->getAccountVData()->getId();
        $this->actionId = $actionId;

        $this->checkActionAccess();

        $this->accountAcl = $this->checkAccess($accountDetailsResponse);

        $accountData = $accountDetailsResponse->getAccountVData();

        $accountActionsDto = new AccountActionsDto($this->accountId, null, $accountData->getParentId());

        $selectUsers = SelectItemAdapter::factory($this->userService->getAllBasic());
        $selectUserGroups = SelectItemAdapter::factory($this->userGroupService->getAllBasic());
        $selectTags = SelectItemAdapter::factory($this->tagService->getAllBasic());

        $usersView = SelectItemAdapter::getIdFromArrayOfObjects(
            array_filter(
                $accountDetailsResponse->getUsers(),
                static fn($value) => (int)$value->isEdit === 0
            )
        );

        $usersEdit = SelectItemAdapter::getIdFromArrayOfObjects(
            array_filter(
                $accountDetailsResponse->getUsers(),
                static fn($value) => (int)$value->isEdit === 1
            )
        );

        $userGroupsView = SelectItemAdapter::getIdFromArrayOfObjects(
            array_filter(
                $accountDetailsResponse->getUserGroups(),
                static fn($value) => (int)$value->isEdit === 0
            )
        );

        $userGroupsEdit = SelectItemAdapter::getIdFromArrayOfObjects(
            array_filter(
                $accountDetailsResponse->getUserGroups(),
                static fn($value) => (int)$value->isEdit === 1
            )
        );

        $this->view->assign('otherUsersView', $selectUsers->getItemsFromModelSelected($usersView));
        $this->view->assign('otherUsersEdit', $selectUsers->getItemsFromModelSelected($usersEdit));
        $this->view->assign('otherUserGroupsView', $selectUserGroups->getItemsFromModelSelected($userGroupsView));
        $this->view->assign('otherUserGroupsEdit', $selectUserGroups->getItemsFromModelSelected($userGroupsEdit));
        $this->view->assign('users', $selectUsers->getItemsFromModelSelected([$accountData->getUserId()]));
        $this->view->assign(
            'userGroups',
            $selectUserGroups->getItemsFromModelSelected([$accountData->getUserGroupId()])
        );
        $this->view->assign(
            'tags',
            $selectTags->getItemsFromModelSelected(
                SelectItemAdapter::getIdFromArrayOfObjects($accountDetailsResponse->getTags())
            )
        );
        $this->view->assign(
            'historyData',
            SelectItemAdapter::factory($this->accountHistoryService->getHistoryForAccount($this->accountId))
                ->getItemsFromArray()
        );
        $this->view->assign('isModified', strtotime($accountData->getDateEdit()) !== false);
        $this->view->assign('maxFileSize', round($this->configData->getFilesAllowedSize() / 1024, 1));
        $this->view->assign('filesAllowedExts', implode(',', $this->configData->getFilesAllowedExts()));

        if ($this->configData->isPublinksEnabled() && $this->accountAcl->isShowLink()) {
            try {
                $publicLinkData = $this->publicLinkService->getHashForItem($this->accountId);
                $accountActionsDto->setPublicLinkId($publicLinkData->getId());
                $accountActionsDto->setPublicLinkCreatorId($publicLinkData->getUserId());

                $baseUrl = ($this->configData->getApplicationUrl() ?: BootstrapBase::$WEBURI).BootstrapBase::$SUBURI;

                $this->view->assign(
                    'publicLinkUrl',
                    PublicLinkService::getLinkForHash(
                        $baseUrl,
                        $publicLinkData->getHash()
                    )
                );
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
        $userProfileData = $this->context->getUserProfile() ?? new ProfileData();

        $this->view->assign(
            'allowPrivate',
            ($userProfileData->isAccPrivate()
             && $accountData->getUserId() === $userData->getId())
            || $userData->getIsAdminApp()
        );

        $this->view->assign(
            'allowPrivateGroup',
            ($userProfileData->isAccPrivateGroup()
             && $accountData->getUserGroupId() === $userData->getUserGroupId())
            || $userData->getIsAdminApp()
        );

        $this->view->assign(
            'accountPassDate',
            date('Y-m-d H:i:s', $accountData->getPassDate())
        );
        $this->view->assign(
            'accountPassDateChange',
            $accountData->getPassDateChange() > 0
                ? gmdate('Y-m-d', $accountData->getPassDateChange())
                : 0
        );
        $this->view->assign('linkedAccounts', $this->accountService->getLinked($this->accountId));

        $this->view->assign('accountId', $accountData->getId());
        $this->view->assign('accountData', $accountData);
        $this->view->assign('gotData', true);

        $this->view->assign(
            'accountActions',
            $this->accountActionsHelper->getActionsForAccount(
                $this->accountAcl,
                $accountActionsDto
            )
        );
        $this->view->assign(
            'accountActionsMenu',
            $this->accountActionsHelper->getActionsGrouppedForAccount(
                $this->accountAcl,
                $accountActionsDto
            )
        );

        $this->setViewCommon();
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @param  AccountEnrichedDto  $accountDetailsResponse
     *
     * @return AccountAcl
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function checkAccess(AccountEnrichedDto $accountDetailsResponse): AccountAcl
    {
        $accountAcl = $this->accountAclService->getAcl(
            $this->actionId,
            AccountAclDto::makeFromAccount($accountDetailsResponse)
        );

        if ($accountAcl->checkAccountAccess($this->actionId) === false) {
            throw new AccountPermissionException(SPException::INFO);
        }

        return $accountAcl;
    }

    /**
     * Sets account's view common data
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    protected function setViewCommon(): void
    {
        $this->view->assign('isView', $this->isView);

        $this->view->assign('accountIsHistory', false);

        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(
                ActionsInterface::ACCOUNT,
                $this->accountId,
                $this->customFieldService
            )
        );

        $this->view->assign(
            'categories',
            SelectItemAdapter::factory($this->categoryService->getAllBasic())->getItemsFromModel()
        );
        $this->view->assign(
            'clients',
            SelectItemAdapter::factory($this->clientService->getAllForUser())->getItemsFromModel()
        );
        $this->view->assign('mailRequestEnabled', $this->configData->isMailRequestsEnabled());
        $this->view->assign('passToImageEnabled', $this->configData->isAccountPassToImage());
        $this->view->assign('otherAccounts', $this->accountService->getForUser($this->accountId));
        $this->view->assign(
            'addClientEnabled',
            !$this->isView && $this->acl->checkUserAccess(ActionsInterface::CLIENT)
        );
        $this->view->assign('addClientRoute', Acl::getActionRoute(ActionsInterface::CLIENT_CREATE));
        $this->view->assign(
            'addCategoryEnabled',
            !$this->isView && $this->acl->checkUserAccess(ActionsInterface::CATEGORY)
        );
        $this->view->assign('addCategoryRoute', Acl::getActionRoute(ActionsInterface::CATEGORY_CREATE));
        $this->view->assign(
            'addTagEnabled',
            !$this->isView
            && $this->acl->checkUserAccess(ActionsInterface::TAG)
        );
        $this->view->assign('addTagRoute', Acl::getActionRoute(ActionsInterface::TAG_CREATE));
        $this->view->assign('fileListRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_LIST));
        $this->view->assign('fileUploadRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_UPLOAD));
        $this->view->assign('disabled', $this->isView ? 'disabled' : '');
        $this->view->assign('readonly', $this->isView ? 'readonly' : '');
        $this->view->assign('showViewCustomPass', $this->accountAcl->isShowViewPass());
        $this->view->assign('accountAcl', $this->accountAcl);

        if ($this->accountId) {
            $this->view->assign(
                'deepLink',
                Link::getDeepLink($this->accountId, $this->actionId, $this->configData)
            );
        }
    }

    /**
     * Sets account's view for a blank form
     *
     * @param  int  $actionId
     *
     * @return void
     * @throws \SP\Core\Acl\UnauthorizedPageException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Domain\User\Services\UpdatedMasterPassException
     */
    public function setViewForBlank(int $actionId): void
    {
        $this->actionId = $actionId;
        $this->accountAcl = new AccountAcl($actionId);

        $this->checkActionAccess();

        $userProfileData = $this->context->getUserProfile() ?? new ProfileData();
        $userData = $this->context->getUserData();

        $this->accountAcl->setShowPermission(
            $userData->getIsAdminApp()
            || $userData->getIsAdminAcc()
            || $userProfileData->isAccPermission()
        );

        $accountPrivate = new AccountPrivate();

        if ($itemPresetPrivate =
            $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)) {
            $accountPrivate = $itemPresetPrivate->hydrate(AccountPrivate::class) ?: $accountPrivate;
        }

        $accountPermission = new AccountPermission();

        if ($itemPresetPermission =
            $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION)) {
            $accountPermission = $itemPresetPermission->hydrate(AccountPermission::class) ?: $accountPermission;
        }

        $selectUsers = SelectItemAdapter::factory($this->userService->getAllBasic());
        $selectUserGroups = SelectItemAdapter::factory($this->userGroupService->getAllBasic());
        $selectTags = SelectItemAdapter::factory($this->tagService->getAllBasic());

        $this->view->assign('accountPassDateChange', date('Y-m-d', time() + 7776000));
        $this->view->assign(
            'otherUsersView',
            $selectUsers->getItemsFromModelSelected($accountPermission->getUsersView())
        );
        $this->view->assign(
            'otherUsersEdit',
            $selectUsers->getItemsFromModelSelected($accountPermission->getUsersEdit())
        );
        $this->view->assign(
            'otherUserGroupsView',
            $selectUserGroups->getItemsFromModelSelected($accountPermission->getUserGroupsView())
        );
        $this->view->assign(
            'otherUserGroupsEdit',
            $selectUserGroups->getItemsFromModelSelected($accountPermission->getUserGroupsEdit())
        );
        $this->view->assign('users', $selectUsers->getItemsFromModel());
        $this->view->assign('userGroups', $selectUserGroups->getItemsFromModel());
        $this->view->assign('tags', $selectTags->getItemsFromModel());
        $this->view->assign('allowPrivate', $userProfileData->isAccPrivate() || $userData->getIsAdminApp());
        $this->view->assign('allowPrivateGroup', $userProfileData->isAccPrivateGroup() || $userData->getIsAdminApp());
        $this->view->assign('privateUserCheck', $accountPrivate->isPrivateUser());
        $this->view->assign('privateUserGroupCheck', $accountPrivate->isPrivateGroup());
        $this->view->assign('accountId', 0);
        $this->view->assign('gotData', false);
        $this->view->assign(
            'accountActions',
            $this->accountActionsHelper->getActionsForAccount(
                $this->accountAcl,
                new AccountActionsDto($this->accountId)
            )
        );

        $this->setViewCommon();
    }
}
