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

namespace SP\Modules\Api\Controllers\Client;


use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\DataModel\ItemSearchData;
use SP\Domain\Api\Services\ApiResponse;

/**
 * Class SearchController
 */
final class SearchController extends ClientBase
{
    /**
     * searchAction
     */
    public function searchAction(): void
    {
        try {
            $this->setupApi(ActionsInterface::CLIENT_SEARCH);

            $itemSearchData = $this->buildSearchData();

            $this->eventDispatcher->notify('search.client', new Event($this));

            $this->returnResponse(
                ApiResponse::makeSuccess(
                    $this->clientService->search($itemSearchData)->getDataAsArray()
                )
            );
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @return \SP\DataModel\ItemSearchData
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function buildSearchData(): ItemSearchData
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString($this->apiService->getParamString('text'));
        $itemSearchData->setLimitCount(
            $this->apiService->getParamInt('count', false, self::SEARCH_COUNT_ITEMS)
        );

        return $itemSearchData;
    }
}
