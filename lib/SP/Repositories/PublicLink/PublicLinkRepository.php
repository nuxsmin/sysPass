<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use RuntimeException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class PublicLinkRepository
 *
 * @package SP\Repositories\PublicLink
 */
final class PublicLinkRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Deletes an item
     *
     * @param $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM PublicLink WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while removing the link'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
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
              INNER JOIN Account A ON itemId = A.id
              ORDER BY PL.id';

        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkListData::class);
        $queryData->setQuery($query);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return new QueryResult();
        }

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
              WHERE PL.id IN (' . $this->getParamsFromArray($ids) . ')
              ORDER BY PL.id';

        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkListData::class);
        $queryData->setQuery($query);
        $queryData->setParams($ids);

        return $this->db->doSelect($queryData);
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
    public function deleteByIdBatch(array $ids)
    {
        if (empty($ids)) {
            return 0;
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM PublicLink WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
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
        throw new RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkListData::class);
        $queryData->setSelect('PublicLink.id, 
              PublicLink.itemId,
              PublicLink.hash,
              PublicLink.data,
              PublicLink.userId,
              PublicLink.typeId,
              PublicLink.notify,
              PublicLink.dateAdd,
              PublicLink.dateExpire,
              PublicLink.dateUpdate,
              PublicLink.countViews,
              PublicLink.maxCountViews,
              PublicLink.totalCountViews,
              PublicLink.useInfo,
              User.name AS userName,
              User.login AS userLogin,
              Account.name AS accountName,
              Client.name AS clientName');
        $queryData->setFrom('PublicLink
              INNER JOIN User ON PublicLink.userId = User.id
              INNER JOIN Account ON PublicLink.itemId = Account.id
              INNER JOIN Client ON Account.clientId = Client.id');
        $queryData->setOrder('PublicLink.dateExpire DESC');

        if ($itemSearchData->getSeachString() !== '') {
            $queryData->setWhere('User.login LIKE ? OR Account.name LIKE ? OR Client.name LIKE ?');

            $search = '%' . $itemSearchData->getSeachString() . '%';
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

    /**
     * Creates an item
     *
     * @param PublicLinkData $itemData
     *
     * @return QueryResult
     * @throws DuplicatedItemException
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new DuplicatedItemException(__u('Link already created'));
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
        $queryData->setOnErrorMessage(__u('Error while creating the link'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param PublicLinkData $itemData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id FROM PublicLink WHERE itemId = ? LIMIT 1');
        $queryData->addParam($itemData->getItemId());

        return $this->db->doQuery($queryData)->getNumRows() === 1;
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
        throw new RuntimeException('Not implemented');
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param PublicLinkData $publicLinkData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
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
        $queryData->setOnErrorMessage(__u('Error while updating the link'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Updates an item
     *
     * @param PublicLinkData $itemData
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
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
        $queryData->setOnErrorMessage(__u('Error while updating the link'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Refreshes a public link
     *
     * @param PublicLinkData $publicLinkData
     *
     * @return int
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
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
        $queryData->setOnErrorMessage(__u('Error while renewing link'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
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
        $queryData->setOnErrorMessage(__u('Error while retrieving the link'));

        return $this->db->doSelect($queryData);
    }

    /**
     * @param $hash string
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
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
        $queryData->setOnErrorMessage(__u('Error while retrieving the link'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @todo Update tests to include userId
     */
    public function getHashForItem($itemId)
    {
        $queryData = new QueryData();
        $queryData->setMapClassName(PublicLinkData::class);
        $queryData->setQuery('SELECT id, `hash`, userId FROM PublicLink WHERE itemId = ? LIMIT 1');
        $queryData->addParam($itemId);
        $queryData->setOnErrorMessage(__u('Error while retrieving the link'));

        return $this->db->doSelect($queryData);
    }
}