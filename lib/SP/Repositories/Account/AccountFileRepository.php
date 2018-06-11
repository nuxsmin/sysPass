<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Repositories\Account;

use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;

/**
 * Class AccountFileRepository
 *
 * @package SP\Repositories\Account
 */
class AccountFileRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Creates an item
     *
     * @param FileData $itemData
     *
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
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
            $itemData->getThumb()
        ]);
        $queryData->setOnErrorMessage(__u('No se pudo guardar el archivo'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Updates an item
     *
     * @param mixed $itemData
     *
     * @return mixed
     */
    public function update($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * @param $id
     *
     * @return FileExtData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getInfoById($id)
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

        return $this->db->doSelect($queryData)->getData();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return FileExtData
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getById($id)
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

        return $this->db->doSelect($queryData)->getData();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return FileData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByAccountId($id)
    {
        $query = /** @lang SQL */
            'SELECT AF.id,
            AF.name,
            AF.size,
            AF.type,
            AF.accountId,
            AF.content,
            AF.thumb,
            AF.extension
            FROM AccountFile AF
            WHERE accountId = ?';

        $queryData = new QueryData();
        $queryData->setMapClassName(FileData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);

        return $this->db->doSelect($queryData)->getDataAsArray();
    }

    /**
     * Returns all the items
     *
     * @return FileExtData[]
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getAll()
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
            INNER JOIN Client C ON A.clientId = C.id';

        $queryData = new QueryData();
        $queryData->setMapClassName(FileExtData::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData)->getDataAsArray();
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return array
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getByIdBatch(array $ids)
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
            WHERE AF.id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setMapClassName(FileExtData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doQuery($queryData)->getDataAsArray();
    }

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return AccountFileRepository
     * @throws NoSuchItemException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM AccountFile WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar el archivo'));

        if ($this->db->doQuery($queryData)->getAffectedNumRows() === 0) {
            throw new NoSuchItemException(__u('Archivo no encontrado'));
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountFile WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error al eliminar los archivos'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(FileExtData::class);
        $queryData->setSelect('AF.id, AF.name, CONCAT(ROUND(AF.size/1000, 2), "KB") AS size, AF.thumb, AF.type, A.name as accountName, C.name as clientName');
        $queryData->setFrom('AccountFile AF INNER JOIN Account A ON A.id = AF.accountId INNER JOIN Client C ON A.clientId = C.id');
        $queryData->setOrder('A.name');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('AF.name LIKE ? OR AF.type LIKE ? OR A.name LIKE ? OR C.name LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($itemSearchData->getLimitStart());
        $queryData->addParam($itemSearchData->getLimitCount());

        return $this->db->doSelect($queryData, true);
    }
}