<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

use SP\Core\Acl\Acl;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserProfileData;
use SP\DataModel\ProfileData;
use SP\Log\Log;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Repositories\RepositoryItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Util;

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
        $query = /** @lang SQL */
            'SELECT login FROM User WHERE userProfileId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResultsArray($Data, $this->db);
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
            throw new SPException(SPException::SP_INFO, __u('Perfil en uso'));
        }

        $query = /** @lang SQL */
            'DELETE FROM UserProfile WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar perfil', false));

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows();
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
        $query = /** @lang SQL */
            'SELECT userProfileId FROM User WHERE userProfileId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DbWrapper::getQuery($Data, $this->db);

        return ($Data->getQueryNumRows() > 0);
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
            'SELECT id, name, profile FROM UserProfile WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(ProfileData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        /**
         * @var UserProfileData $queryRes
         * @var ProfileData     $Profile
         */
        $queryRes = DbWrapper::getResults($Data, $this->db);

        $Profile = Util::unserialize(ProfileData::class, $queryRes->getProfile());
        $Profile->setId($queryRes->getId());
        $Profile->setName($queryRes->getName());

        return $Profile;
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id, name FROM UserProfile ORDER BY name';

        $Data = new QueryData();
        $Data->setMapClassName(UserProfileData::class);
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
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT id, name FROM UserProfile WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName(ProfileData::class);
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     */
    public function deleteByIdBatch(array $ids)
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
        $Data->setSelect('id, name');
        $Data->setFrom('UserProfile');
        $Data->setOrder('name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
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
     * @param ProfileData $itemData
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws SPException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_INFO, __u('Nombre de perfil duplicado'));
        }

        $query = /** @lang SQL */
            'INSERT INTO UserProfile SET
            name = ?,
            profile = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam(serialize($itemData));
        $Data->setOnErrorMessage(__('Error al crear perfil', false));

        DbWrapper::getQuery($Data, $this->db);

        return $this->db->getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param ProfileData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT name
            FROM UserProfile
            WHERE UPPER(name) = ?';

        $Data = new QueryData();
        $Data->addParam($itemData->getName());
        $Data->setQuery($query);

        DbWrapper::getQuery($Data, $this->db);

        return ($Data->getQueryNumRows() > 0);
    }

    /**
     * Updates an item
     *
     * @param ProfileData $itemData
     * @return bool
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(SPException::SP_INFO, __u('Nombre de perfil duplicado'));
        }

        $query = /** @lang SQL */
            'UPDATE UserProfile SET name = ?, profile = ? WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getName());
        $Data->addParam(serialize($itemData));
        $Data->addParam($itemData->getId());
        $Data->setOnErrorMessage(__u('Error al modificar perfil'));

        DbWrapper::getQuery($Data, $this->db);

//        if ($Data->getQueryNumRows() > 0) {
//            $this->updateSessionProfile();
//        }

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param ProfileData $itemData
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

        $Data = new QueryData();
        $Data->addParam($itemData->getName());
        $Data->addParam($itemData->getId());
        $Data->setQuery($query);

        DbWrapper::getQuery($Data, $this->db);

        return ($Data->getQueryNumRows() > 0);
    }

    /**
     * Logs profile action
     *
     * @param int $id
     * @param int $actionId
     * @return \SP\Core\Messages\LogMessage
     */
    public function logAction($id, $actionId)
    {
        $query = /** @lang SQL */
            'SELECT name FROM UserProfile WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $userprofile = DbWrapper::getResults($Data, $this->db);

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(Acl::getActionInfo($actionId));
        $LogMessage->addDetails(__u('Perfil'), $userprofile->name);
        $LogMessage->addDetails(__u('ID'), $id);
        $Log->writeLog();

        return $LogMessage;
    }
}