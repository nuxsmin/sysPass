<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\PluginData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
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
     * indexAction
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function indexAction()
    {
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getSearchGrid()
    {
        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        return $itemsGridHelper->updatePager($itemsGridHelper->getPluginsGrid($this->pluginService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Search action
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(Acl::PLUGIN_SEARCH)) {
            return;
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * View action
     *
     * @param $id
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::PLUGIN_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Plugin'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.plugin', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying items's data
     *
     * @param $pluginId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    protected function setViewData($pluginId = null)
    {
        $this->view->addTemplate('plugin');

        $pluginData = $pluginId ? $this->pluginService->getById($pluginId) : new PluginData();

        $this->view->assign('plugin', $pluginData);

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ITEMS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }
    }

    /**
     * enableAction
     *
     * @param $id
     */
    public function enableAction($id)
    {
        try {
            $this->pluginService->toggleEnabled($id, 1);

            $this->eventDispatcher->notifyEvent('edit.plugin.enable',
                new Event($this,
                    EventMessage::factory()->addDescription(__u('Plugin habilitado')))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Plugin habilitado'));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * disableAction
     *
     * @param $id
     */
    public function disableAction($id)
    {
        try {
            $this->pluginService->toggleEnabled($id, 0);

            $this->eventDispatcher->notifyEvent('edit.plugin.disable',
                new Event($this,
                    EventMessage::factory()->addDescription(__u('Plugin deshabilitado')))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Plugin deshabilitado'));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * resetAction
     *
     * @param $id
     */
    public function resetAction($id)
    {
        try {
            $this->pluginService->resetById($id);

            $this->eventDispatcher->notifyEvent('edit.plugin.reset',
                new Event($this,
                    EventMessage::factory()->addDescription(__u('Plugin restablecido')))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Plugin restablecido'));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \SP\Services\Auth\AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->pluginService = $this->dic->get(PluginService::class);
    }
}