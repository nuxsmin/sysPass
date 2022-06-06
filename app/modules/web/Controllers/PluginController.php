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
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Plugin\Services\PluginDataService;
use SP\Domain\Plugin\Services\PluginService;
use SP\Html\DataGrid\DataGridInterface;
use SP\Http\JsonResponse;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Plugin\Repositories\PluginModel;
use SP\Modules\Web\Controllers\Helpers\Grid\PluginGrid;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Plugin\PluginManager;

/**
 * Class PluginController
 *
 * @package web\Controllers
 */
final class PluginController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    protected ?PluginService $pluginService = null;
    protected ?PluginDataService $pluginDataService = null;

    /**
     * indexAction
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function indexAction(): void
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PLUGIN)) {
            return;
        }

        $this->view->addTemplate('index');

        $this->view->assign('data', $this->getSearchGrid());

        $this->view();
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

        $pluginGrid = $this->dic->get(PluginGrid::class);

        return $pluginGrid->updatePager(
            $pluginGrid->getGrid($this->pluginService->search($itemSearchData)),
            $itemSearchData
        );
    }

    /**
     * Search action
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PLUGIN_SEARCH)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('You don\'t have permission to do this operation')
            );
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * View action
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function viewAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::PLUGIN_VIEW)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('View Plugin'));
            $this->view->assign('isView', true);

            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent(
                'show.plugin',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
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
     * Sets view data for displaying items's data
     *
     * @param int|null $pluginId
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    protected function setViewData(?int $pluginId = null): void
    {
        $this->view->addTemplate('plugin');

        $pluginData = $pluginId
            ? $this->pluginService->getById($pluginId)
            : new PluginModel();
        $pluginInfo = $this->dic->get(PluginManager::class)
            ->getPlugin($pluginData->getName());

        $this->view->assign('plugin', $pluginData);
        $this->view->assign('pluginInfo', $pluginInfo);

        $this->view->assign(
            'nextAction',
            Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE)
        );

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }

    /**
     * enableAction
     *
     * @param int $id
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function enableAction(int $id): bool
    {
        try {
            $this->pluginService->toggleEnabled($id, true);

            $this->eventDispatcher->notifyEvent(
                'edit.plugin.enable',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Plugin enabled'))
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Plugin enabled')
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
     * disableAction
     *
     * @param int $id
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function disableAction(int $id): bool
    {
        try {
            $this->pluginService->toggleEnabled($id, false);

            $this->eventDispatcher->notifyEvent(
                'edit.plugin.disable',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Plugin disabled'))
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Plugin disabled')
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
     * resetAction
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function resetAction(int $id): bool
    {
        try {
            $pluginModel = $this->pluginService->getById($id);
            $this->pluginDataService->delete($pluginModel->getName());

            $this->eventDispatcher->notifyEvent(
                'edit.plugin.reset',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Plugin reset'))
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Plugin reset')
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
     * resetAction
     *
     * @param int|null $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::PLUGIN_DELETE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            if ($id === null) {
                $this->pluginService
                    ->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent(
                    'delete.plugin.selection',
                    new Event($this)
                );

                return $this->returnJsonResponse(
                    JsonResponse::JSON_SUCCESS,
                    __u('Plugins deleted')
                );
            }

            $this->pluginService->delete($id);

            $this->eventDispatcher->notifyEvent(
                'delete.plugin',
                new Event($this)
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Plugin deleted')
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

        $this->pluginService = $this->dic->get(PluginService::class);
        $this->pluginDataService = $this->dic->get(PluginDataService::class);
    }
}