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

namespace SP;

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
    protected $_userId = 0;
    /**
     * @var string
     */
    protected $_userName = '';
    /**
     * @var int
     */
    protected $_userGroupId = 0;
    /**
     * @var string
     */
    protected $_userGroupName = '';
    /**
     * @var string
     */
    protected $_userLogin = '';
    /**
     * @var string
     */
    protected $_userPass = '';
    /**
     * @var string
     */
    protected $_userEmail = '';
    /**
     * @var string
     */
    protected $_userNotes = '';
    /**
     * @var int
     */
    protected $_userProfileId = 0;
    /**
     * @var bool
     */
    protected $_userIsAdminApp = false;
    /**
     * @var bool
     */
    protected $_userIsAdminAcc = false;
    /**
     * @var bool
     */
    protected $_userIsDisabled = false;
    /**
     * @var bool
     */
    protected $_userIsLdap = false;
    /**
     * @var bool
     */
    protected $_userChangePass = false;

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * @param int $_userId
     */
    public function setUserId($_userId)
    {
        $this->_userId = $_userId;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->_userName;
    }

    /**
     * @param string $_userName
     */
    public function setUserName($_userName)
    {
        $this->_userName = $_userName;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->_userGroupId;
    }

    /**
     * @param int $_userGroupId
     */
    public function setUserGroupId($_userGroupId)
    {
        $this->_userGroupId = $_userGroupId;
    }

    /**
     * @return string
     */
    public function getUserGroupName()
    {
        return $this->_userGroupName;
    }

    /**
     * @param string $_userGroupName
     */
    public function setUserGroupName($_userGroupName)
    {
        $this->_userGroupName = $_userGroupName;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->_userLogin;
    }

    /**
     * @param string $_userLogin
     */
    public function setUserLogin($_userLogin)
    {
        $this->_userLogin = $_userLogin;
    }

    /**
     * @return string
     */
    public function getUserPass()
    {
        return $this->_userPass;
    }

    /**
     * @param string $_userPass
     */
    public function setUserPass($_userPass)
    {
        $this->_userPass = $_userPass;
    }

    /**
     * @return string
     */
    public function getUserEmail()
    {
        return $this->_userEmail;
    }

    /**
     * @param string $_userEmail
     */
    public function setUserEmail($_userEmail)
    {
        $this->_userEmail = $_userEmail;
    }

    /**
     * @return string
     */
    public function getUserNotes()
    {
        return $this->_userNotes;
    }

    /**
     * @param string $_userNotes
     */
    public function setUserNotes($_userNotes)
    {
        $this->_userNotes = $_userNotes;
    }

    /**
     * @return int
     */
    public function getUserProfileId()
    {
        return $this->_userProfileId;
    }

    /**
     * @param int $_userProfileId
     */
    public function setUserProfileId($_userProfileId)
    {
        $this->_userProfileId = $_userProfileId;
    }

    /**
     * @return boolean
     */
    public function isUserIsAdminApp()
    {
        return $this->_userIsAdminApp;
    }

    /**
     * @param boolean $_userIsAdminApp
     */
    public function setUserIsAdminApp($_userIsAdminApp)
    {
        $this->_userIsAdminApp = $_userIsAdminApp;
    }

    /**
     * @return boolean
     */
    public function isUserIsAdminAcc()
    {
        return $this->_userIsAdminAcc;
    }

    /**
     * @param boolean $_userIsAdminAcc
     */
    public function setUserIsAdminAcc($_userIsAdminAcc)
    {
        $this->_userIsAdminAcc = $_userIsAdminAcc;
    }

    /**
     * @return boolean
     */
    public function isUserIsDisabled()
    {
        return $this->_userIsDisabled;
    }

    /**
     * @param boolean $_userIsDisabled
     */
    public function setUserIsDisabled($_userIsDisabled)
    {
        $this->_userIsDisabled = $_userIsDisabled;
    }

    /**
     * @return boolean
     */
    public function isUserIsLdap()
    {
        return $this->_userIsLdap;
    }

    /**
     * @param boolean $_userIsLdap
     */
    public function setUserIsLdap($_userIsLdap)
    {
        $this->_userIsLdap = $_userIsLdap;
    }

    /**
     * @return boolean
     */
    public function isUserChangePass()
    {
        return $this->_userChangePass;
    }

    /**
     * @param boolean $_userChangePass
     */
    public function setUserChangePass($_userChangePass)
    {
        $this->_userChangePass = $_userChangePass;
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
        $passdata = UserPass::makeUserPassHash($this->_userPass);

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

        $data['name'] = $this->_userName;
        $data['login'] = $this->_userLogin;
        $data['email'] = $this->_userEmail;
        $data['notes'] = $this->_userNotes;
        $data['groupId'] = $this->_userGroupId;
        $data['profileId'] = $this->_userProfileId;
        $data['isAdminApp'] = $this->_userIsAdminApp;
        $data['isAdminAcc'] = $this->_userIsAdminAcc;
        $data['isDisabled'] = $this->_userIsDisabled;
        $data['isChangePass'] = $this->_userChangePass;
        $data['pass'] = $passdata['pass'];
        $data['salt'] = $passdata['salt'];

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->_userId = DB::getLastId();

        $Log = new Log(_('Nuevo Usuario'));
        $Log->addDescription(sprintf('%s: %s (%s)', Html::strongText(_('Usuario')), $this->_userName, $this->_userLogin));

        if ($this->_userChangePass) {
            if (!Auth::mailPassRecover(DB::escape($this->_userLogin), DB::escape($this->_userEmail))) {
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

        $data['login'] = $this->_userLogin;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        $this->_userId = intval($queryRes->user_id);
        $this->_userName = $queryRes->user_name;
        $this->_userGroupId = intval($queryRes->user_groupId);
        $this->_userGroupName = $queryRes->usergroup_name;
        $this->_userEmail = $queryRes->user_email;
        $this->_userProfileId = intval($queryRes->user_profileId);
        $this->_userIsAdminApp = intval($queryRes->user_isAdminApp);
        $this->_userIsAdminAcc = intval($queryRes->user_isAdminAcc);
        $this->_userIsLdap = intval($queryRes->user_isLdap);
        $this->_userChangePass = intval($queryRes->user_isChangePass);

        return true;
    }

    /**
     * Comprobar si un usuario/email existen en la BBDD.
     *
     * @return false|int Devuelve bool si error y int si existe el usuario/email
     */
    public function checkUserExist()
    {
        $userLogin = strtoupper($this->_userLogin);
        $userEmail = strtoupper($this->_userEmail);

        $query = 'SELECT user_login, user_email '
            . 'FROM usrData '
            . 'WHERE (UPPER(user_login) = :login '
            . 'OR UPPER(user_email) = :email) '
            . 'AND user_id != :id';

        $data['login'] = $userLogin;
        $data['email'] = $userEmail;
        $data['id'] = $this->_userId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

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

        $data['name'] = $this->_userName;
        $data['login'] = $this->_userLogin;
        $data['email'] = $this->_userEmail;
        $data['notes'] = $this->_userNotes;
        $data['groupId'] = $this->_userGroupId;
        $data['profileId'] = $this->_userProfileId;
        $data['isAdminApp'] = $this->_userIsAdminApp;
        $data['isAdminAcc'] = $this->_userIsAdminAcc;
        $data['isDisabled'] = $this->_userIsDisabled;
        $data['isChangePass'] = $this->_userChangePass;
        $data['id'] = $this->_userId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->queryLastId = DB::$lastId;

        $Log = new Log(_('Modificar Usuario'));
        $Log->addDescription(sprintf('%s: %s (%s)', Html::strongText(_('Usuario')), $this->_userName, $this->_userLogin));

        if ($this->_userChangePass) {
            if (!Auth::mailPassRecover(DB::escape($this->_userLogin), DB::escape($this->_userEmail))) {
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
        $passdata = UserPass::makeUserPassHash($this->_userPass);
        $userLogin = UserUtil::getUserLoginById($this->_userId);

        $query = 'UPDATE usrData SET '
            . 'user_pass = :pass,'
            . 'user_hashSalt = :salt,'
            . 'user_isChangePass = 0,'
            . 'user_lastUpdate = NOW() '
            . 'WHERE user_id = :id LIMIT 1';

        $data['pass'] = $passdata['pass'];
        $data['salt'] = $passdata['salt'];
        $data['id'] = $this->_userId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->queryLastId = DB::$lastId;

        Log::writeNewLogAndEmail(_('Modificar Clave Usuario'), sprintf('%s: %s', Html::strongText(_('Login')), $userLogin));

        return true;
    }

    /**
     * Eliminar un usuario.
     *
     * @return bool
     */
    public function deleteUser()
    {
        $userLogin = UserUtil::getUserLoginById($this->_userId);

        $query = 'DELETE FROM usrData WHERE user_id = :id LIMIT 1';

        $data['id'] = $this->_userId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->queryLastId = DB::$lastId;

        Log::writeNewLogAndEmail(_('Eliminar Usuario'), sprintf('%s: %s', Html::strongText(_('Login')), $userLogin));

        return true;
    }
}