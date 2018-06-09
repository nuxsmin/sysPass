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

namespace SP\Repositories\PublicLink;

use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class PublicLinkRepository
 *
 * @package SP\Repositories\PublicLink
 */
class PublicLinkRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;


    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return int
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM PublicLink WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar enlace'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Returns all the items
     *
     * @return PublicLinkData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.dateUpdate,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName        
              FROM PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON itemId = A.id';

        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkListData::class);
        $queryData->setQuery($query);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return PublicLinkData[]
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.dateUpdate,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName        
              FROM PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON itemId = A.id
              WHERE PL.id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkListData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM PublicLink WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     *
     * @return void
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     *
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkListData::class);
        $queryData->setSelect('PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.dateUpdate,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName,
              C.name AS clientName');
        $queryData->setFrom('PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON itemId = A.id
              INNER JOIN Client C ON A.clientId = C.id');
        $queryData->setOrder('PL.dateExpire DESC');

        if ($SearchData->getSeachString() !== '') {
            $queryData->setWhere('U.login LIKE ? OR A.name LIKE ? OR C.name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $queryData->addParam($search);
            $queryData->addParam($search);
            $queryData->addParam($search);
        }

        $queryData->setLimit('?,?');
        $queryData->addParam($SearchData->getLimitStart());
        $queryData->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($queryData, $this->db);

        $queryRes['count'] = $queryData->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Creates an item
     *
     * @param PublicLinkData $itemData
     *
     * @return int
     * @throws DuplicatedItemException
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new DuplicatedItemException(__u('Enlace ya creado'));
        }

        $query = /** @lang SQL */
            'INSERT INTO PublicLink
            SET itemId = ?,
            `hash` = ?,
            `data` = ?,
            userId = ?,
            typeId = ?,
            notify = ?,
            dateAdd = UNIX_TIMESTAMP(),
            dateExpire = ?,
            maxCountViews = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getItemId(),
            $itemData->getHash(),
            $itemData->getData(),
            $itemData->getUserId(),
            $itemData->getTypeId(),
            (int)$itemData->isNotify(),
            $itemData->getDateExpire(),
            $itemData->getMaxCountViews()
        ]);
        $queryData->setOnErrorMessage(__u('Error al crear enlace'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param PublicLinkData $itemData
     *
     * @return bool
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM PublicLink WHERE itemId = ? LIMIT 1');
        $queryData->addParam($itemData->getItemId());

        DbWrapper::getResults($queryData, $this->db);

        return ($queryData->getQueryNumRows() === 1);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     *
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param PublicLinkData $publicLinkData
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function addLinkView(PublicLinkData $publicLinkData)
    {
        $query = /** @lang SQL */
            'UPDATE PublicLink
            SET countViews = countViews + 1,
            totalCountViews = totalCountViews + 1,
            useInfo = ?
            WHERE `hash` = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $publicLinkData->getUseInfo(),
            $publicLinkData->getHash()
        ]);
        $queryData->setOnErrorMessage(__u('Error al actualizar enlace'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Updates an item
     *
     * @param PublicLinkData $itemData
     *
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $query = /** @lang SQL */
            'UPDATE PublicLink
            SET itemId = ?,
            `hash` = ?,
            `data` = ?,
            userId = ?,
            notify = ?,
            dateAdd = ?,
            dateExpire = ?,
            countViews = ?,
            maxCountViews = ?,
            useInfo = ?,
            typeId = ?
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $itemData->getItemId(),
            $itemData->getHash(),
            $itemData->getData(),
            $itemData->getUserId(),
            (int)$itemData->isNotify(),
            $itemData->getDateAdd(),
            $itemData->getDateExpire(),
            $itemData->getCountViews(),
            $itemData->getMaxCountViews(),
            $itemData->getUseInfo(),
            $itemData->getTypeId(),
            $itemData->getId()
        ]);
        $queryData->setOnErrorMessage(__u('Error al actualizar enlace'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Refreshes a public link
     *
     * @param PublicLinkData $publicLinkData
     *
     * @return bool
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function refresh(PublicLinkData $publicLinkData)
    {
        $query = /** @lang SQL */
            'UPDATE PublicLink
            SET `hash` = ?,
            `data` = ?,
            dateExpire = ?,
            countViews = 0,
            maxCountViews = ?
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams([
            $publicLinkData->getHash(),
            $publicLinkData->getData(),
            $publicLinkData->getDateExpire(),
            $publicLinkData->getMaxCountViews(),
            $publicLinkData->getId()
        ]);
        $queryData->setOnErrorMessage(__u('Error al renovar enlace'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return PublicLinkData
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName        
              FROM PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON PL.itemId = A.id
              WHERE PL.id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkListData::class);
        $queryData->setQuery($query);
        $queryData->addParam($id);

        $queryRes = DbWrapper::getResults($queryData, $this->db);

        if ($queryRes === false) {
            throw new QueryException(__u('Error al obtener enlace'));
        }

        if ($queryData->getQueryNumRows() === 0) {
            throw new NoSuchItemException(__u('El enlace no existe'));
        }

        return $queryRes;
    }

    /**
     * @param $hash string
     *
     * @return bool|PublicLinkData
     * @throws NoSuchItemException
     */
    public function getByHash($hash)
    {
        $query = /** @lang SQL */
            'SELECT PL.id, 
              PL.itemId,
              PL.hash,
              PL.data,
              PL.userId,
              PL.typeId,
              PL.notify,
              PL.dateAdd,
              PL.dateExpire,
              PL.dateUpdate,
              PL.countViews,
              PL.maxCountViews,
              PL.totalCountViews,
              PL.useInfo,
              U.name AS userName,
              U.login AS userLogin,
              A.name AS accountName        
              FROM PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON itemId = A.id
              WHERE PL.hash = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkData::class);
        $queryData->setQuery($query);
        $queryData->addParam($hash);
        $queryData->setOnErrorMessage(__u('Error al obtener enlace'));

        /** @var PublicLinkData $queryRes */
        $queryRes = DbWrapper::getResults($queryData, $this->db);

        if ($queryData->getQueryNumRows() === 0) {
            throw new NoSuchItemException(__u('El enlace no existe'));
        }

        return $queryRes;
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     *
     * @return PublicLinkData
     * @throws QueryException
     */
    public function getHashForItem($itemId)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkData::class);
        $queryData->setQuery('SELECT id, `hash` FROM PublicLink WHERE itemId = ? LIMIT 1');
        $queryData->addParam($itemId);

        $queryRes = DbWrapper::getResults($queryData, $this->db);

        if ($queryRes === false) {
            throw new QueryException(__u('Error al obtener enlace'));
        }

        return $queryRes;
    }
}