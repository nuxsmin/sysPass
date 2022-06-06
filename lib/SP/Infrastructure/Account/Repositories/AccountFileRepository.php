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

use RuntimeException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\In\AccountFileRepositoryInterface;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountFileRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
final class AccountFileRepository extends Repository implements AccountFileRepositoryInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param  FileData  $itemData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create($itemData): int
    {
        $query = /** @lang SQL */
            'INSERT INTO AccountFile
            SET accountId = ?,
            `name` = ?,
            type = ?,
            size = ?,
            content = ?,
            extension = ?,
            thumb = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getAccountId(),
            $itemData->getName(),
            $itemData->getType(),
            $itemData->getSize(),
            $itemData->getContent(),
            $itemData->getExtension(),
            $itemData->getThumb(),
        ]);
        $queryData->setOnErrorMessage(__u('Error while saving file'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @param  mixed  $itemData
     *
     * @return void
     */
    public function update($itemData): void
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * @param  int  $id
     *
     * @return QueryResult
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getInfoById(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT AF.name,
            AF.size,
            AF.type,
            AF.accountId,
            AF.extension,
            A.name AS accountName,
            C.name AS clientName
            FROM AccountFile AF
            INNER JOIN Account A ON A.id = AF.accountId
            INNER JOIN Client C ON A.clientId = C.id
            WHERE AF.id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(FileExtData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT AF.id, 
            AF.name,
            AF.size,
            AF.type,
            AF.accountId,
            AF.content,
            AF.thumb,
            AF.extension,
            A.name AS accountName,
            C.name AS clientName
            FROM AccountFile AF
            INNER JOIN Account A ON A.id = AF.accountId
            INNER JOIN Client C ON A.clientId = C.id
            WHERE AF.id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(FileExtData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByAccountId(int $id): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT id,
            `name`,
            size,
            type,
            accountId,
            content,
            thumb,
            extension
            FROM AccountFile
            WHERE accountId = ?
            ORDER BY `name`';

        $queryData = new QueryData();
        $queryData->setMapClassName(FileData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $query = /** @lang SQL */
            'SELECT AF.id,
            AF.name,
            AF.size,
            AF.type,
            AF.accountId,
            AF.content,
            AF.thumb,
            AF.extension,
            A.name AS accountName,
            C.name AS clientName
            FROM AccountFile AF
            INNER JOIN Account A ON A.id = AF.accountId
            INNER JOIN Client C ON A.clientId = C.id
            ORDER BY AF.name';

        $queryData = new QueryData();
        $queryData->setMapClassName(FileExtData::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param  array  $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIdBatch(array $ids): QueryResult
    {
        if (count($ids) === 0) {
            return new QueryResult();
        }

        $query = /** @lang SQL */
            'SELECT AF.id,
            AF.name,
            AF.size,
            AF.type,
            AF.accountId,
            AF.content,
            AF.thumb,
            AF.extension,
            A.name AS accountName,
            C.name AS clientName
            FROM AccountFile AF
            INNER JOIN Account A ON A.id = AF.accountId
            INNER JOIN Client C ON A.clientId = C.id
            WHERE AF.id IN ('.$this->getParamsFromArray($ids).')';

        $queryData = new QueryData();
        $queryData->setMapClassName(FileExtData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doQuery($queryData);
    }

    /**
     * Deletes an item
     *
     * @param  int  $id
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete(int $id): int
    {
        $query = /** @lang SQL */
            'DELETE FROM AccountFile WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while deleting the file'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountFile WHERE id IN ('.$this->getParamsFromArray($ids).')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error while deleting the files'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse(int $id): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param  mixed  $itemData
     */
    public function checkDuplicatedOnUpdate($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param  mixed  $itemData
     */
    public function checkDuplicatedOnAdd($itemData): bool
    {
        throw new RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return \SP\Infrastructure\Database\QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(FileExtData::class);
        $queryData->setSelect(
            'AccountFile.id, 
        AccountFile.accountId, 
        AccountFile.name, 
        AccountFile.size, 
        AccountFile.thumb, 
        AccountFile.type, 
        AccountFile.extension, 
        Account.name as accountName, 
        Client.name as clientName'
        );
        $queryData->setFrom(
            'AccountFile 
        INNER JOIN Account ON Account.id = AccountFile.accountId 
        INNER JOIN Client ON Account.clientId = Client.id'
        );
        $queryData->setOrder('Account.name');

        if (!empty($itemSearchData->getSeachString())) {
            $queryData->setWhere(
                'AccountFile.name LIKE ? 
            OR AccountFile.type LIKE ? 
            OR Account.name LIKE ? 
            OR Client.name LIKE ?'
            );

            $search = '%'.$itemSearchData->getSeachString().'%';
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit(
            '?,?',
            [$itemSearchData->getLimitStart(), $itemSearchData->getLimitCount()]
        );

        return $this->db->doSelect($queryData, true);
    }
}