<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedActionException;
use SP\Core\Events\Event;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Security\Services\TrackService;
use SP\Html\DataGrid\DataGridInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\TrackGrid;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;

/**
 * Class TrackController
 *
 * @package SP\Modules\Web\Controllers
 */
final class TrackController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    protected ?TrackService $trackService = null;

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
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::TRACK_SEARCH)) {
            throw new UnauthorizedActionException(SPException::ERROR);
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign(
            'index',
            $this->request->analyzeInt('activetab', 0)
        );
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        $itemsGridHelper = $this->dic->get(TrackGrid::class);

        return $itemsGridHelper->updatePager(
            $itemsGridHelper->getGrid($this->trackService->search($itemSearchData)),
            $itemSearchData
        );
    }

    /**
     * Unlocks a track
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function unlockAction(int $id): ?bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::TRACK_UNLOCK)) {
                throw new UnauthorizedActionException(SPException::ERROR);
            }

            $this->trackService->unlock($id);

            $this->eventDispatcher->notifyEvent(
                'unlock.track',
                new Event($this)
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Track unlocked')
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Clears tracks
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function clearAction(): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::TRACK_CLEAR)) {
                throw new UnauthorizedActionException(SPException::ERROR);
            }

            $this->trackService->clear();

            $this->eventDispatcher->notifyEvent(
                'clear.track',
                new Event($this)
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Tracks cleared out')
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checkLoggedIn();

        $this->trackService = $this->dic->get(TrackService::class);
    }
}