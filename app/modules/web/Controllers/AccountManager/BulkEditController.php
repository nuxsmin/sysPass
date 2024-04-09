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

namespace SP\Modules\Web\Controllers\AccountManager;

use Exception;
use JsonException;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountSearchService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Tag\Ports\TagService;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserService;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountGrid;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\Components\SelectItemAdapter;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class BulkEditController extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    private AccountService        $accountService;
    private AccountSearchService  $accountSearchService;
    private AccountHistoryService $accountHistoryService;
    private AccountGrid            $accountGrid;
    private CustomFieldDataService $customFieldService;
    private CategoryService        $categoryService;
    private ClientService        $clientService;
    private TagService  $tagService;
    private UserService $userService;
    private UserGroupService $userGroupService;

    public function __construct(
        Application         $application,
        WebControllerHelper $webControllerHelper,
        CategoryService     $categoryService,
        ClientService       $clientService,
        TagService          $tagService,
        UserService         $userService,
        UserGroupService    $userGroupService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->categoryService = $categoryService;
        $this->clientService = $clientService;
        $this->tagService = $tagService;
        $this->userService = $userService;
        $this->userGroupService = $userGroupService;

        $this->checkLoggedIn();
    }

    /**
     * bulkEditAction
     *
     * @return bool
     * @throws JsonException
     */
    public function bulkEditAction(): bool
    {
        try {
            if (!$this->acl->checkUserAccess(AclActionsInterface::ACCOUNTMGR)) {
                return $this->returnJsonResponse(
                    JsonMessage::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('Bulk Update'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'accountManager/saveBulkEdit');
            $this->view->assign('itemsId', $this->getItemsIdFromRequest($this->request));

            $this->setViewData();

            $this->eventDispatcher->notify(
                'show.account.bulkEdit',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data
     */
    protected function setViewData(): void
    {
        $this->view->addTemplate('account_bulkedit', 'itemshow');

        $this->view->assign('nextAction', Acl::getActionRoute(AclActionsInterface::ITEMS_MANAGE));

        $clients = SelectItemAdapter::factory($this->clientService->getAll())->getItemsFromModel();
        $categories = SelectItemAdapter::factory($this->categoryService->getAll())->getItemsFromModel();
        $tags = SelectItemAdapter::factory($this->tagService->getAll())->getItemsFromModel();
        $users = SelectItemAdapter::factory($this->userService->getAll())->getItemsFromModel();
        $userGroups = SelectItemAdapter::factory($this->userGroupService->getAll())->getItemsFromModel();

        $this->view->assign('users', $users);
        $this->view->assign('userGroups', $userGroups);

        $this->view->assign('clients', $clients);
        $this->view->assign('categories', $categories);
        $this->view->assign('tags', $tags);

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }
}
