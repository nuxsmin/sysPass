<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace SP\Mgmt\Users;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Auth\Auth;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\Html\Html;
use SP\Log\Email;
use SP\Log\Log;
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
            throw new SPException(SPException::SP_INFO, _('Login/email de usuario duplicados'));
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
            throw new SPException(SPException::SP_ERROR, _('Error al crear el usuario'));
        }

        $this->itemData->setUserId(DB::getLastId());

        $Log = new Log(_('Nuevo Usuario'));
        $Log->addDetails(Html::strongText(_('Usuario')), sprintf('%s (%s)', $this->itemData->getUserName(), $this->itemData->getUserLogin()));

        if ($this->itemData->isUserIsChangePass()) {
            if (!Auth::mailPassRecover($this->itemData)) {
                $Log->addDescription(Html::strongText(_('No se pudo realizar la petición de cambio de clave.')));
            }
        }

        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @param $id int
     * @return $this
     */
    public function delete($id)
    {
        $oldUserData = $this->getById($id);

        $query = 'DELETE FROM usrData WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            new SPException(SPException::SP_ERROR, _('Error al eliminar el usuario'));
        }

        $this->itemData->setUserId(DB::$lastId);

        $Log = new Log(_('Eliminar Usuario'));
        $Log->addDetails(Html::strongText(_('Login')), $oldUserData->getUserLogin());
        $Log->addDetails(Html::strongText(_('Nombre')), $oldUserData->getUserName());
        $Log->writeLog();

        Email::sendEmail($Log);

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
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener los datos del usuario'));
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
            throw new SPException(SPException::SP_INFO, _('Login/email de usuario duplicados'));
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
        $Data->addParam(intval($this->itemData->isUserIsAdminApp()));
        $Data->addParam(intval($this->itemData->isUserIsAdminAcc()));
        $Data->addParam(intval($this->itemData->isUserIsDisabled()));
        $Data->addParam(intval($this->itemData->isUserIsChangePass()));
        $Data->addParam($this->itemData->getUserId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al actualizar el usuario'));
        }

        $this->itemData->setUserId(DB::getLastId());

        $Log = new Log(_('Modificar Usuario'));
        $Log->addDetails(Html::strongText(_('Usuario')), sprintf('%s (%s)', $this->itemData->getUserName(), $this->itemData->getUserLogin()));

        if ($this->itemData->isUserIsChangePass()) {
            if (!Auth::mailPassRecover($this->itemData)) {
                $Log->addDescription(Html::strongText(_('No se pudo realizar la petición de cambio de clave.')));
            }
        }

        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @return bool
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
            throw new SPException(SPException::SP_ERROR, _('Error al obtener los usuarios'));
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
     * @return bool
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
            throw new SPException(SPException::SP_ERROR, _('Error al modificar la clave'));
        }

        $Log = new Log(_('Modificar Clave Usuario'));
        $Log->addDetails(Html::strongText(_('Login')), $UserData->getUserLogin());
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @param $login string
     * @return $this
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
            throw new SPException(SPException::SP_ERROR, _('Error al obtener los datos del usuario'));
        }

        $this->itemData = $queryRes;

        return $this;
    }
}