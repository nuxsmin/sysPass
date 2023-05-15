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

namespace SP\Infrastructure\Account\Repositories;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\FileData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Ports\AccountFileRepositoryInterface;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use function SP\__u;

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
     * @param  FileData  $fileData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(FileData $fileData): int
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into('AccountFile')
            ->cols([
                'accountId' => $fileData->getAccountId(),
                'name'      => $fileData->getName(),
                'type'      => $fileData->getType(),
                'size'      => $fileData->getSize(),
                'content'   => $fileData->getContent(),
                'extension' => $fileData->getExtension(),
                'thumb'     => $fileData->getThumb(),
            ]);
        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while saving file'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     */
    public function getById(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                'AccountFile.id',
                'AccountFile.name',
                'AccountFile.size',
                'AccountFile.type',
                'AccountFile.accountId',
                'AccountFile.extension',
                'AccountFile.content',
                'AccountFile.thumb',
                'Account.name AS accountName',
                'Client.name AS clientName',
            ])
            ->from('AccountFile')
            ->join('INNER', 'Account', 'Account.id = AccountFile.accountId')
            ->join('INNER', 'Client', 'Client.id = Account.clientId')
            ->where('AccountFile.id = :id')
            ->bindValues(['id' => $id])
            ->limit(1);

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     */
    public function getByAccountId(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                'id',
                'name',
                'size',
                'type',
                'accountId',
                'extension',
                'content',
                'thumb',
            ])
            ->from('AccountFile')
            ->where('accountId = :accountId')
            ->bindValues(['accountId' => $id])
            ->orderBy(['name ASC'])
            ->limit(1);

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * Deletes an item
     *
     * @param  int  $id
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete(int $id): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('AccountFile')
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the file'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
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

        $query = $this->queryFactory
            ->newDelete()
            ->from('AccountFile')
            ->where('AccountFile.id IN (:accountFileIds)', ['accountFileIds' => $ids]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the files'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return \SP\Infrastructure\Database\QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                'AccountFile.id',
                'AccountFile.name',
                'AccountFile.size',
                'AccountFile.type',
                'AccountFile.accountId',
                'AccountFile.extension',
                'AccountFile.thumb',
                'Account.name AS accountName',
                'Client.name AS clientName',
            ])
            ->from('AccountFile')
            ->join('INNER', 'Account', 'Account.id = AccountFile.accountId')
            ->join('INNER', 'Client', 'Client.id = Account.clientId')
            ->orderBy(['AccountFile.name ASC'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('AccountFile.name LIKE :name')
                  ->orWhere('AccountFile.type LIKE :type')
                  ->orWhere('Account.name LIKE :accountName')
                  ->orWhere('Client.name LIKE :clientName');

            $search = '%'.$itemSearchData->getSeachString().'%';

            $query->bindValues([
                'name'        => $search,
                'type'        => $search,
                'accountName' => $search,
                'clientName'  => $search,
            ]);
        }

        return $this->db->doSelect(QueryData::build($query), true);
    }
}
