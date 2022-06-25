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

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\EventlogGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Auth\AuthException;
use SP\Services\EventLog\EventlogService;

/**
 * Class EventlogController
 *
 * @package SP\Modules\Web\Controllers
 */
final class EventlogController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    /**
     * @var EventlogService
     */
    protected $eventLogService;

    /**
     * indexAction
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function indexAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if (!$this->acl->checkUserAccess(Acl::EVENTLOG)) {
            return;
        }

        $this->view->addTemplate('index');

        $this->view->assign('data', $this->getSearchGrid());

        $this->view();
    }

    /**
     * getSearchGrid
     *
     * @return $this
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getSearchGrid()
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        $eventlogGrid = $this->dic->get(EventlogGrid::class);

        return $eventlogGrid->updatePager($eventlogGrid->getGrid($this->eventLogService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * searchAction
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function searchAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if (!$this->acl->checkUserAccess(Acl::EVENTLOG_SEARCH)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
        }

        $this->view->addTemplate('datagrid-table-simple', 'grid');
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * clearAction
     */
    public function clearAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $this->eventLogService->clear();

            $this->eventDispatcher->notifyEvent('clear.eventlog',
                new Event($this, EventMessage::factory()->addDescription(__u('Event log cleared')))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Event log cleared'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->eventLogService = $this->dic->get(EventlogService::class);
    }
}