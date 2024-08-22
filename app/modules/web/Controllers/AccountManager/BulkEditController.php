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
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\Tag\Ports\TagService;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\Components\SelectItemAdapter;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class BulkEditController extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                       $application,
        WebControllerHelper               $webControllerHelper,
        private readonly CategoryService  $categoryService,
        private readonly ClientService    $clientService,
        private readonly TagService       $tagService,
        private readonly UserService      $userService,
        private readonly UserGroupService $userGroupService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * bulkEditAction
     *
     * @return bool
     * @throws SPException
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

            $this->eventDispatcher->notify('show.account.bulkEdit', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function setViewData(): void
    {
        $this->view->addTemplate('account_bulkedit', 'itemshow');

        $this->view->assign('nextAction', $this->acl->getRouteFor(AclActionsInterface::ITEMS_MANAGE));

        $this->view->assign('users', SelectItemAdapter::factory($this->userService->getAll())->getItemsFromModel());
        $this->view->assign(
            'userGroups',
            SelectItemAdapter::factory($this->userGroupService->getAll())->getItemsFromModel()
        );
        $this->view->assign('clients', SelectItemAdapter::factory($this->clientService->getAll())->getItemsFromModel());
        $this->view->assign(
            'categories',
            SelectItemAdapter::factory($this->categoryService->getAll())->getItemsFromModel()
        );
        $this->view->assign('tags', SelectItemAdapter::factory($this->tagService->getAll())->getItemsFromModel());
        $this->view->assign('disabled', '');
        $this->view->assign('readonly', '');
    }
}
