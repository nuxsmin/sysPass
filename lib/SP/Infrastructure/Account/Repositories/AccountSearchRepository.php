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

namespace SP\Infrastructure\Account\Repositories;


use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryFactory;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcherInterface;
use SP\DataModel\AccountSearchVData;
use SP\Domain\Account\Ports\AccountFilterUserInterface;
use SP\Domain\Account\Ports\AccountSearchRepositoryInterface;
use SP\Domain\Account\Search\AccountSearchConstants;
use SP\Domain\Account\Search\AccountSearchFilter;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Util\Filter;

/**
 * Class AccountSearchRepository
 */
final class AccountSearchRepository extends Repository implements AccountSearchRepositoryInterface
{
    private SelectInterface            $query;
    private AccountFilterUserInterface $accountFilterUser;

    public function __construct(
        DatabaseInterface $database,
        ContextInterface $session,
        EventDispatcherInterface $eventDispatcher,
        QueryFactory $queryFactory,
        AccountFilterUserInterface $accountFilterUser
    ) {
        parent::__construct($database, $session, $eventDispatcher, $queryFactory);

        $this->accountFilterUser = $accountFilterUser;
    }

    /**
     * Obtener las cuentas de una búsqueda.
     *
     * @param  AccountSearchFilter  $accountSearchFilter
     *
     * @return QueryResult
     */
    public function getByFilter(AccountSearchFilter $accountSearchFilter): QueryResult
    {
        $this->accountFilterUser->buildFilter($accountSearchFilter->getGlobalSearch(), $this->query);

        // Sets the search text depending on whether special search filters are being used
        $searchText = $accountSearchFilter->getCleanTxtSearch();

        if (!empty($searchText)) {
            $searchTextLike = '%'.$searchText.'%';

            $this->query
                ->where(
                    '(Account.name LIKE :name OR Account.login LIKE :login OR Account.url LIKE :url OR Account.notes LIKE :notes)',
                    [
                        'name'  => $searchTextLike,
                        'login' => $searchTextLike,
                        'url'   => $searchTextLike,
                        'notes' => $searchTextLike,
                    ]
                );
        }

        if ($accountSearchFilter->getCategoryId() !== null) {
            $this->query
                ->where(
                    'Account.categoryId = :categoryId',
                    [
                        'categoryId' => $accountSearchFilter->getCategoryId(),
                    ]
                );
        }

        if ($accountSearchFilter->getClientId() !== null) {
            $this->query
                ->where(
                    'Account.categoryId = :clientId',
                    [
                        'categoryId' => $accountSearchFilter->getClientId(),
                    ]
                );
        }

        if ($accountSearchFilter->isSearchFavorites() === true) {
            $this->query
                ->join(
                    'INNER',
                    'AccountToFavorite',
                    'AccountToFavorite.accountId = Account.id AND AccountToFavorite.userId = :userId',
                    [
                        'userId' => $this->context->getUserData()->getId(),
                    ]
                );
        }

        if ($accountSearchFilter->hasTags()) {
            $this->query->join(
                'INNER',
                'AccountToTag',
                'AccountToTag.accountId = Account.id'
            );

            $this->query
                ->where(
                    'AccountToTag.tagId IN (:tagId)',
                    [
                        'tagId' => $accountSearchFilter->getTagsId(),
                    ]
                );

            if (AccountSearchConstants::FILTER_CHAIN_AND === $accountSearchFilter->getFilterOperator()) {
                $this->query
                    ->groupBy(['Account.id'])
                    ->having(
                        'COUNT(DISTINCT AccountToTag.tagId) = :tagsCount',
                        [
                            'tagsCount' => count($accountSearchFilter->getTagsId()),
                        ]
                    );
            }
        }

        $this->setOrder($accountSearchFilter);

        if ($accountSearchFilter->getLimitCount() > 0) {
            $this->query->limit($accountSearchFilter->getLimitCount());
            $this->query->offset($accountSearchFilter->getLimitStart());
        }

        return $this->db->doSelect(
            QueryData::build($this->query)->setMapClassName(AccountSearchVData::class),
            true
        );
    }

    /**
     * Devuelve la cadena de ordenación de la consulta
     */
    private function setOrder(AccountSearchFilter $filter): void
    {
        $orderKey = match ($filter->getSortKey()) {
            AccountSearchConstants::SORT_NAME => 'Account.name',
            AccountSearchConstants::SORT_CATEGORY => 'Account.categoryName',
            AccountSearchConstants::SORT_LOGIN => 'Account.login',
            AccountSearchConstants::SORT_URL => 'Account.url',
            AccountSearchConstants::SORT_CLIENT => 'Account.clientName',
            default => 'Account.clientName, Account.name',
        };

        if ($filter->isSortViews() && !$filter->getSortKey()) {
            $this->query->orderBy(['Account.countView DESC']);
        } else {
            $sortOrder = match ($filter->getSortOrder()) {
                AccountSearchConstants::SORT_DIR_ASC => 'ASC',
                AccountSearchConstants::SORT_DIR_DESC => 'DESC',
            };

            $this->query->orderBy([
                sprintf('%s %s', $orderKey, $sortOrder),
            ]);
        }
    }

