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
use SP\Modules\Web\Controllers\Helpers\Grid\PluginGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Plugin\PluginManager;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Plugin\PluginModel;
use SP\Services\Auth\AuthException;
use SP\Services\Plugin\PluginDataService;
use SP\Services\Plugin\PluginService;

/**
 * Class PluginController
 *
 * @package web\Controllers
 */
final class PluginController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    /**
     * @var PluginService
     */
    protected $pluginService;
    /**
     * @var PluginDataService
     */
    protected $pluginDataService;

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

        if (!$this->acl->checkUserAccess(Acl::PLUGIN)) {
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

        $pluginGrid = $this->dic->get(PluginGrid::class);

        return $pluginGrid->updatePager($pluginGrid->getGrid($this->pluginService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Search action
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

        if (!$this->acl->checkUserAccess(Acl::PLUGIN_SEARCH)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * View action
     *
     * @param $id
     *
     * @return bool
     */
    public function viewAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::PLUGIN_VIEW)) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
            }

            $this->view->assign('header', __('View Plugin'));
            $this->view->assign('isView', true);

            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.plugin', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying items's data
     *
     * @param $pluginId
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    protected function setViewData($pluginId = null)
    {
        $this->view->addTemplate('plugin');

        $pluginData = $pluginId ? $this->pluginService->getById($pluginId) : new PluginModel();
        $pluginInfo = $this->dic->get(PluginManager::class)->getPlugin($pluginData->getName());

        $this->view->assign('plugin', $pluginData);
        $this->view->assign('pluginInfo', $pluginInfo);

        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ITEMS_MANAGE));

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
     * @param $id
     *
     * @return bool
     */
    public function enableAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $this->pluginService->toggleEnabled($id, 1);

            $this->eventDispatcher->notifyEvent('edit.plugin.enable',
                new Event($this,
                    EventMessage::factory()->addDescription(__u('Plugin enabled')))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Plugin enabled'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * disableAction
     *
     * @param $id
     *
     * @return bool
     */
    public function disableAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $this->pluginService->toggleEnabled($id, 0);

            $this->eventDispatcher->notifyEvent('edit.plugin.disable',
                new Event($this,
                    EventMessage::factory()->addDescription(__u('Plugin disabled')))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Plugin disabled'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * resetAction
     *
     * @param $id
     *
     * @return bool
     */
    public function resetAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $pluginModel = $this->pluginService->getById($id);
            $this->pluginDataService->delete($pluginModel->getName());

            $this->eventDispatcher->notifyEvent('edit.plugin.reset',
                new Event($this,
                    EventMessage::factory()->addDescription(__u('Plugin reset')))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Plugin reset'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * resetAction
     *
     * @param $id
     *
     * @return bool
     */
    public function deleteAction($id)
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if (!$this->acl->checkUserAccess(Acl::PLUGIN_DELETE)) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('You don\'t have permission to do this operation'));
            }

            if ($id === null) {
                $this->pluginService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent('delete.plugin.selection', new Event($this));

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Plugins deleted'));
            } else {
                $this->pluginService->delete($id);

                $this->eventDispatcher->notifyEvent('delete.plugin', new Event($this));

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Plugin deleted'));
            }
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

        $this->pluginService = $this->dic->get(PluginService::class);
        $this->pluginDataService = $this->dic->get(PluginDataService::class);
    }
}