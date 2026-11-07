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

use SP\Domain\Core\Events\Event;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;

/**
 * Class SearchController
 */
final class SearchController extends CategoryBase
{
    /**
     * searchAction
     */
    #[Action(ResponseType::JSON_RPC)]
    public function searchAction(): ActionResponse
    {
        $this->setupApi(AclActionsInterface::CATEGORY_SEARCH);

        $itemSearchData = $this->buildSearchData();

        $this->eventDispatcher->notify('search.category', new Event($this));

        return ActionResponse::ok(
            '',
            $this->categoryService->search($itemSearchData)->getDataAsArray()
        );
    }

    /**
     * @return ItemSearchDto
     * @throws ServiceException
     */
    private function buildSearchData(): ItemSearchDto
    {
        return new ItemSearchDto(
            $this->apiService->getParamString('text'),
            0,
            $this->apiService->getParamInt('count', false, self::SEARCH_COUNT_ITEMS)
        );
    }
}
