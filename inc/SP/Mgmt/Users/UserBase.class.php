<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Auth\Auth;
use SP\Html\Html;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\DBUtil;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class UserBase
 *
 * @package SP
 */
abstract class UserBase
{
    /**
     * @var int
     */
    var $queryLastId = 0;
    /**
     * @var int
     */
    protected $userId = 0;
    /**
     * @var string
     */
    protected $userName = '';
    /**
     * @var int
     */
    protected $userGroupId = 0;
    /**
     * @var string
     */
    protected $userGroupName = '';
    /**
     * @var string
     */
    protected $userLogin = '';
    /**
     * @var string
     */
    protected $userPass = '';
    /**
     * @var string
     */
    protected $userEmail = '';
    /**
     * @var string
     */
    protected $userNotes = '';
    /**
     * @var int
     */
    protected $userProfileId = 0;
    /**
     * @var bool
     */
    protected $userIsAdminApp = false;
    /**
     * @var bool
     */
    protected $userIsAdminAcc = false;
    /**
     * @var bool
     */
    protected $userIsDisabled = false;
    /**
     * @var bool
     */
    protected $userIsLdap = false;
    /**
     * @var bool
     */
    protected $userChangePass = false;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->userGroupId;
    }

    /**
     * @param int $userGroupId
     */
    public function setUserGroupId($userGroupId)
    {
        $this->userGroupId = $userGroupId;
    }

    /**
     * @return string
     */
    public function getUserGroupName()
    {
        return $this->userGroupName;
    }

    /**
     * @param string $userGroupName
     */
    public function setUserGroupName($userGroupName)
    {
        $this->userGroupName = $userGroupName;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->userLogin;
    }

    /**
     * @param string $userLogin
     */
    public function setUserLogin($userLogin)
    {
        $this->userLogin = $userLogin;
    }

    /**
     * @return string
     */
    public function getUserPass()
    {
        return $this->userPass;
    }

    /**
     * @param string $userPass
     */
    public function setUserPass($userPass)
    {
        $this->userPass = $userPass;
    }

    /**
     * @return string
     */
    public function getUserEmail()
    {
        return $this->userEmail;
    }

    /**
     * @param string $userEmail
     */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
    }

    /**
     * @return string
     */
    public function getUserNotes()
    {
        return $this->userNotes;
    }

    /**
     * @param string $userNotes
     */
    public function setUserNotes($userNotes)
    {
        $this->userNotes = $userNotes;
    }

    /**
     * @return int
     */
    public function getUserProfileId()
    {
        return $this->userProfileId;
    }

    /**
     * @param int $userProfileId
     */
    public function setUserProfileId($userProfileId)
    {
        $this->userProfileId = $userProfileId;
    }

    /**
     * @return boolean
     */
    public function isUserIsAdminApp()
    {
        return $this->userIsAdminApp;
    }

    /**
     * @param boolean $userIsAdminApp
     */
    public function setUserIsAdminApp($userIsAdminApp)
    {
        $this->userIsAdminApp = $userIsAdminApp;
    }

    /**
     * @return boolean
     */
    public function isUserIsAdminAcc()
    {
        return $this->userIsAdminAcc;
    }

    /**
     * @param boolean $userIsAdminAcc
     */
    public function setUserIsAdminAcc($userIsAdminAcc)
    {
        $this->userIsAdminAcc = $userIsAdminAcc;
    }

    /**
     * @return boolean
     */
    public function isUserIsDisabled()
    {
        return $this->userIsDisabled;
    }

    /**
     * @param boolean $userIsDisabled
     */
    public function setUserIsDisabled($userIsDisabled)
    {
        $this->userIsDisabled = $userIsDisabled;
    }

    /**
     * @return boolean
     */
    public function isUserIsLdap()
    {
        return $this->userIsLdap;
    }

    /**
     * @param boolean $userIsLdap
     */
    public function setUserIsLdap($userIsLdap)
    {
        $this->userIsLdap = $userIsLdap;
    }

    /**
     * @return boolean
     */
    public function isUserChangePass()
    {
        return $this->userChangePass;
    }

    /**
     * @param boolean $userChangePass
     */
    public function setUserChangePass($userChangePass)
    {
        $this->userChangePass = $userChangePass;
    }

    /**
     * @return int
     */
    public function getQueryLastId()
    {
        return $this->queryLastId;
    }

    /**
     * @param int $queryLastId
     */
    public function setQueryLastId($queryLastId)
    {
        $this->queryLastId = $queryLastId;
    }

    /**
     * Crear un usuario.
     *
     * @return bool
     */
    public function addUser()
    {
        $passdata = UserPass::makeUserPassHash($this->userPass);

        $query = 'INSERT INTO usrData SET '
            . 'user_name = :name,'
            . 'user_login = :login,'
            . 'user_email = :email,'
            . 'user_notes = :notes,'
            . 'user_groupId = :groupId,'
            . 'user_profileId = :profileId,'
            . 'user_mPass = \'\','
            . 'user_mIV = \'\','
            . 'user_isAdminApp = :isAdminApp,'
            . 'user_isAdminAcc = :isAdminAcc,'
            . 'user_isDisabled = :isDisabled,'
            . 'user_isChangePass = :isChangePass,'
            . 'user_pass = :pass,'
            . 'user_hashSalt = :salt,'
            . 'user_isLdap = 0';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->userName, 'name');
        $Data->addParam($this->userLogin, 'login');
        $Data->addParam($this->userEmail, 'email');
        $Data->addParam($this->userNotes, 'notes');
        $Data->addParam($this->userGroupId, 'groupId');
        $Data->addParam($this->userProfileId, 'profileId');
        $Data->addParam(intval($this->userIsAdminApp), 'isAdminApp');
        $Data->addParam(intval($this->userIsAdminAcc), 'isAdminAcc');
        $Data->addParam(intval($this->userIsDisabled), 'isDisabled');
        $Data->addParam(intval($this->userChangePass), 'isChangePass');
        $Data->addParam($passdata['pass'], 'pass');
        $Data->addParam($passdata['salt'], 'salt');

        if (DB::getQuery($Data) === false) {
            return false;
        }

        $this->userId = DB::getLastId();

        $Log = new Log(_('Nuevo Usuario'));
        $Log->addDetails(Html::strongText(_('Usuario')), sprintf('%s (%s)', $this->userName, $this->userLogin));

        if ($this->userChangePass) {
            if (!Auth::mailPassRecover(DBUtil::escape($this->userLogin), DBUtil::escape($this->userEmail))) {
                $Log->addDescription(Html::strongText(_('No se pudo realizar la petición de cambio de clave.')));
            }
        }

        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Obtener los datos de un usuario desde la BBDD.
     * Esta función obtiene los datos de un usuario y los guarda en las variables de la clase.
     *
     * @return bool
     */
    public function getUserInfo()
    {
        $query = 'SELECT user_id,'
            . 'user_name,'
            . 'user_groupId,'
            . 'user_login,'
            . 'user_email,'
            . 'user_notes,'
            . 'user_count,'
            . 'user_profileId,'
            . 'usergroup_name,'
            . 'BIN(user_isAdminApp) AS user_isAdminApp,'
            . 'BIN(user_isAdminAcc) AS user_isAdminAcc,'
            . 'BIN(user_isLdap) AS user_isLdap,'
            . 'BIN(user_isDisabled) AS user_isDisabled,'
            . 'BIN(user_isChangePass) AS user_isChangePass '
            . 'FROM usrData '
            . 'LEFT JOIN usrGroups ON user_groupId = usergroup_id '
            . 'LEFT JOIN usrProfiles ON user_profileId = userprofile_id '
            . 'WHERE user_login = :login LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->userLogin, 'login');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        $this->userId = intval($queryRes->user_id);
        $this->userName = $queryRes->user_name;
        $this->userGroupId = intval($queryRes->user_groupId);
        $this->userGroupName = $queryRes->usergroup_name;
        $this->userEmail = $queryRes->user_email;
        $this->userProfileId = intval($queryRes->user_profileId);
        $this->userIsAdminApp = intval($queryRes->user_isAdminApp);
        $this->userIsAdminAcc = intval($queryRes->user_isAdminAcc);
        $this->userIsLdap = intval($queryRes->user_isLdap);
        $this->userChangePass = intval($queryRes->user_isChangePass);

        return true;
    }

    /**
     * Comprobar si un usuario/email existen en la BBDD.
     *
     * @return false|int Devuelve bool si error y int si existe el usuario/email
     */
    public function checkUserExist()
    {
        $userLogin = strtoupper($this->userLogin);
        $userEmail = strtoupper($this->userEmail);

        $query = 'SELECT user_login, user_email '
            . 'FROM usrData '
            . 'WHERE (UPPER(user_login) = :login '
            . 'OR UPPER(user_email) = :email) '
            . 'AND user_id != :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->userLogin, 'login');
        $Data->addParam($this->userEmail, 'email');
        $Data->addParam($this->userId, 'id');

        DB::setReturnArray();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $userData) {
            $resULogin = strtoupper($userData->user_login);
            $resUEmail = strtoupper($userData->user_email);

            if ($resULogin == $userLogin) {
                return UserUtil::USER_LOGIN_EXIST;
            } elseif ($resUEmail == $userEmail) {
                return UserUtil::USER_MAIL_EXIST;
            }
        }
    }

    /**
     * Modificar un usuario.
     *
     * @return bool
     */
    public function updateUser()
    {
        $query = 'UPDATE usrData SET '
            . 'user_name = :name,'
            . 'user_login = :login,'
            . 'user_email = :email,'
            . 'user_notes = :notes,'
            . 'user_groupId = :groupId,'
            . 'user_profileId = :profileId,'
            . 'user_isAdminApp = :isAdminApp,'
            . 'user_isAdminAcc = :isAdminAcc,'
            . 'user_isDisabled = :isDisabled,'
            . 'user_isChangePass = :isChangePass,'
            . 'user_lastUpdate = NOW() '
            . 'WHERE user_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->userName, 'name');
        $Data->addParam($this->userLogin, 'login');
        $Data->addParam($this->userEmail, 'email');
        $Data->addParam($this->userNotes, 'notes');
        $Data->addParam($this->userGroupId, 'groupId');
        $Data->addParam($this->userProfileId, 'profileId');
        $Data->addParam(intval($this->userIsAdminApp), 'isAdminApp');
        $Data->addParam(intval($this->userIsAdminAcc), 'isAdminAcc');
        $Data->addParam(intval($this->userIsDisabled), 'isDisabled');
        $Data->addParam(intval($this->userChangePass), 'isChangePass');
        $Data->addParam($this->userId, 'id');

        if (DB::getQuery($Data) === false) {
            return false;
        }

        $this->queryLastId = DB::$lastId;

        $Log = new Log(_('Modificar Usuario'));
        $Log->addDetails(Html::strongText(_('Usuario')), sprintf('%s (%s)', $this->userName, $this->userLogin));

        if ($this->userChangePass) {
            if (!Auth::mailPassRecover(DBUtil::escape($this->userLogin), DBUtil::escape($this->userEmail))) {
                $Log->addDescription(Html::strongText(_('No se pudo realizar la petición de cambio de clave.')));
            }
        }

        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Modificar la clave de un usuario.
     *
     * @return bool
     */
    public function updateUserPass()
    {
        $passdata = UserPass::makeUserPassHash($this->userPass);
        $userLogin = UserUtil::getUserLoginById($this->userId);

        $query = 'UPDATE usrData SET '
            . 'user_pass = :pass,'
            . 'user_hashSalt = :salt,'
            . 'user_isChangePass = 0,'
            . 'user_lastUpdate = NOW() '
            . 'WHERE user_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->userId, 'id');
        $Data->addParam($passdata['pass'], 'pass');
        $Data->addParam($passdata['salt'], 'salt');

        if (DB::getQuery($Data) === false) {
            return false;
        }

        $this->queryLastId = DB::$lastId;

        $Log = new Log(_('Modificar Clave Usuario'));
        $Log->addDetails(Html::strongText(_('Login')), $userLogin);
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Eliminar un usuario.
     *
     * @return bool
     */
    public function deleteUser()
    {
        $userLogin = UserUtil::getUserLoginById($this->userId);

        $query = 'DELETE FROM usrData WHERE user_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->userId, 'id');

        if (DB::getQuery($Data) === false) {
            return false;
        }

        $this->queryLastId = DB::$lastId;

        $Log = new Log(_('Eliminar Usuario'));
        $Log->addDetails(Html::strongText(_('Login')), $userLogin);
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }
}