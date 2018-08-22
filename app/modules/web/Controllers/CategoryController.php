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
use SP\DataModel\CategoryData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\CategoryGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\CategoryForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\Category\CategoryService;

/**
 * Class CategoryController
 *
 * @package SP\Modules\Web\Controllers
 */
final class CategoryController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var CategoryService
     */
    protected $categoryService;

    /**
     * Search action
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(Acl::CATEGORY_SEARCH)) {
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

        $itemsGridHelper = $this->dic->get(CategoryGrid::class);

        return $itemsGridHelper->updatePager($itemsGridHelper->getGrid($this->categoryService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(Acl::CATEGORY_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nueva Categoría'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'category/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.category.create', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying category's data
     *
     * @param $categoryId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\ServiceException
     * @throws \SP\Repositories\NoSuchItemException
     */
    protected function setViewData($categoryId = null)
    {
        $this->view->addTemplate('category', 'itemshow');

        $category = $categoryId ? $this->categoryService->getById($categoryId) : new CategoryData();

        $this->view->assign('category', $category);

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ITEMS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }

        $this->view->assign('showViewCustomPass', $this->acl->checkUserAccess(Acl::CUSTOMFIELD_VIEW_PASS));
        $this->view->assign('customFields', $this->getCustomFieldsForItem(Acl::CATEGORY, $categoryId));
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
        if (!$this->acl->checkUserAccess(Acl::CATEGORY_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Editar Categoría'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'category/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.category.edit', new Event($this));

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
        if (!$this->acl->checkUserAccess(Acl::CATEGORY_DELETE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            if ($id === null) {
                $this->categoryService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(Acl::CATEGORY, $id);

                $this->eventDispatcher->notifyEvent('delete.category',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Categorías eliminadas')))
                );

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Categorías eliminadas'));
            }

            $this->categoryService->delete($id);

            $this->deleteCustomFieldsForItem(Acl::CATEGORY, $id);

            $this->eventDispatcher->notifyEvent('delete.category',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Categoría eliminada'))
                        ->addDetail(__u('Categoría'), $id))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Categoría eliminada'));
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
        if (!$this->acl->checkUserAccess(Acl::CATEGORY_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new CategoryForm($this->dic);
            $form->validate(Acl::CATEGORY_CREATE);

            $itemData = $form->getItemData();

            $id = $this->categoryService->create($itemData);

            $this->addCustomFieldsForItem(Acl::CATEGORY, $id, $this->request);

            $this->eventDispatcher->notifyEvent('create.category',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Categoría creada'))
                        ->addDetail(__u('Categoría'), $itemData->getName()))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Categoría creada'));
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
        if (!$this->acl->checkUserAccess(Acl::CATEGORY_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new CategoryForm($this->dic, $id);
            $form->validate(Acl::CATEGORY_EDIT);

            $itemData = $form->getItemData();

            $this->categoryService->update($itemData);

            $this->updateCustomFieldsForItem(Acl::CATEGORY, $id, $this->request);

            $this->eventDispatcher->notifyEvent('edit.category',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Categoría actualizada'))
                        ->addDetail(__u('Categoría'), $itemData->getName()))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Categoría actualizada'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
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
        if (!$this->acl->checkUserAccess(Acl::CATEGORY_VIEW)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Ver Categoría'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.category', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
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

        $this->categoryService = $this->dic->get(CategoryService::class);
    }

}