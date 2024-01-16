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

namespace SP\Modules\Api\Controllers\Account;

use Exception;
use Klein\Klein;
use SP\Core\Application;
use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Account\Ports\AccountSearchConstants;
use SP\Domain\Account\Ports\AccountSearchService;
use SP\Domain\Api\Ports\ApiServiceInterface;
use SP\Domain\Api\Services\ApiResponse;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Modules\Api\Controllers\ControllerBase;

/**
 * Class SearchController
 */
final class SearchController extends ControllerBase
{
    private AccountSearchService $accountSearchService;

    public function __construct(
        Application         $application,
        Klein               $router,
        ApiServiceInterface $apiService,
        AclInterface        $acl,
        AccountSearchService $accountSearchService
    ) {
        parent::__construct($application, $router, $apiService, $acl);

        $this->accountSearchService = $accountSearchService;
    }

    /**
     * searchAction
     */
    public function searchAction(): void
    {
        try {
            $this->setupApi(AclActionsInterface::ACCOUNT_SEARCH);

            $accountSearchFilter = $this->buildAccountSearchFilter();

            $this->returnResponse(
                ApiResponse::makeSuccess(
                    $this->accountSearchService->getByFilter($accountSearchFilter)->getDataAsArray()
                )
            );
        } catch (Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @return \SP\Domain\Account\Dtos\AccountSearchFilterDto
     * @throws ServiceException
     */
    private function buildAccountSearchFilter(): AccountSearchFilterDto
    {
        $filter = AccountSearchFilterDto::build($this->apiService->getParamString('text'))
                                        ->setCategoryId($this->apiService->getParamInt('categoryId'))
                                        ->setClientId($this->apiService->getParamInt('clientId'))
                                        ->setTagsId(
                                            array_map('intval', $this->apiService->getParamArray('tagsId', false, []))
                                        )
                                        ->setLimitCount($this->apiService->getParamInt('count', false, 50))
                                        ->setSortOrder(
                $this->apiService->getParamInt('order', false, AccountSearchConstants::SORT_DEFAULT)
            );

        $op = $this->apiService->getParamString('op', false, AccountSearchConstants::FILTER_CHAIN_AND);

        if ($op !== null) {
            switch ($op) {
                case AccountSearchConstants::FILTER_CHAIN_AND:
                    $filter->setFilterOperator(AccountSearchConstants::FILTER_CHAIN_AND);
                    break;
                case AccountSearchConstants::FILTER_CHAIN_OR:
                    $filter->setFilterOperator(AccountSearchConstants::FILTER_CHAIN_OR);
                    break;
            }
        }

        return $filter;
    }
}
