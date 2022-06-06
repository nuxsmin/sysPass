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
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\CategoryData;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Category\Services\CategoryService;
use SP\Domain\Common\Services\ServiceException;
use SP\Html\DataGrid\DataGridInterface;
use SP\Http\JsonResponse;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\Helpers\Grid\CategoryGrid;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\CategoryForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\Controller\ItemTrait;

/**
 * Class CategoryController
 *
 * @package SP\Modules\Web\Controllers
 */
final class CategoryController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    protected ?CategoryService $categoryService = null;

    /**
     * Search action
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws \JsonException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_SEARCH)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('You don\'t have permission to do this operation')
            );
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

        $itemsGridHelper = $this->dic->get(CategoryGrid::class);

        return $itemsGridHelper->updatePager(
            $itemsGridHelper->getGrid($this->categoryService->search($itemSearchData)),
            $itemSearchData
        );
    }

    /**
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function createAction(): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_CREATE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('New Category'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'category/saveCreate');

            $this->setViewData();

            $this->eventDispatcher->notifyEvent(
                'show.category.create',
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
     * Sets view data for displaying category's data
     *
     * @param int|null $categoryId
     *
     * @throws ConstraintException
     * @throws DependencyException
     * @throws NoSuchItemException
     * @throws NotFoundException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    protected function setViewData(?int $categoryId = null)
    {
        $this->view->addTemplate('category', 'itemshow');

        $category = $categoryId
            ? $this->categoryService->getById($categoryId)
            : new CategoryData();

        $this->view->assign('category', $category);

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

        $this->view->assign(
            'showViewCustomPass',
            $this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_VIEW_PASS)
        );
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(ActionsInterface::CATEGORY, $categoryId)
        );
    }

    /**
     * Edit action
     *
     * @param int $id
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function editAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_EDIT)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('Edit Category'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'category/saveEdit/' . $id);


            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent(
                'show.category.edit',
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
     * Delete action
     *
     * @param int|null $id
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_DELETE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            if ($id === null) {
                $this->categoryService
                    ->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(ActionsInterface::CATEGORY, $id);

                $this->eventDispatcher->notifyEvent(
                    'delete.category',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Categories deleted'))
                    )
                );

                return $this->returnJsonResponse(
                    JsonResponse::JSON_SUCCESS,
                    __u('Categories deleted')
                );
            }

            $this->categoryService->delete($id);

            $this->deleteCustomFieldsForItem(ActionsInterface::CATEGORY, $id);

            $this->eventDispatcher->notifyEvent(
                'delete.category',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Category deleted'))
                        ->addDetail(__u('Category'), $id)
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Category deleted')
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
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function saveCreateAction(): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_CREATE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $form = new CategoryForm($this->dic);
            $form->validate(ActionsInterface::CATEGORY_CREATE);

            $itemData = $form->getItemData();

            $id = $this->categoryService->create($itemData);

            $this->eventDispatcher->notifyEvent(
                'create.category',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Category added'))
                        ->addDetail(__u('Category'), $itemData->getName())
                )
            );

            $this->addCustomFieldsForItem(
                ActionsInterface::CATEGORY,
                $id,
                $this->request
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Category added')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
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
     * Saves edit action
     *
     * @param int $id
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function saveEditAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_EDIT)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $form = new CategoryForm($this->dic, $id);
            $form->validate(Acl::CATEGORY_EDIT);

            $itemData = $form->getItemData();

            $this->categoryService->update($itemData);

            $this->eventDispatcher->notifyEvent(
                'edit.category',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Category updated'))
                        ->addDetail(__u('Category'), $itemData->getName())
                )
            );

            $this->updateCustomFieldsForItem(
                ActionsInterface::CATEGORY,
                $id,
                $this->request
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Category updated')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
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
     * View action
     *
     * @param int $id
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function viewAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::CATEGORY_VIEW)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('View Category'));
            $this->view->assign('isView', true);

            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent(
                'show.category',
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
     * Initialize class
     *
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checkLoggedIn();

        $this->categoryService = $this->dic->get(CategoryService::class);
    }

}