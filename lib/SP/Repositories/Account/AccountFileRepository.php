<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Core\Exceptions\SPException;
use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

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
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        $query = /** @lang SQL */
            'INSERT INTO AccountFile
            SET accountId = ?,
            name = ?,
            type = ?,
            size = ?,
            content = ?,
            extension = ?,
            thumb = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setParams([
            $itemData->getAccountId(),
            $itemData->getName(),
            $itemData->getType(),
            $itemData->getSize(),
            $itemData->getContent(),
            $itemData->getExtension(),
            $itemData->getThumb()
        ]);
        $Data->setOnErrorMessage(__u('No se pudo guardar el archivo'));

//        $Log = new Log();
//        $LogMessage = $Log->getLogMessage();
//        $LogMessage->setAction(__('Subir Archivo', false));
//        $LogMessage->addDetails(__('Cuenta', false), AccountUtil::getAccountNameById($this->itemData->getAccfileAccountId()));
//        $LogMessage->addDetails(__('Archivo', false), $this->itemData->getAccfileName());
//        $LogMessage->addDetails(__('Tipo', false), $this->itemData->getAccfileType());
//        $LogMessage->addDetails(__('Tamaño', false), $this->itemData->getRoundSize() . 'KB');
//
//        DbWrapper::getQuery($Data);
//
//        $LogMessage->addDescription(__('Archivo subido', false));
//        $Log->writeLog();
//
//        Email::sendEmail($LogMessage);

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Updates an item
     *
     * @param mixed $itemData
     * @return mixed
     */
    public function update($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * @param $id
     * @return FileExtData
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

        $Data = new QueryData();
        $Data->setMapClassName(FileExtData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return FileExtData
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

        $Data = new QueryData();
        $Data->setMapClassName(FileExtData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return FileData[]
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

        $Data = new QueryData();
        $Data->setMapClassName(FileData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return FileExtData[]
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

        $Data = new QueryData();
        $Data->setMapClassName(FileExtData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
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
            WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(FileExtData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return AccountFileRepository
     * @throws SPException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM AccountFile WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar el archivo'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(__u('Archivo no encontrado'), SPException::INFO);
        }

        return $this;
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountFile WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error al eliminar el archivos'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
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
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData)
    {
        $Data = new QueryData();
        $Data->setMapClassName(FileExtData::class);
        $Data->setSelect('AF.id, AF.name, CONCAT(ROUND(AF.size/1000, 2), "KB") AS size, AF.thumb, AF.type, A.name as accountName, C.name as clientName');
        $Data->setFrom('AccountFile AF INNER JOIN Account A ON A.id = AF.accountId INNER JOIN Client C ON A.clientId = C.id');
        $Data->setOrder('A.name');

        if ($itemSearchData->getSeachString() !== '') {
            $Data->setWhere('AF.name LIKE ? OR AF.type LIKE ? OR A.name LIKE ? OR C.name LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($itemSearchData->getLimitStart());
        $Data->addParam($itemSearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}