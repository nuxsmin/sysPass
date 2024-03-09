<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapBase;
use SP\DataModel\ItemPreset\AccountPermission as AccountPermissionPreset;
use SP\DataModel\ItemPreset\AccountPrivate;
use SP\DataModel\ProfileData;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Account\Services\PublicLink;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Http\RequestInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Domain\Tag\Ports\TagServiceInterface;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Domain\User\Services\UpdatedMasterPassException;
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

    private AccountService            $accountService;
    private AccountHistoryService     $accountHistoryService;
    private PublicLinkService         $publicLinkService;
    private ItemPresetService         $itemPresetService;
    private MasterPassService         $masterPassService;
    private AccountAclService         $accountAclService;
    private CategoryService           $categoryService;
    private ClientService             $clientService;
    private CustomFieldDataService    $customFieldService;
    private ?AccountPermission        $accountAcl = null;
    private ?int                      $accountId  = null;
    private UserServiceInterface      $userService;
    private UserGroupServiceInterface $userGroupService;
    private TagServiceInterface       $tagService;

    public function __construct(
        Application               $application,
        TemplateInterface         $template,
        RequestInterface          $request,
        AclInterface              $acl,
        AccountService            $accountService,
        AccountHistoryService     $accountHistoryService,
        PublicLinkService         $publicLinkService,
        ItemPresetService         $itemPresetService,
        MasterPassService         $masterPassService,
        AccountActionsHelper      $accountActionsHelper,
        AccountAclService         $accountAclService,
        CategoryService           $categoryService,
        ClientService             $clientService,
        CustomFieldDataService    $customFieldService,
        UserServiceInterface      $userService,
        UserGroupServiceInterface $userGroupService,
        TagServiceInterface       $tagService
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
     * @param AccountEnrichedDto $accountDetailsResponse
     * @param int $actionId
     *
     * @throws AccountPermissionException
     * @throws UnauthorizedPageException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     * @throws UpdatedMasterPassException
     * @throws NoSuchItemException
     */
    public function setViewForAccount(
        AccountEnrichedDto $accountDetailsResponse,
        int $actionId
    ): void {
        $this->accountId = $accountDetailsResponse->getAccountDataView()->getId();
        $this->actionId = $actionId;

        $this->checkActionAccess();

        $this->accountAcl = $this->checkAccess($accountDetailsResponse);

        $accountData = $accountDetailsResponse->getAccountDataView();

        $accountActionsDto = new AccountActionsDto($this->accountId, null, $accountData->getParentId());

        $selectUsers = SelectItemAdapter::factory($this->userService->getAll());
        $selectUserGroups = SelectItemAdapter::factory($this->userGroupService->getAll());
        $selectTags = SelectItemAdapter::factory($this->tagService->getAll());

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
            SelectItemAdapter::factory(
                AccountHistoryHelper::mapHistoryForDateSelect(
                    $this->accountHistoryService->getHistoryForAccount($this->accountId)
                )
            )
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

                $baseUrl = ($this->configData->getApplicationUrl() ?: BootstrapBase::$WEBURI) . BootstrapBase::$SUBURI;

                $this->view->assign(
                    'publicLinkUrl',
                    PublicLink::getLinkForHash(
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
     * @param AccountEnrichedDto $accountDetailsResponse
     *
     * @return AccountPermission
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function checkAccess(AccountEnrichedDto $accountDetailsResponse): AccountPermission
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
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    protected function setViewCommon(): void
    {
        $this->view->assign('isView', $this->isView);

        $this->view->assign('accountIsHistory', false);

        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(
                AclActionsInterface::ACCOUNT,
                $this->accountId,
                $this->customFieldService
            )
        );

        $this->view->assign(
            'categories',
            SelectItemAdapter::factory($this->categoryService->getAll())->getItemsFromModel()
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
            !$this->isView && $this->acl->checkUserAccess(AclActionsInterface::CLIENT)
        );
        $this->view->assign('addClientRoute', Acl::getActionRoute(AclActionsInterface::CLIENT_CREATE));
        $this->view->assign(
            'addCategoryEnabled',
            !$this->isView && $this->acl->checkUserAccess(AclActionsInterface::CATEGORY)
        );
        $this->view->assign('addCategoryRoute', Acl::getActionRoute(AclActionsInterface::CATEGORY_CREATE));
        $this->view->assign(
            'addTagEnabled',
            !$this->isView
            && $this->acl->checkUserAccess(AclActionsInterface::TAG)
        );
        $this->view->assign('addTagRoute', Acl::getActionRoute(AclActionsInterface::TAG_CREATE));
        $this->view->assign('fileListRoute', Acl::getActionRoute(AclActionsInterface::ACCOUNT_FILE_LIST));
        $this->view->assign('fileUploadRoute', Acl::getActionRoute(AclActionsInterface::ACCOUNT_FILE_UPLOAD));
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
     * @param int $actionId
     *
     * @return void
     * @throws UnauthorizedPageException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws SPException
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws UpdatedMasterPassException
     */
    public function setViewForBlank(int $actionId): void
    {
        $this->actionId = $actionId;
        $this->accountAcl = new AccountPermission($actionId);

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
            $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE)
        ) {
            $accountPrivate = $itemPresetPrivate->hydrate(AccountPrivate::class) ?? $accountPrivate;
        }

        $accountPermission = new AccountPermissionPreset();

        if ($itemPresetPermission =
            $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION)
        ) {
            $accountPermission = $itemPresetPermission->hydrate(AccountPermissionPreset::class) ?? $accountPermission;
        }

        $selectUsers = SelectItemAdapter::factory($this->userService->getAll());
        $selectUserGroups = SelectItemAdapter::factory($this->userGroupService->getAll());
        $selectTags = SelectItemAdapter::factory($this->tagService->getAll());

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
