<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
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

namespace SP\Repositories\UserProfile;

use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class UserProfileRepository
 *
 * @package SP\Repositories\UserProfile
 */
class UserProfileRepository extends Repository implements RepositoryItemInterface
{
    use RepositoryItemTrait;

    /**
     * Obtener el nombre de los usuarios que usan un perfil.
     *
     * @param $id int El id del perfil
     * @return array
     */
    public function getUsersForProfile($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT login FROM User WHERE userProfileId = ?');
        $queryData->addParam($id);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

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
        if ($this->checkInUse($id)) {
            throw new SPException(__u('Perfil en uso'), SPException::INFO);
        }

        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM UserProfile WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar perfil'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkInUse($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT userProfileId FROM User WHERE userProfileId = ?');
        $queryData->addParam($id);

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return UserProfileData
     */
    public function getById($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, name, profile FROM UserProfile WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setMapClassName(UserProfileData::class);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * Returns all the items
     *
     * @return UserProfileData[]
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT id, name FROM UserProfile ORDER BY name');
        $queryData->setMapClassName(UserProfileData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT id, name FROM UserProfile WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setParams($ids);
        $queryData->setMapClassName(ProfileData::class);

        return DbWrapper::getResultsArray($queryData, $this->db);
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
        $queryData->setQuery('DELETE FROM UserProfile WHERE id IN (' . $this->getParamsFromArray($ids) . ')');
        $queryData->setParams($ids);
        $queryData->setOnErrorMessage(__u('Error al eliminar los perfiles'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $queryData = new QueryData();
        $queryData->setSelect('id, name');
        $queryData->setFrom('UserProfile');
        $queryData->setOrder('name');

        if ($SearchData->getSeachString() !== '') {
            $queryData->setWhere('name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
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
     * @param UserProfileData $itemData
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws SPException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(__u('Nombre de perfil duplicado'), SPException::INFO);
        }

        $queryData = new QueryData();
        $queryData->setQuery('INSERT INTO UserProfile SET name = ?, profile = ?');
        $queryData->addParam($itemData->getName());
        $queryData->addParam(serialize($itemData->getProfile()));
        $queryData->setOnErrorMessage(__u('Error al crear perfil'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param UserProfileData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT name FROM UserProfile WHERE UPPER(name) = ?');
        $queryData->addParam($itemData->getName());

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }

    /**
     * Updates an item
     *
     * @param UserProfileData $itemData
     * @return bool
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(__u('Nombre de perfil duplicado'), SPException::INFO);
        }

        $query = /** @lang SQL */
            'UPDATE UserProfile SET name = ?, profile = ? WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($itemData->getName());
        $queryData->addParam(serialize($itemData->getProfile()));
        $queryData->addParam($itemData->getId());
        $queryData->setOnErrorMessage(__u('Error al modificar perfil'));

        DbWrapper::getQuery($queryData, $this->db);

        return $queryData->getQueryNumRows() > 0;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param UserProfileData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $query = /** @lang SQL */
            'SELECT name
            FROM UserProfile
            WHERE UPPER(name) = ?
            AND id <> ?';

        $queryData = new QueryData();
        $queryData->addParam($itemData->getName());
        $queryData->addParam($itemData->getId());
        $queryData->setQuery($query);

        DbWrapper::getQuery($queryData, $this->db);

        return ($queryData->getQueryNumRows() > 0);
    }
}