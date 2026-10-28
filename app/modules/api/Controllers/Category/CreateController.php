<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Api\Dtos\ApiResponse;
use SP\Domain\Category\Models\Category;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use function SP\__;
use function SP\__u;
use function SP\processException;

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
            $this->setupApi(AclActionsInterface::CATEGORY_CREATE);

            $categoryData = $this->buildCategoryData();

            $id = $this->categoryService->create($categoryData);

            $categoryData = $categoryData->mutate(['id' => $id]);

            $this->eventDispatcher->notify(
                'create.category',
                new Event(
                    $this,
                    EventMessage::build()
                        ->addDescription(__u('Category added'))
                        ->addDetail(__u('Name'), $categoryData->getName())
                        ->addDetail('ID', $id)
                )
            );

            $this->returnResponse(ApiResponse::makeSuccess($categoryData, __('Category added'), $id));
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @return Category
     * @throws ServiceException
     */
    private function buildCategoryData(): Category
    {
        return new Category(
            [
                'name'        => $this->apiService->getParamString('name', true),
                'description' => $this->apiService->getParamString('description'),
            ]
        );
    }
}
