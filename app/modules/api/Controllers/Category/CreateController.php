<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Api\Controllers\Category;


use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\CategoryData;
use SP\Domain\Api\Services\ApiResponse;

/**
 * Class CreateController
 */
final class CreateController extends CategoryBase
{
    /**
     * createAction
     */
    public function createAction(): void
    {
        try {
            $this->setupApi(ActionsInterface::CATEGORY_CREATE);

            $categoryData = $this->buildCategoryData();

            $id = $this->categoryService->create($categoryData);

            $categoryData->setId($id);

            $this->eventDispatcher->notify(
                'create.category',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Category added'))
                        ->addDetail(__u('Name'), $categoryData->getName())
                        ->addDetail('ID', $id)
                )
            );

            $this->returnResponse(ApiResponse::makeSuccess($categoryData, $id, __('Category added')));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @return \SP\DataModel\CategoryData
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function buildCategoryData(): CategoryData
    {
        $categoryData = new CategoryData();
        $categoryData->setName($this->apiService->getParamString('name', true));
        $categoryData->setDescription($this->apiService->getParamString('description'));

        return $categoryData;
    }
}
