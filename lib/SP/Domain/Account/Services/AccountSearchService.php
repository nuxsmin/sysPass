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

namespace SP\Domain\Account\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Domain\Account\Ports\AccountSearchDataBuilderInterface;
use SP\Domain\Account\Ports\AccountSearchRepositoryInterface;
use SP\Domain\Account\Ports\AccountSearchServiceInterface;
use SP\Domain\Account\Search\AccountSearchConstants;
use SP\Domain\Account\Search\AccountSearchFilter;
use SP\Domain\Account\Search\AccountSearchTokenizer;
use SP\Domain\Common\Services\Service;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Infrastructure\Database\QueryResult;
use SP\Util\Filter;
use function SP\processException;

/**
 * Class AccountSearchService
 */
final class AccountSearchService extends Service implements AccountSearchServiceInterface
{
    public function __construct(
        Application $application,
        private UserServiceInterface $userService,
        private UserGroupServiceInterface $userGroupService,
        private AccountSearchRepositoryInterface $accountSearchRepository,
        private AccountSearchDataBuilderInterface $accountSearchDataBuilder
    ) {
        parent::__construct($application);
    }

    /**
     * Procesar los resultados de la búsqueda
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter): QueryResult
    {
        if (!empty($accountSearchFilter->getTxtSearch())) {
            $tokens = (new AccountSearchTokenizer())->tokenizeFrom($accountSearchFilter->getTxtSearch());

            if (null !== $tokens) {
                $accountSearchFilter->setFilterOperator($tokens->getOperator());
                $accountSearchFilter->setCleanTxtSearch($tokens->getSearch());

                $this->processFilterItems($tokens->getItems());
                $this->processFilterConditions($tokens->getConditions());
            }
        }

        $queryResult = $this->accountSearchRepository->getByFilter($accountSearchFilter);

        return QueryResult::withTotalNumRows(
            $this->accountSearchDataBuilder->buildFrom($queryResult),
            $queryResult->getTotalNumRows()
        );
    }

    private function processFilterItems(array $filters): void
    {
        foreach ($filters as $filter => $text) {
            try {
                switch ($filter) {
                    case AccountSearchConstants::FILTER_USER_NAME:
                        $userData = $this->userService->getByLogin(Filter::safeSearchString($text));

                        $this->accountSearchRepository->withFilterForUser(
                            $userData->getId(),
                            $userData->getUserGroupId()
                        );
                        break;
                    case AccountSearchConstants::FILTER_OWNER:
                        $this->accountSearchRepository->withFilterForOwner($text);
                        break;
                    case AccountSearchConstants::FILTER_GROUP_NAME:
                        $userGroupData = $this->userGroupService->getByName(Filter::safeSearchString($text));

                        $this->accountSearchRepository->withFilterForGroup($userGroupData->getId());
                        break;
                    case AccountSearchConstants::FILTER_MAIN_GROUP:
                        $this->accountSearchRepository->withFilterForMainGroup($text);
                        break;
                    case AccountSearchConstants::FILTER_FILE_NAME:
                        $this->accountSearchRepository->withFilterForFile($text);
                        break;
                    case AccountSearchConstants::FILTER_ACCOUNT_ID:
                        $this->accountSearchRepository->withFilterForAccountId((int)$text);
                        break;
                    case AccountSearchConstants::FILTER_CLIENT_NAME:
                        $this->accountSearchRepository->withFilterForClient($text);
                        break;
                    case AccountSearchConstants::FILTER_CATEGORY_NAME:
                        $this->accountSearchRepository->withFilterForCategory($text);
                        break;
                    case AccountSearchConstants::FILTER_ACCOUNT_NAME_REGEX:
                        $this->accountSearchRepository->withFilterForAccountNameRegex($text);
                        break;
                }
            } catch (Exception $e) {
                processException($e);
            }
        }
    }

    private function processFilterConditions(array $filters,): void
    {
        foreach ($filters as $filter) {
            switch ($filter) {
                case AccountSearchConstants::FILTER_IS_EXPIRED:
                    $this->accountSearchRepository->withFilterForIsExpired();
                    break;
                case AccountSearchConstants::FILTER_NOT_EXPIRED:
                    $this->accountSearchRepository->withFilterForIsNotExpired();
                    break;
                case AccountSearchConstants::FILTER_IS_PRIVATE:
                    $this->accountSearchRepository->withFilterForIsPrivate(
                        $this->context->getUserData()->getId(),
                        $this->context->getUserData()->getUserGroupId()
                    );
                    break;
                case AccountSearchConstants::FILTER_NOT_PRIVATE:
                    $this->accountSearchRepository->withFilterForIsNotPrivate();
                    break;
            }
        }
    }
}
