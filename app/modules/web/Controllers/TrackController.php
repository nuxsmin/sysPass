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
use SP\Core\Acl\UnauthorizedActionException;
use SP\Core\Events\Event;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\TrackGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Auth\AuthException;
use SP\Services\Track\TrackService;

/**
 * Class TrackController
 *
 * @package SP\Modules\Web\Controllers
 */
final class TrackController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    /**
     * @var TrackService
     */
    protected $trackService;

    /**
     * Search action
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws UnauthorizedActionException
     * @throws SPException
     */
    public function searchAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if (!$this->acl->checkUserAccess(Acl::TRACK_SEARCH)) {
            throw new UnauthorizedActionException(UnauthorizedActionException::ERROR);
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
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

        $itemsGridHelper = $this->dic->get(TrackGrid::class);

        return $itemsGridHelper->updatePager($itemsGridHelper->getGrid($this->trackService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Unlocks a track
     *
     * @param int $id
     *
     * @return bool
     */
    public function unlockAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::TRACK_UNLOCK)) {
                throw new UnauthorizedActionException(UnauthorizedActionException::ERROR);
            }

            $this->trackService->unlock($id);

            $this->eventDispatcher->notifyEvent('unlock.track', new Event($this));

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Track unlocked'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Clears tracks
     *
     * @return bool
     */
    public function clearAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::TRACK_CLEAR)) {
                throw new UnauthorizedActionException(UnauthorizedActionException::ERROR);
            }

            $this->trackService->clear();

            $this->eventDispatcher->notifyEvent('clear.track', new Event($this));

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Tracks cleared out'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

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

        $this->trackService = $this->dic->get(TrackService::class);
    }
}