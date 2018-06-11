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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\CustomFieldDefinitionData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\CustomFieldDefForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\CustomField\CustomFieldDefService;
use SP\Services\CustomField\CustomFieldTypeService;

/**
 * Class CustomFieldController
 *
 * @package SP\Modules\Web\Controllers
 */
class CustomFieldController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var CustomFieldDefService
     */
    protected $customFieldService;

    /**
     * Search action
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_SEARCH)) {
            return;
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', Request::analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        $this->returnJsonResponseData(['html' => $this->render()]);
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
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount());

        return $itemsGridHelper->updatePager($itemsGridHelper->getCustomFieldsGrid($this->customFieldService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Campo'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'customField/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.customField.create', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponse(1, $e->getMessage());
        }
    }

    /**
     * Sets view data for displaying custom field's data
     *
     * @param $customFieldId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function setViewData($customFieldId = null)
    {
        $this->view->addTemplate('customfield', 'itemshow');

        $customField = $customFieldId ? $this->customFieldService->getById($customFieldId) : new CustomFieldDefinitionData();

        $this->view->assign('field', $customField);
        $this->view->assign('types', SelectItemAdapter::factory(CustomFieldTypeService::getItemsBasic())->getItemsFromModelSelected([$customField->getTypeId()]));
        $this->view->assign('modules', SelectItemAdapter::factory(CustomFieldDefService::getFieldModules())->getItemsFromArraySelected([$customField->getModuleId()]));

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }
    }

    /**
     * Edit action
     *
     * @param $id
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_EDIT)) {
            return;
        }

        $this->view->assign('header', __('Editar Campo'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'customField/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.customField.edit', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Delete action
     *
     * @param $id
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteAction($id = null)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_DELETE)) {
            return;
        }

        try {
            if ($id === null) {
                $this->customFieldService->deleteByIdBatch($this->getItemsIdFromRequest());

                $this->eventDispatcher->notifyEvent('delete.customField.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Campos eliminados')))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Campos eliminados'));
            } else {
                $this->customFieldService->delete($id);

                $this->eventDispatcher->notifyEvent('delete.customField', new Event($this));

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Campo eliminado'));
            }
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_CREATE)) {
            return;
        }

        try {
            $form = new CustomFieldDefForm();
            $form->validate(ActionsInterface::CUSTOMFIELD_CREATE);

            $itemData = $form->getItemData();

            $this->customFieldService->create($itemData);

            $this->eventDispatcher->notifyEvent('create.customField',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Campo creado'))
                    ->addDetail(__u('Campo'), $itemData->getName()))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Campo creado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_EDIT)) {
            return;
        }

        try {
            $form = new CustomFieldDefForm($id);
            $form->validate(ActionsInterface::CUSTOMFIELD_EDIT);

            $itemData = $form->getItemData();

            $this->customFieldService->update($itemData);

            $this->eventDispatcher->notifyEvent('edit.customField',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Campo actualizado'))
                    ->addDetail(__u('Campo'), $itemData->getName()))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Campo actualizado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * View action
     *
     * @param $id
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Campo'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.customField', new Event($this));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
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

        $this->customFieldService = $this->dic->get(CustomFieldDefService::class);
    }

}