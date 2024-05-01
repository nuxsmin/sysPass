<?php
declare(strict_types=1);
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

namespace SP\Infrastructure\Account\Repositories;

use SP\Domain\Account\Models\File as FileModel;
use SP\Domain\Account\Ports\AccountFileRepository;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class AccountFile
 */
final class AccountFile extends BaseRepository implements AccountFileRepository
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param FileModel $fileData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(FileModel $fileData): int
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(FileModel::TABLE)
            ->cols([
                       'accountId' => $fileData->getAccountId(),
                       'name' => $fileData->getName(),
                       'type' => $fileData->getType(),
                       'size' => $fileData->getSize(),
                       'content' => $fileData->getContent(),
                       'extension' => $fileData->getExtension(),
                       'thumb' => $fileData->getThumb(),
                   ]);
        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while saving file'));

        return $this->db->runQuery($queryData)->getLastId();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
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
            ->from(FileModel::TABLE)
            ->join('INNER', 'Account', 'Account.id = AccountFile.accountId')
            ->join('INNER', 'Client', 'Client.id = Account.clientId')
            ->where('AccountFile.id = :id')
            ->bindValues(['id' => $id])
            ->limit(1);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
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
            ->from(FileModel::TABLE)
            ->where('accountId = :accountId')
            ->bindValues(['accountId' => $id])
            ->orderBy(['name ASC'])
            ->limit(1);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(FileModel::TABLE)
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the file'));

        return $this->db->runQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
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
            ->from(FileModel::TABLE)
            ->where('AccountFile.id IN (:accountFileIds)', ['accountFileIds' => $ids]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the files'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
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
            ->from(FileModel::TABLE)
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

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues([
                                   'name' => $search,
                                   'type' => $search,
                                   'accountName' => $search,
                                   'clientName' => $search,
                               ]);
        }

        return $this->db->runQuery(QueryData::build($query), true);
    }
}
