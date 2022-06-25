<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Api\Controllers\Account;

use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Domain\Account\Services\AccountSearchFilter;
use SP\Domain\Api\Services\ApiResponse;
use SP\Mvc\Model\QueryCondition;

/**
 * Class SearchController
 */
final class SearchController extends AccountBase
{
    /**
     * searchAction
     */
    public function searchAction(): void
    {
        try {
            $this->setupApi(ActionsInterface::ACCOUNT_SEARCH);

            $accountSearchFilter = $this->buildAccountSearchFilter();

            $this->returnResponse(
                ApiResponse::makeSuccess($this->accountService->getByFilter($accountSearchFilter)->getDataAsArray())
            );
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @return \SP\Domain\Account\Services\AccountSearchFilter
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    private function buildAccountSearchFilter(): AccountSearchFilter
    {
        $accountSearchFilter = new AccountSearchFilter();
        $accountSearchFilter->setCleanTxtSearch($this->apiService->getParamString('text'));
        $accountSearchFilter->setCategoryId($this->apiService->getParamInt('categoryId'));
        $accountSearchFilter->setClientId($this->apiService->getParamInt('clientId'));

        $tagsId = array_map('intval', $this->apiService->getParamArray('tagsId', false, []));

        if (count($tagsId) !== 0) {
            $accountSearchFilter->setTagsId($tagsId);
        }

        $op = $this->apiService->getParamString('op');

        if ($op !== null) {
            switch ($op) {
                case 'and':
                    $accountSearchFilter->setFilterOperator(QueryCondition::CONDITION_AND);
                    break;
                case 'or':
                    $accountSearchFilter->setFilterOperator(QueryCondition::CONDITION_OR);
                    break;
            }
        }

        $accountSearchFilter->setLimitCount($this->apiService->getParamInt('count', false, 50));
        $accountSearchFilter->setSortOrder(
            $this->apiService->getParamInt('order', false, AccountSearchFilter::SORT_DEFAULT)
        );

        return $accountSearchFilter;
    }
}