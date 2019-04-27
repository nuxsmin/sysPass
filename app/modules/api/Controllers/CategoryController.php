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

namespace SP\Modules\Api\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\InvalidClassException;
use SP\DataModel\CategoryData;
use SP\DataModel\ItemSearchData;
use SP\Modules\Api\Controllers\Help\CategoryHelp;
use SP\Services\Api\ApiResponse;
use SP\Services\Category\CategoryService;


/**
 * Class CategoryController
 *
 * @package SP\Modules\Api\Controllers
 */
final class CategoryController extends ControllerBase
{
    /**
     * @var CategoryService
     */
    private $categoryService;

    /**
     * viewAction
     */
    public function viewAction()
    {
        try {
            $this->setupApi(ActionsInterface::CATEGORY_VIEW);

            $id = $this->apiService->getParamInt('id', true);
            $categoryData = $this->categoryService->getById($id);

            $this->eventDispatcher->notifyEvent('show.category',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Category displayed'))
                    ->addDetail(__u('Name'), $categoryData->getName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($categoryData, $id));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * createAction
     */
    public function createAction()
    {
        try {
            $this->setupApi(ActionsInterface::CATEGORY_CREATE);

            $categoryData = new CategoryData();
            $categoryData->setName($this->apiService->getParamString('name', true));
            $categoryData->setDescription($this->apiService->getParamString('description'));

            $id = $this->categoryService->create($categoryData);

            $this->eventDispatcher->notifyEvent('create.category',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Category added'))
                    ->addDetail(__u('Name'), $categoryData->getName())
                    ->addDetail('ID', $id))
            );

            $this->returnResponse(ApiResponse::makeSuccess($categoryData, $id, __('Category added')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * editAction
     */
    public function editAction()
    {
        try {
            $this->setupApi(ActionsInterface::CATEGORY_EDIT);

            $categoryData = new CategoryData();
            $categoryData->setId($this->apiService->getParamInt('id', true));
            $categoryData->setName($this->apiService->getParamString('name', true));
            $categoryData->setDescription($this->apiService->getParamString('description'));

            $this->categoryService->update($categoryData);

            $this->eventDispatcher->notifyEvent('edit.category',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Category updated'))
                    ->addDetail(__u('Name'), $categoryData->getName())
                    ->addDetail('ID', $categoryData->getId()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($categoryData, $categoryData->getId(), __('Category updated')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * deleteAction
     */
    public function deleteAction()
    {
        try {
            $this->setupApi(ActionsInterface::CATEGORY_DELETE);

            $id = $this->apiService->getParamInt('id', true);

            $categoryData = $this->categoryService->getById($id);

            $this->categoryService->delete($id);

            $this->eventDispatcher->notifyEvent('delete.category',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Category deleted'))
                    ->addDetail(__u('Name'), $categoryData->getName())
                    ->addDetail('ID', $categoryData->getId()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($categoryData, $id, __('Category deleted')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * searchAction
     */
    public function searchAction()
    {
        try {
            $this->setupApi(ActionsInterface::CATEGORY_SEARCH);

            $itemSearchData = new ItemSearchData();
            $itemSearchData->setSeachString($this->apiService->getParamString('text'));
            $itemSearchData->setLimitCount($this->apiService->getParamInt('count', false, self::SEARCH_COUNT_ITEMS));

            $this->eventDispatcher->notifyEvent('search.category', new Event($this));

            $this->returnResponse(ApiResponse::makeSuccess($this->categoryService->search($itemSearchData)->getDataAsArray()));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * initialize
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws InvalidClassException
     */
    protected function initialize()
    {
        $this->categoryService = $this->dic->get(CategoryService::class);
        $this->apiService->setHelpClass(CategoryHelp::class);
    }
}