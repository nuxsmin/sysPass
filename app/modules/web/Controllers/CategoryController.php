<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
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
use SP\DataModel\CategoryData;
use SP\Forms\CategoryForm;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\Category\CategoryService;

/**
 * Class CategoryController
 *
 * @package SP\Modules\Web\Controllers
 */
class CategoryController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;
    use ItemTrait;

    /**
     * @var CategoryService
     */
    protected $categoryService;

    /**
     * Search action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_SEARCH)) {
            return;
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', Request::analyze('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @return $this
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getSearchGrid()
    {
        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount());

        return $itemsGridHelper->updatePager($itemsGridHelper->getCategoriesGrid($this->categoryService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nueva Categoría'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'category/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.category.create', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $categoryId
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function setViewData($categoryId = null)
    {
        $this->view->addTemplate('category', 'itemshow');

        $category = $categoryId ? $this->categoryService->getById($categoryId) : new CategoryData();

        $this->view->assign('category', $category);

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }

        $this->view->assign('customFields', $this->getCustomFieldsForItem(ActionsInterface::CATEGORY, $categoryId));
    }

    /**
     * Edit action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_EDIT)) {
            return;
        }

        $this->view->assign('header', __('Editar Categoría'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'category/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.category.edit', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Delete action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteAction($id = null)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_DELETE)) {
            return;
        }

        try {
            if ($id === null) {
                $this->categoryService->deleteByIdBatch($this->getItemsIdFromRequest());

                $this->deleteCustomFieldsForItem(ActionsInterface::CATEGORY, $id);

                $this->eventDispatcher->notifyEvent('delete.category',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Categorías eliminadas')))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Categorías eliminadas'));
            } else {
                $this->categoryService->delete($id);

                $this->deleteCustomFieldsForItem(ActionsInterface::CATEGORY, $id);

                $this->eventDispatcher->notifyEvent('delete.category',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Categoría eliminada'))
                            ->addDetail(__u('Categoría'), $id))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Categoría eliminada'));
            }
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveCreateAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_CREATE)) {
            return;
        }

        try {
            $form = new CategoryForm();
            $form->validate(ActionsInterface::CATEGORY_CREATE);

            $itemData = $form->getItemData();

            $id = $this->categoryService->create($itemData);

            $this->addCustomFieldsForItem(ActionsInterface::CATEGORY, $id);

            $this->eventDispatcher->notifyEvent('create.category',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Categoría creada'))
                        ->addDetail(__u('Categoría'), $itemData->getName()))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Categoría creada'));
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     *
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_EDIT)) {
            return;
        }

        try {
            $form = new CategoryForm($id);
            $form->validate(ActionsInterface::CATEGORY_EDIT);

            $itemData = $form->getItemData();

            $this->categoryService->update($itemData);

            $this->updateCustomFieldsForItem(ActionsInterface::CATEGORY, $id);

            $this->eventDispatcher->notifyEvent('edit.category',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Categoría actualizada'))
                        ->addDetail(__u('Categoría'), $itemData->getName()))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Categoría actualizada'));
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
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
        if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Categoría'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.category', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
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