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

namespace SP\Infrastructure\Client\Repositories;

use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Ports\AccountFilterBuilder;
use SP\Domain\Client\Models\Client as ClientModel;
use SP\Domain\Client\Ports\ClientRepository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class ClientRepository
 *
 * @template T of ClientModel
 */
final class Client extends Repository implements ClientRepository
{
    use RepositoryItemTrait;

    public const TABLE = 'Client';

    /**
     * Creates an item
     *
     * @param ClientModel $client
     *
     * @return QueryResult
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function create(ClientModel $client): QueryResult
    {
        if ($this->checkDuplicatedOnAdd($client)) {
            throw new DuplicatedItemException(__u('Duplicated client'));
        }

        $query = $this->queryFactory
            ->newInsert()
            ->into(self::TABLE)
            ->cols($client->toArray(null, ['id', 'hash']))
            ->col('hash', $this->makeItemHash($client->getName()));

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the client'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param ClientModel $client
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnAdd(ClientModel $client): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(self::TABLE)
            ->where('hash = :hash')
            ->orWhere('name = :name')
            ->bindValues(
                [
                    'hash' => $client->getHash(),
                    'name' => $client->getName()
                ]
            );

        return $this->db->doQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param ClientModel $client
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update(ClientModel $client): int
    {
        if ($this->checkDuplicatedOnUpdate($client)) {
            throw new DuplicatedItemException(__u('Duplicated client'));
        }

        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols($client->toArray(null, ['id', 'hash']))
            ->where('id = :id')
            ->limit(1)
            ->bindValues(
                [
                    'id' => $client->getId(),
                    'hash' => $this->makeItemHash($client->getName())
                ]
            );

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the client'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param ClientModel $client
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnUpdate(ClientModel $client): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(self::TABLE)
            ->where('(hash = :hash OR name = :name)')
            ->where('id <> :id')
            ->bindValues(
                [
                    'id' => $client->getId(),
                    'hash' => $client->getHash(),
                    'name' => $client->getName(),
                ]
            );

        return $this->db->doQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $clientId
     *
     * @return QueryResult<T>
     */
    public function getById(int $clientId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(ClientModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $clientId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, ClientModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given name
     *
     * @param string $name
     *
     * @return QueryResult<T>
     */
    public function getByName(string $name): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(ClientModel::getCols())
            ->where('(name = :name OR hash = :hash)')
            ->bindValues(['name' => $name, 'hash' => $this->makeItemHash($name)])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, ClientModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(ClientModel::getCols());

        return $this->db->doSelect(QueryData::buildWithMapper($query, ClientModel::class));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $clientIds
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $clientIds): QueryResult
    {
        if (count($clientIds) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('id IN (:ids)', ['ids' => $clientIds]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the clients'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the client'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult<T>
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(ClientModel::getCols(['hash']))
            ->orderBy(['name'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('name LIKE :name OR description LIKE :description');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['name' => $search, 'description' => $search]);
        }

        $queryData = QueryData::build($query)->setMapClassName(ClientModel::class);

        return $this->db->doSelect($queryData, true);
    }

    /**
     * Return the clients visible for the current user
     *
     * @param AccountFilterBuilder $accountFilterUser
     *
     * @return QueryResult
     */
    public function getAllForFilter(AccountFilterBuilder $accountFilterUser): QueryResult
    {
        $query = $accountFilterUser
            ->buildFilter()
            ->cols(['Client.id', 'Client.name'])
            ->join('right', 'Client', 'Account.clientId = Client.id')
            ->where('Account.clientId IS NULL')
            ->orWhere('Client.isGlobal = 1')
            ->groupBy(['id'])
            ->orderBy(['Client.name']);

        return $this->db->doSelect(QueryData::build($query));
    }
}
