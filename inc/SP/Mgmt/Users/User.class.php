<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Mgmt\Users;

defined('APP_ROOT') || die();

use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class User
 *
 * @package SP
 */
class User extends UserBase implements ItemInterface, ItemSelectInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_INFO, __('Login/email de usuario duplicados', false));
        }

        $passdata = UserPass::makeUserPassHash($this->itemData->getUserPass());

        $query = /** @lang SQL */
            'INSERT INTO usrData SET
            user_name = ?,
            user_login = ?,
            user_email = ?,
            user_notes = ?,
            user_groupId = ?,
            user_profileId = ?,
            user_mPass = \'\',
            user_mIV = \'\',
            user_isAdminApp = ?,
            user_isAdminAcc = ?,
            user_isDisabled = ?,
            user_isChangePass = ?,
            user_isLdap = 0,
            user_pass = ?,
            user_hashSalt = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserName());
        $Data->addParam($this->itemData->getUserLogin());
        $Data->addParam($this->itemData->getUserEmail());
        $Data->addParam($this->itemData->getUserNotes());
        $Data->addParam($this->itemData->getUserGroupId());
        $Data->addParam($this->itemData->getUserProfileId());
        $Data->addParam($this->itemData->isUserIsAdminApp());
        $Data->addParam($this->itemData->isUserIsAdminAcc());
        $Data->addParam($this->itemData->isUserIsDisabled());
        $Data->addParam($this->itemData->isUserIsChangePass());
        $Data->addParam($passdata['pass']);
        $Data->addParam($passdata['salt']);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al crear el usuario', false));
        }

        $this->itemData->setUserId(DB::getLastId());

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT user_login, user_email
            FROM usrData
            WHERE UPPER(user_login) = UPPER(?) OR UPPER(user_email) = UPPER(?)';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserLogin());
        $Data->addParam($this->itemData->getUserEmail());

        return (DB::getQuery($Data) === false || $Data->getQueryNumRows() > 0);
    }

    /**
     * @param $id int|array
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if (is_array($id)) {
            foreach ($id as $itemId) {
                $this->delete($itemId);
            }

            return $this;
        }

        $oldUserData = $this->getById($id);

        $query = 'DELETE FROM usrData WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            new SPException(SPException::SP_ERROR, __('Error al eliminar el usuario', false));
        }

        $this->itemData->setUserId(DB::$lastId);

        return $this;
    }

    /**
     * @param $id int
     * @return UserData
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT user_id,
            user_name,
            user_groupId,
            usergroup_name,
            user_login,
            user_email,
            user_notes,
            user_count,
            user_profileId,
            user_count,
            user_lastLogin,
            user_lastUpdate,
            user_lastUpdateMPass,
            user_preferences,
            BIN(user_isAdminApp) AS user_isAdminApp,
            BIN(user_isAdminAcc) AS user_isAdminAcc,
            BIN(user_isLdap) AS user_isLdap,
            BIN(user_isDisabled) AS user_isDisabled,
            BIN(user_isChangePass) AS user_isChangePass,
            BIN(user_isMigrate) AS user_isMigrate
            FROM usrData
            JOIN usrGroups ON usergroup_id = user_groupId
            WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();

        if (is_object($this->itemData)) {
            $Data->setMapClass($this->itemData);
        } else {
            $Data->setMapClassName($this->getDataModel());
        }

        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al obtener los datos del usuario', false));
        }

        return $queryRes;
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()) {
            throw new SPException(SPException::SP_INFO, __('Login/email de usuario duplicados', false));
        }

        $query = /** @lang SQL */
            'UPDATE usrData SET
            user_name = ?,
            user_login = ?,
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
        $Data->addParam($this->itemData->getUserName());
        $Data->addParam($this->itemData->getUserLogin());
        $Data->addParam($this->itemData->getUserEmail());
        $Data->addParam($this->itemData->getUserNotes());
        $Data->addParam($this->itemData->getUserGroupId());
        $Data->addParam($this->itemData->getUserProfileId());
        $Data->addParam((int)$this->itemData->isUserIsAdminApp());
        $Data->addParam((int)$this->itemData->isUserIsAdminAcc());
        $Data->addParam((int)$this->itemData->isUserIsDisabled());
        $Data->addParam((int)$this->itemData->isUserIsChangePass());
        $Data->addParam($this->itemData->getUserId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al actualizar el usuario', false));
        }

        $this->itemData->setUserId(DB::getLastId());

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT user_login, user_email
            FROM usrData
            WHERE (UPPER(user_login) = UPPER(?) OR UPPER(user_email) = UPPER(?))
            AND user_id <> ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserLogin());
        $Data->addParam($this->itemData->getUserEmail());
        $Data->addParam($this->itemData->getUserId());

        return (DB::getQuery($Data) === false || $Data->getQueryNumRows() > 0);
    }

    /**
     * @return UserData[]
     * @throws SPException
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT user_id,
            user_name,
            user_groupId,
            user_login,
            user_email,
            user_notes,
            user_count,
            user_profileId,
            user_preferences,
            BIN(user_isAdminApp) AS user_isAdminApp,
            BIN(user_isAdminAcc) AS user_isAdminAcc,
            BIN(user_isLdap) AS user_isLdap,
            BIN(user_isDisabled) AS user_isDisabled,
            BIN(user_isChangePass) AS user_isChangePass
            FROM usrData';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);


        try {
            $queryRes = DB::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_ERROR, __('Error al obtener los usuarios', false));
        }

        return $queryRes;
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function updatePass()
    {
        $passdata = UserPass::makeUserPassHash($this->itemData->getUserPass());
        $UserData = $this->getById($this->itemData->getUserId());

        $query = /** @lang SQL */
            'UPDATE usrData SET
            user_pass = ?,
            user_hashSalt = ?,
            user_isChangePass = 0,
            user_lastUpdate = NOW()
            WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($passdata['pass']);
        $Data->addParam($passdata['salt']);
        $Data->addParam($this->itemData->getUserId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al modificar la clave', false));
        }

        return $this;
    }

    /**
     * @param $login string
     * @return UserData
     * @throws SPException
     */
    public function getByLogin($login)
    {
        $query = /** @lang SQL */
            'SELECT user_id,
            user_name,
            user_groupId,
            usergroup_name,
            user_login,
            user_email,
            user_notes,
            user_count,
            user_profileId,
            user_count,
            user_lastLogin,
            user_lastUpdate,
            user_lastUpdateMPass,
            user_preferences,
            BIN(user_isAdminApp) AS user_isAdminApp,
            BIN(user_isAdminAcc) AS user_isAdminAcc,
            BIN(user_isLdap) AS user_isLdap,
            BIN(user_isDisabled) AS user_isDisabled,
            BIN(user_isChangePass) AS user_isChangePass,
            BIN(user_isDisabled) AS user_isDisabled,
            BIN(user_isMigrate) AS user_isMigrate
            FROM usrData
            JOIN usrGroups ON usergroup_id = user_groupId
            WHERE user_login = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($login);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __('Error al obtener los datos del usuario', false));
        }

        return $queryRes;
    }
}