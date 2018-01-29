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

namespace SP\Repositories\PublicLink;

use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
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
     * @return int
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM PublicLink WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar enlace'));

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows();
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

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkListData::class);
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
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

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkListData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'DELETE FROM PublicLink WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return void
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Unimplemented');
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
        $Data->setMapClassName(PublicLinkListData::class);
        $Data->setSelect('PL.id, 
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
              A.name AS accountName');
        $Data->setFrom('PublicLink PL
              INNER JOIN User U ON PL.userId = U.id
              INNER JOIN Account A ON itemId = A.id');
        $Data->setOrder('PL.dateExpire DESC');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('U.login LIKE ? OR A.name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
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

    /**
     * Creates an item
     *
     * @param PublicLinkData $itemData
     * @return int
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_INFO, __u('Enlace ya creado'));
        }

        $query = /** @lang SQL */
            'INSERT INTO PublicLink
            SET itemId = ?,
            `hash` = ?,
            data = ?,
            userId = ?,
            typeId = ?,
            notify = ?,
            dateAdd = UNIX_TIMESTAMP(),
            dateExpire = ?,
            maxCountViews = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getItemId());
        $Data->addParam($itemData->getHash());
        $Data->addParam($itemData->getData());
        $Data->addParam($itemData->getUserId());
        $Data->addParam($itemData->getTypeId());
        $Data->addParam((int)$itemData->isNotify());
        $Data->addParam($itemData->getDateExpire());
        $Data->addParam($itemData->getMaxCountViews());
        $Data->setOnErrorMessage(__u('Error al crear enlace'));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param PublicLinkData $itemData
     * @return bool
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT id FROM PublicLink WHERE itemId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getItemId());

        DbWrapper::getResults($Data, $this->db);

        return ($Data->getQueryNumRows() === 1);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
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

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($publicLinkData->getUseInfo());
        $Data->addParam($publicLinkData->getHash());
        $Data->setOnErrorMessage(__u('Error al actualizar enlace'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Updates an item
     *
     * @param PublicLinkData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        $query = /** @lang SQL */
            'UPDATE PublicLink
            SET `hash` = ?,
            data = ?,
            notify = ?,
            dateExpire = ?,
            maxCountViews = ?
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getHash());
        $Data->addParam($itemData->getData());
        $Data->addParam((int)$itemData->isNotify());
        $Data->addParam($itemData->getDateExpire());
        $Data->addParam($itemData->getMaxCountViews());
        $Data->addParam($itemData->getId());
        $Data->setOnErrorMessage(__u('Error al actualizar enlace'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Refreshes a public link
     *
     * @param PublicLinkData $publicLinkData
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
            data = ?,
            dateExpire = ?,
            countViews = 0,
            maxCountViews = ?
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($publicLinkData->getHash());
        $Data->addParam($publicLinkData->getData());
        $Data->addParam($publicLinkData->getDateExpire());
        $Data->addParam($publicLinkData->getMaxCountViews());
        $Data->addParam($publicLinkData->getId());
        $Data->setOnErrorMessage(__u('Error al renovar enlace'));

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return PublicLinkData
     * @throws SPException
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

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkListData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener enlace'));
        }

        return $queryRes;
    }

    /**
     * @param $hash string
     * @return bool|PublicLinkData
     * @throws \SP\Core\Exceptions\SPException
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

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkData::class);
        $Data->setQuery($query);
        $Data->addParam($hash);

        /** @var PublicLinkData $queryRes */
        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener enlace'));
        }

        return $queryRes;
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     * @return PublicLinkData
     * @throws SPException
     */
    public function getHashForItem($itemId)
    {
        $query = /** @lang SQL */
            'SELECT id, `hash` FROM PublicLink WHERE itemId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkData::class);
        $Data->setQuery($query);
        $Data->addParam($itemId);

        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener enlace'));
        }

        return $queryRes;
    }
}