    /**
     * @param  int  $userId
     * @param  int  $userGroupId
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForUser(int $userId, int $userGroupId): SelectInterface
    {
        $where = [
            'Account.userId = :userId',
            'Account.userGroupId = :userGroupId',
            'Account.id IN (SELECT AccountToUser.accountId FROM AccountToUser WHERE AccountToUser.accountId = Account.id AND AccountToUser.userId = :userId
                                    UNION
                                    SELECT AccountToUserGroup.accountId FROM AccountToUserGroup WHERE AccountToUserGroup.accountId = Account.id AND AccountToUserGroup.userGroupId = :userGroupId)',
        ];

        return $this->query
            ->where(sprintf('(%s)', join(sprintf(' %s ', AccountSearchConstants::FILTER_CHAIN_OR), $where)))
            ->bindValues([
                'userId'      => $userId,
                'userGroupId' => $userGroupId,
            ]);
    }

    /**
     * @param  int  $userGroupId
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForGroup(int $userGroupId): SelectInterface
    {
        return $this->query
            ->where('Account.userGroupId = :userGroupId')
            ->orWhere(
                '(Account.id IN (SELECT AccountToUserGroup.accountId FROM AccountToUserGroup WHERE AccountToUserGroup.accountId = id AND AccountToUserGroup.userGroupId = :userGroupId))'
            )
            ->bindValues([
                'userGroupId' => $userGroupId,
            ]);

    }

    /**
     * @param  string  $userGroupName
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForMainGroup(string $userGroupName): SelectInterface
    {
        $userGroupNameLike = '%'.Filter::safeSearchString($userGroupName).'%';

        return $this->query
            ->where('Account.userGroupName LIKE :userGroupName')
            ->bindValues([
                'userGroupName' => $userGroupNameLike,
            ]);
    }

    /**
     * @param  string  $owner
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForOwner(string $owner): SelectInterface
    {
        $ownerLike = '%'.Filter::safeSearchString($owner).'%';

        return $this->query
            ->where('(Account.userLogin LIKE :userLogin OR Account.userName LIKE :userName)')
            ->bindValues([
                'userLogin' => $ownerLike,
                'userName'  => $ownerLike,
            ]);
    }

    /**
     * @param  string  $fileName
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForFile(string $fileName): SelectInterface
    {
        $fileNameLike = '%'.Filter::safeSearchString($fileName).'%';

        return $this->query
            ->where(
                '(Account.id IN (SELECT AccountFile.accountId FROM AccountFile WHERE AccountFile.name LIKE :fileName))'
            )
            ->bindValues([
                'fileName' => $fileNameLike,
            ]);
    }

    /**
     * @param  int  $accountId
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForAccountId(int $accountId): SelectInterface
    {
        return $this->query
            ->where('Account.id = :id')
            ->bindValues([
                'id' => $accountId,
            ]);
    }

    /**
     * @param  string  $clientName
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForClient(string $clientName): SelectInterface
    {
        $clientNameLike = '%'.Filter::safeSearchString($clientName).'%';

        return $this->query
            ->where('Account.clientName LIKE :clientName')
            ->bindValues([
                'clientName' => $clientNameLike,
            ]);
    }

    /**
     * @param  string  $categoryName
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForCategory(string $categoryName): SelectInterface
    {
        $categoryNameLike = '%'.Filter::safeSearchString($categoryName).'%';

        return $this->query
            ->where('Account.categoryName LIKE :categoryName')
            ->bindValues([
                'categoryName' => $categoryNameLike,
            ]);
    }

    /**
     * @param  string  $accountName
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForAccountNameRegex(string $accountName): SelectInterface
    {
        return $this->query
            ->where('Account.name REGEXP :name')
            ->bindValues([
                'name' => $accountName,
            ]);
    }

    public function withFilterForIsExpired(): SelectInterface
    {
        return $this->query
            ->where('(Account.passDateChange > 0 AND UNIX_TIMESTAMP() > Account.passDateChange)');
    }

    public function withFilterForIsNotExpired(): SelectInterface
    {
        return $this->query
            ->where(
                '(Account.passDateChange = 0 OR Account.passDateChange IS NULL OR UNIX_TIMESTAMP() < Account.passDateChange)'
            );
    }

    /**
     * @param  int  $userId
     * @param  int  $userGroupId
     *
     * @return \Aura\SqlQuery\Common\SelectInterface
     */
    public function withFilterForIsPrivate(int $userId, int $userGroupId): SelectInterface
    {
        return $this->query
            ->where(
                '(Account.isPrivate = 1 AND Account.userId = :userId) OR (Account.isPrivateGroup = 1 AND Account.userGroupId = :userGroupId)'
            )
            ->bindValues([
                'userId'      => $userId,
                'userGroupId' => $userGroupId,
            ]);
    }

    public function withFilterForIsNotPrivate(): SelectInterface
    {
        return $this->query
            ->where(
                '(Account.isPrivate = 0 OR Account.isPrivate IS NULL) AND (Account.isPrivateGroup = 0 OR Account.isPrivateGroup IS NULL)'
            );
    }

    protected function initialize()
    {
        $this->query = $this->queryFactory
            ->newSelect()
            ->from('account_search_v AS Account')
            ->distinct();
    }
}
