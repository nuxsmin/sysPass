<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Account;

use SP\Core\Exceptions\SPException;
use SP\DataModel\FileData;
use SP\DataModel\FileExtData;
use SP\DataModel\ItemSearchData;
use SP\Mgmt\Files\FileUtil;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\ImageUtil;

/**
 * Class AccountFileService
 *
 * @package SP\Services\Account
 */
class AccountFileService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

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
            'INSERT INTO accFiles
            SET accountId = ?,
            name = ?,
            type = ?,
            size = ?,
            content = ?,
            extension = ?,
            thumb = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getAccountId());
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getType());
        $Data->addParam($itemData->getSize());
        $Data->addParam($itemData->getContent());
        $Data->addParam($itemData->getExtension());
        $Data->setOnErrorMessage(__u('No se pudo guardar el archivo'));

        if (FileUtil::isImage($itemData)) {
            $thumbnail = ImageUtil::createThumbnail($itemData->getContent());

            $Data->addParam($thumbnail ?: 'no_thumb');
        } else {
            $Data->addParam('no_thumb');
        }

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
            'SELECT name,
            size,
            type,
            accountId,
            extension,
            account_name,
            customer_name
            FROM accFiles
            INNER JOIN accounts ON account_id = accountId
            INNER JOIN customers ON customer_id = account_customerId
            WHERE id = ? LIMIT 1';

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
     * @return mixed
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT name,
            size,
            type,
            accountId,
            content,
            thumb,
            extension,
            account_name,
            customer_name
            FROM accFiles
            INNER JOIN accounts ON account_id = accountId
            INNER JOIN customers ON customer_id = account_customerId
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(FileExtData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return FileExtData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT name,
            size,
            type,
            accountId,
            content,
            thumb,
            extension,
            account_name,
            customer_name
            FROM accFiles
            INNER JOIN accounts ON account_id = accountId
            INNER JOIN customers ON customer_id = account_customerId';

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
            'SELECT name,
            size,
            type,
            accountId,
            content,
            thumb,
            extension,
            account_name,
            customer_name
            FROM accFiles
            LEFT JOIN accounts ON account_id = accountId
            LEFT JOIN customers ON customer_id = account_customerId
            WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(FileExtData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids)
    {
        foreach ($ids as $id) {
            $this->delete($id);
        }
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return AccountFileService
     * @throws SPException
     */
    public function delete($id)
    {
        // Eliminamos el archivo de la BBDD
        $query = /** @lang SQL */
            'DELETE FROM accFiles WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar el archivo'));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Archivo no encontrado'));
        }

        return $this;
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
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setMapClassName(FileExtData::class);
        $Data->setSelect('id, name, CONCAT(ROUND(size/1000, 2), "KB") AS size, thumb, type, account_name, customer_name');
        $Data->setFrom('accFiles INNER JOIN accounts ON accountId = account_id INNER JOIN customers ON customer_id = account_customerId');
        $Data->setOrder('name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('name LIKE ? OR type LIKE ? OR account_name LIKE ? OR customer_name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}