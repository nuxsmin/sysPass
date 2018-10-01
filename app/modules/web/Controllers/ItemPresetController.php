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

use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ItemPresetData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\ItemPresetGrid;
use SP\Modules\Web\Controllers\Helpers\ItemPresetHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\ItemsPresetForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\ItemPreset\ItemPresetInterface;
use SP\Services\ItemPreset\ItemPresetService;
use SP\Util\Filter;

/**
 * Class AccountDefaultPermissionController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ItemPresetController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var ItemPresetService
     */
    protected $itemPresetService;

    /**
     * View action
     *
     * @param $id
     *
     * @return bool
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::ITEMPRESET_VIEW)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Ver Valor'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.itemPreset', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying permissions' data
     *
     * @param int    $id
     * @param string $type
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    protected function setViewData(int $id = null, string $type = null)
    {
        $this->view->addTemplate('item_preset', 'itemshow');

        $itemPresetData = $id ? $this->itemPresetService->getById($id) : new ItemPresetData();

        $itemPresetHelper = $this->dic->get(ItemPresetHelper::class);
        $itemPresetHelper->setCommon($itemPresetData);

        if ($itemPresetData->getType() === null) {
            $itemPresetData->setType($type);
        }

        switch ($itemPresetData->getType()) {
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION:
                $itemPresetHelper->makeAccountPermissionView($itemPresetData);
                break;
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE:
                $itemPresetHelper->makeAccountPrivateView($itemPresetData);
                break;
            case ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT:
                $itemPresetHelper->makeSessionTimeoutView($itemPresetData);
                break;
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD:
                $itemPresetHelper->makeAccountPasswordView($itemPresetData);
                break;
        }

        $this->view->assign('preset', $itemPresetData);
        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }
    }

    /**
     * Search action
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(Acl::ITEMPRESET_SEARCH)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getSearchGrid()
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        $grid = $this->dic->get(ItemPresetGrid::class);

        return $grid->updatePager(
            $grid->getGrid($this->itemPresetService->search($itemSearchData)),
            $itemSearchData
        );
    }

    /**
     * Create action
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(Acl::ITEMPRESET_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $args = func_get_args();
        $type = null;

        if (count($args) > 0) {
            $type = Filter::getString($args[0]);
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Valor'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'itemPreset/saveCreate');

        try {
            $this->setViewData(null, $type);

            $this->eventDispatcher->notifyEvent('show.itemPreset.create', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Edit action
     *
     * @param $id
     *
     * @return bool
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::ITEMPRESET_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Editar Valor'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'itemPreset/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.itemPreset.edit', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Delete action
     *
     * @param $id
     *
     * @return bool
     */
    public function deleteAction($id = null)
    {
        if (!$this->acl->checkUserAccess(Acl::ITEMPRESET_DELETE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            if ($id === null) {
                $this->itemPresetService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent('delete.itemPreset',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Valores eliminados')))
                );

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Valores eliminados'));
            }

            $this->itemPresetService->delete($id);

            $this->eventDispatcher->notifyEvent('delete.itemPreset',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Valor eliminado'))
                        ->addDetail(__u('ID'), $id))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Valor eliminado'));
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        if (!$this->acl->checkUserAccess(Acl::ITEMPRESET_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new ItemsPresetForm($this->dic);
            $form->validate(Acl::ITEMPRESET_CREATE);

            $itemData = $form->getItemData();

            $id = $this->itemPresetService->create($itemData);

            $this->eventDispatcher->notifyEvent('create.itemPreset',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Valor creado'))
                        ->addDetail(__u('Tipo'), $itemData->getItemPresetData()->getType())
                        ->addDetail(__u('ID'), $id))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Valor creado'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     *
     * @return bool
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::ITEMPRESET_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new ItemsPresetForm($this->dic, $id);
            $form->validate(Acl::ITEMPRESET_EDIT);

            $itemData = $form->getItemData();

            $this->itemPresetService->update($itemData);

            $this->eventDispatcher->notifyEvent('edit.itemPreset',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Valor actualizado'))
                        ->addDetail(__u('Tipo'), $itemData->getItemPresetData()->getType())
                        ->addDetail(__u('ID'), $id))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Valor actualizado'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Initialize class
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Services\Auth\AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->itemPresetService = $this->dic->get(ItemPresetService::class);
    }
}