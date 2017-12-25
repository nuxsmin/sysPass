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

namespace SP\Services\UserProfile;

use SP\Core\Acl\Acl;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\ProfileBaseData;
use SP\DataModel\ProfileData;
use SP\Log\Log;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class UserProfileService
 *
 * @package SP\Services\UserProfile
 */
class UserProfileService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

    /**
     * Obtener el nombre de los usuarios que usan un perfil.
     *
     * @param $id int El id del perfil
     * @return array
     */
    public function getUsersForProfile($id)
    {
        $query = /** @lang SQL */
            'SELECT user_login FROM usrData WHERE user_profileId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return mixed
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
            'DELETE FROM usrProfiles WHERE userprofile_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar perfil', false));

        DbWrapper::getQuery($Data, $this->db);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Perfil no encontrado'));
        }

        return $this;
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
            'SELECT user_profileId FROM usrData WHERE user_profileId = ?';

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
            'SELECT userprofile_id,
            userprofile_name,
            userprofile_profile
            FROM usrProfiles
            WHERE userprofile_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(ProfileData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        /**
         * @var ProfileBaseData $queryRes
         * @var ProfileData     $Profile
         */
        $queryRes = DbWrapper::getResults($Data, $this->db);

        $Profile = Util::unserialize(ProfileData::class, $queryRes->getUserprofileProfile());
        $Profile->setUserprofileId($queryRes->getUserprofileId());
        $Profile->setUserprofileName($queryRes->getUserprofileName());

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
            'SELECT userprofile_id, userprofile_name
                FROM usrProfiles
                ORDER BY userprofile_name';

        $Data = new QueryData();
        $Data->setMapClassName(ProfileBaseData::class);
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
            'SELECT userprofile_id,
            userprofile_name
            FROM usrProfiles
            WHERE userprofile_id IN (' . $this->getParamsFromArray($ids) . ')';

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
     * @return $this
     */
    public function deleteByIdBatch(array $ids)
    {
        // TODO: Implement deleteByIdBatch() method.
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
        $Data->setSelect('userprofile_id, userprofile_name');
        $Data->setFrom('usrProfiles');
        $Data->setOrder('userprofile_name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('userprofile_name LIKE ?');

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
            'INSERT INTO usrProfiles SET
            userprofile_name = ?,
            userprofile_profile = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUserprofileName());
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
            'SELECT userprofile_name
            FROM usrProfiles
            WHERE UPPER(userprofile_name) = ?';

        $Data = new QueryData();
        $Data->addParam($itemData->getUserprofileName());
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
            'UPDATE usrProfiles SET
          userprofile_name = ?,
          userprofile_profile = ?
          WHERE userprofile_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUserprofileName());
        $Data->addParam(serialize($itemData));
        $Data->addParam($itemData->getUserprofileId());
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
            'SELECT userprofile_name
            FROM usrProfiles
            WHERE UPPER(userprofile_name) = ?
            AND userprofile_id <> ?';

        $Data = new QueryData();
        $Data->addParam($itemData->getUserprofileName());
        $Data->addParam($itemData->getUserprofileId());
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
            'SELECT userprofile_name FROM usrProfiles WHERE userprofile_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $userprofile = DbWrapper::getResults($Data, $this->db);

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(Acl::getActionInfo($actionId));
        $LogMessage->addDetails(__u('Perfil'), $userprofile->userprofile_name);
        $LogMessage->addDetails(__u('ID'), $id);
        $Log->writeLog();

        return $LogMessage;
    }
}