<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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


use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\CustomFieldDefinitionData;
use SP\Forms\CustomFieldDefForm;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Repositories\CustomField\CustomFieldDefRepository;
use SP\Repositories\CustomField\CustomFieldTypeRepository;
use SP\Services\CustomField\CustomFieldDefService;

/**
 * Class CustomFieldController
 *
 * @package SP\Modules\Web\Controllers
 */
class CustomFieldController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;
    use ItemTrait;

    /**
     * @var CustomFieldDefService
     */
    protected $customFieldService;

    /**
     * Search action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_SEARCH)) {
            return;
        }

        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $grid = $itemsGridHelper->getCustomFieldsGrid($this->customFieldService->search($this->getSearchData($this->configData)))->updatePager();

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', Request::analyze('activetab', 0));
        $this->view->assign('data', $grid);

        $this->returnJsonResponseData(['html' => $this->render()]);
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

            $this->eventDispatcher->notifyEvent('show.customField.create', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $customFieldId
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function setViewData($customFieldId = null)
    {
        $this->view->addTemplate('customfield', 'itemshow');

        $customField = $customFieldId ? $this->customFieldService->getById($customFieldId) : new CustomFieldDefinitionData();

        // FIXME
        $customFieldTypeService = new CustomFieldTypeRepository();

        $this->view->assign('field', $customField);
        $this->view->assign('types', $customFieldTypeService->getAll());
        $this->view->assign('modules', CustomFieldDefRepository::getFieldModules());

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

            $this->eventDispatcher->notifyEvent('show.customField.edit', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Delete action
     *
     * @param $id
     */
    public function deleteAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_DELETE)) {
            return;
        }

        try {
            $this->customFieldService->delete($id);

            $this->deleteCustomFieldsForItem(ActionsInterface::CUSTOMFIELD, $id);

            $this->eventDispatcher->notifyEvent('delete.customField', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Campo eliminado'));
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

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

            $this->customFieldService->create($form->getItemData());

            $this->eventDispatcher->notifyEvent('create.customField', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Campo creado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     * @throws \SP\Core\Dic\ContainerException
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_EDIT)) {
            return;
        }

        try {
            $form = new CustomFieldDefForm($id);
            $form->validate(ActionsInterface::CUSTOMFIELD_EDIT);

            $this->customFieldService->update($form->getItemData());

            $this->eventDispatcher->notifyEvent('edit.customField', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Campo actualizado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * View action
     *
     * @param $id
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

            $this->eventDispatcher->notifyEvent('show.customField', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Initialize class
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->customFieldService = $this->dic->get(CustomFieldDefService::class);
    }

}