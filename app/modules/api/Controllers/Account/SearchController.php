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

namespace SP\Modules\Api\Controllers\Account;

use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Account\Models\AccountSearchView;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountCacheService;
use SP\Domain\Account\Ports\AccountSearchConstants;
use SP\Domain\Account\Ports\AccountSearchService;
use SP\Domain\Api\Ports\ApiService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Api\Controllers\ApiControllerHelper;
use SP\Modules\Api\Controllers\ControllerBase;

/**
 * Class SearchController
 */
final class SearchController extends ControllerBase
{
    public function __construct(
        ApiControllerHelper                   $controllerHelper,
        ApiService                            $apiService,
        private readonly AccountSearchService $accountSearchService,
        private readonly AccountAclService    $accountAclService,
        private readonly AccountCacheService  $accountCacheService
    ) {
        parent::__construct($controllerHelper, $apiService);
    }

    #[Action(ResponseType::JSON_RPC)]
    public function searchAction(): ActionResponse
    {
        $this->setupApi(AclActionsInterface::ACCOUNT_SEARCH);

        $accountSearchFilter = $this->buildAccountSearchFilter();
        $items = $this->accountSearchService->getByFilter($accountSearchFilter)->getDataAsArray() ?? [];

        $sanitized = array_map(
            function (AccountSearchView $item): AccountSearchView {
                $cache = $this->accountCacheService->getCacheForAccount(
                    (int)$item->getId(),
                    (int)(strtotime($item->getDateEdit() ?? '') ?: 0)
                );

                $accountAcl = $this->accountAclService->getAcl(
                    AclActionsInterface::ACCOUNT_SEARCH,
                    AccountAclDto::makeFromAccountSearch(
                        $item,
                        $cache->getUsers(),
                        $cache->getUserGroups()
                    )
                );

                if ($accountAcl->isResultView()) {
                    return $item;
                }

                return new AccountSearchView([
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'clientId' => $item->getClientId(),
                    'clientName' => $item->getClientName(),
                    'categoryId' => $item->getCategoryId(),
                    'categoryName' => $item->getCategoryName(),
                ]);
            },
            $items
        );

        return ActionResponse::ok('', $sanitized);
    }

    /**
     * @return AccountSearchFilterDto
     * @throws ServiceException
     */
    private function buildAccountSearchFilter(): AccountSearchFilterDto
    {
        $globalSearch = ($this->apiService->getParamInt('globalSearch', false, 0) ?? 0) !== 0
            || ($this->apiService->getParamInt('gsearch', false, 0) ?? 0) !== 0;

        $filter = AccountSearchFilterDto::build($this->apiService->getParamString('text') ?? '')
                                        ->setGlobalSearch($globalSearch)
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
