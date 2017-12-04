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

namespace SP\Services\User;


use SP\Core\Acl\Acl;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\UserData;
use SP\Log\Log;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class UserService
 *
 * @package SP\Services\User
 */
class UserService extends Service implements ServiceItemInterface
{

    /**
     * Updates an item
     *
     * @param UserData $itemData
     * @return mixed
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update($itemData)
    {
        if ($this->checkDuplicatedOnUpdate($itemData)) {
            throw new SPException(SPException::SP_INFO, __u('Login/email de usuario duplicados'));
        }

        $query = /** @lang SQL */
            'UPDATE usrData SET
            user_name = ?,
            user_login = ?,
            user_ssoLogin = ?,
            user_email = ?,
            user_notes = ?,
            user_groupId = ?,
            user_profileId = ?,
            user_isAdminApp = ?,
            user_isAdminAcc = ?,
            user_isDisabled = ?,
            user_isChangePass = ?,
            user_lastUpdate = NOW()
            WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUserName());
        $Data->addParam($itemData->getUserLogin());
        $Data->addParam($itemData->getUserSsoLogin());
        $Data->addParam($itemData->getUserEmail());
        $Data->addParam($itemData->getUserNotes());
        $Data->addParam($itemData->getUserGroupId());
        $Data->addParam($itemData->getUserProfileId());
        $Data->addParam($itemData->isUserIsAdminApp());
        $Data->addParam($itemData->isUserIsAdminAcc());
        $Data->addParam($itemData->isUserIsDisabled());
        $Data->addParam($itemData->isUserIsChangePass());
        $Data->addParam($itemData->getUserId());
        $Data->setOnErrorMessage(__u('Error al actualizar el usuario'));

        DbWrapper::getQuery($Data);

        if ($Data->getQueryNumRows() > 0) {
            $itemData->setUserId(DbWrapper::getLastId());
        }

        return $this;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param UserData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        $query = /** @lang SQL */
            'SELECT user_login, user_email
            FROM usrData
            WHERE (UPPER(user_login) = UPPER(?) 
            OR UPPER(user_ssoLogin) = UPPER(?) 
            OR UPPER(user_email) = UPPER(?))
            AND user_id <> ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUserLogin());
        $Data->addParam($itemData->getUserSsoLogin());
        $Data->addParam($itemData->getUserEmail());
        $Data->addParam($itemData->getUserId());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * Updates an user's pass
     *
     * @param UserData $itemData
     * @return $this
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function updatePass($itemData)
    {
        $query = /** @lang SQL */
            'UPDATE usrData SET
            user_pass = ?,
            user_hashSalt = \'\',
            user_isChangePass = 0,
            user_isChangedPass = 1,
            user_lastUpdate = NOW()
            WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(Hash::hashKey($itemData->getUserPass()));
        $Data->addParam($itemData->getUserId());
        $Data->setOnErrorMessage(__u('Error al modificar la clave'));

        DbWrapper::getQuery($Data);

        return $this;
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return int
     * @throws SPException
     */
    public function delete($id)
    {
        $query = 'DELETE FROM usrData WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__u('Error al eliminar el usuario'));

        DbWrapper::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __u('Usuario no encontrado'));
        }

        return DbWrapper::$lastId;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT user_id,
            user_name,
            user_groupId,
            usergroup_name,
            user_login,
            user_ssoLogin,
            user_email,
            user_notes,
            user_count,
            user_profileId,
            user_count,
            user_lastLogin,
            user_lastUpdate,
            user_lastUpdateMPass,
            user_preferences,
            user_pass,
            user_hashSalt,
            user_mPass,
            user_mKey,            
            BIN(user_isAdminApp) AS user_isAdminApp,
            BIN(user_isAdminAcc) AS user_isAdminAcc,
            BIN(user_isLdap) AS user_isLdap,
            BIN(user_isDisabled) AS user_isDisabled,
            BIN(user_isChangePass) AS user_isChangePass,
            BIN(user_isChangedPass) AS user_isChangedPass,
            BIN(user_isMigrate) AS user_isMigrate
            FROM usrData
            JOIN usrGroups ON usergroup_id = user_groupId
            WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(UserData::class);
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DbWrapper::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('Error al obtener los datos del usuario'));
        }

        return $queryRes;
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        // TODO: Implement getByIdBatch() method.
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
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return bool
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return array
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('user_id,
            user_name, 
            user_login,
            userprofile_name,
            usergroup_name,
            BIN(user_isAdminApp) AS user_isAdminApp,
            BIN(user_isAdminAcc) AS user_isAdminAcc,
            BIN(user_isLdap) AS user_isLdap,
            BIN(user_isDisabled) AS user_isDisabled,
            BIN(user_isChangePass) AS user_isChangePass');
        $Data->setFrom('usrData LEFT JOIN usrProfiles ON user_profileId = userprofile_id LEFT JOIN usrGroups ON usrData.user_groupId = usergroup_id');
        $Data->setOrder('user_name');

        if ($SearchData->getSeachString() !== '') {
            if ($this->session->getUserData()->isUserIsAdminApp()) {
                $Data->setWhere('user_name LIKE ? OR user_login LIKE ?');
            } else {
                $Data->setWhere('user_name LIKE ? OR user_login LIKE ? AND user_isAdminApp = 0');
            }

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        } elseif (!$this->session->getUserData()->isUserIsAdminApp()) {
            $Data->setWhere('user_isAdminApp = 0');
        }

        $Data->setLimit('?, ?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Logs user action
     *
     * @param int $id
     * @param int $actionId
     * @return \SP\Core\Messages\LogMessage
     */
    public function logAction($id, $actionId)
    {
        $query = /** @lang SQL */
            'SELECT user_id, user_login, user_name FROM usrData WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $user = DbWrapper::getResults($Data);

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(Acl::getActionInfo($actionId));
        $LogMessage->addDetails(__u('Usuario'), sprintf('%s (%s)', $user->user_name, $user->user_login));
        $LogMessage->addDetails(__u('ID'), $id);
        $Log->writeLog();

        return $LogMessage;
    }

    /**
     * Creates an item
     *
     * @param UserData $itemData
     * @return mixed
     * @throws SPException
     */
    public function create($itemData)
    {
        if ($this->checkDuplicatedOnAdd($itemData)) {
            throw new SPException(SPException::SP_INFO, __u('Login/email de usuario duplicados'));
        }

        $query = /** @lang SQL */
            'INSERT INTO usrData SET
            user_name = ?,
            user_login = ?,
            user_ssoLogin = ?,
            user_email = ?,
            user_notes = ?,
            user_groupId = ?,
            user_profileId = ?,
            user_mPass = \'\',
            user_mKey = \'\',
            user_isAdminApp = ?,
            user_isAdminAcc = ?,
            user_isDisabled = ?,
            user_isChangePass = ?,
            user_isLdap = 0,
            user_pass = ?,
            user_hashSalt = \'\'';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUserName());
        $Data->addParam($itemData->getUserLogin());
        $Data->addParam($itemData->getUserSsoLogin());
        $Data->addParam($itemData->getUserEmail());
        $Data->addParam($itemData->getUserNotes());
        $Data->addParam($itemData->getUserGroupId());
        $Data->addParam($itemData->getUserProfileId());
        $Data->addParam($itemData->isUserIsAdminApp());
        $Data->addParam($itemData->isUserIsAdminAcc());
        $Data->addParam($itemData->isUserIsDisabled());
        $Data->addParam($itemData->isUserIsChangePass());
        $Data->addParam(Hash::hashKey($itemData->getUserPass()));
        $Data->setOnErrorMessage(__u('Error al crear el usuario'));

        DbWrapper::getQuery($Data);

        return DbWrapper::getLastId();
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param UserData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        $query = /** @lang SQL */
            'SELECT user_login, user_email
            FROM usrData
            WHERE UPPER(user_login) = UPPER(?) 
            OR UPPER(user_ssoLogin) = UPPER(?) 
            OR UPPER(user_email) = UPPER(?)';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getUserLogin());
        $Data->addParam($itemData->getUserSsoLogin());
        $Data->addParam($itemData->getUserEmail());

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }
}