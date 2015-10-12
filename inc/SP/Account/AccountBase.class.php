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

namespace SP\Account;

use SP\Mgmt\User\Groups;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase abstracta para definición de métodos comunes a las cuentas
 */
abstract class AccountBase
{
    /**
     * Tiempo de expiración de la caché de ACLde usuarios/grupos de cuentas
     */
    const CACHE_EXPIRE_TIME = 300;
    /**
     * @var int Id de la cuenta padre.
     */
    private $_accountParentId;
    /**
     * @var string Hash con los datos de la cuenta para verificación de cambios.
     */
    private $_accountModHash;
    /**
     * @var int Indica si la cuenta es un registro del hitórico.
     */
    private $_accountIsHistory = 0;
    /**
     * @var int Id de la cuenta.
     */
    private $_accountId;
    /**
     * @var int Id del usuario principal de la cuenta.
     */
    private $_accountUserId;
    /**
     * @var array Los Ids de los usuarios secundarios de la cuenta.
     */
    private $_accountUsersId;
    /**
     * @var array Id del grupo principal de la cuenta.
     */
    private $_accountUserGroupId;
    /**
     * @var array Los Ids de los grupos secundarios de la cuenta.
     */
    private $_accountUserGroupsId;
    /**
     * @var int Id del usuario que editó la cuenta.
     */
    private $_accountUserEditId;
    /**
     * @var string El nombre de la cuenta.
     */
    private $_accountName;
    /**
     * @var int Id del cliente de la cuenta.
     */
    private $_accountCustomerId;
    /**
     * @var int Id de la categoría de la cuenta.
     */
    private $_accountCategoryId;
    /**
     * @var string El nombre de usuario de la cuenta.
     */
    private $_accountLogin;
    /**
     * @var string La URL de la cuenta.
     */
    private $_accountUrl;
    /**
     * @var string La clave de la cuenta.
     */
    private $_accountPass;
    /**
     * @var string El vector de inicialización de la cuenta.
     */
    private $_accountIV;
    /**
     * @var string Las nosta de la cuenta.
     */
    private $_accountNotes;
    /**
     * @var bool Si se permite la edición por los usuarios secundarios.
     */
    private $_accountOtherUserEdit;
    /**
     * @var bool Si se permita la edición por los grupos secundarios.
     */
    private $_accountOtherGroupEdit;
    /**
     * @var array Los Ids de los grupos con acceso a la cuenta
     */
    private $_cacheUserGroupsId;
    /**
     * @var array Los Ids de los usuarios con acceso a la cuenta
     */
    private $_cacheUsersId;

    /**
     * Constructor
     *
     * @param int $id con el Id de la cuenta a obtener
     */
    public function __construct($id = null)
    {
        if (!is_null($id)) {
            $this->setAccountId($id);
        }
    }

    /**
     * @return int
     */
    public function getAccountUserEditId()
    {
        return $this->_accountUserEditId;
    }

    /**
     * @param int $accountUserEditId
     */
    public function setAccountUserEditId($accountUserEditId)
    {
        $this->_accountUserEditId = $accountUserEditId;
    }

    /**
     * @return string
     */
    public function getAccountPass()
    {
        return $this->_accountPass;
    }

    /**
     * @param string $accountPass
     */
    public function setAccountPass($accountPass)
    {
        $this->_accountPass = $accountPass;
    }

    /**
     * @return string
     */
    public function getAccountIV()
    {
        return $this->_accountIV;
    }

    /**
     * @param string $accountIV
     */
    public function setAccountIV($accountIV)
    {
        $this->_accountIV = $accountIV;
    }

    /**
     * @return int
     */
    public function getAccountIsHistory()
    {
        return $this->_accountIsHistory;
    }

    /**
     * @param int $accountIsHistory
     */
    public function setAccountIsHistory($accountIsHistory)
    {
        $this->_accountIsHistory = $accountIsHistory;
    }

    /**
     * @return int
     */
    public function getAccountParentId()
    {
        return $this->_accountParentId;
    }

    /**
     * @param int $accountParentId
     */
    public function setAccountParentId($accountParentId)
    {
        $this->_accountParentId = $accountParentId;
    }

    /**
     * Devolver datos de la cuenta para comprobación de accesos.
     *
     * @param int $accountId con el id de la cuenta
     * @return array con los datos de la cuenta
     */
    public function getAccountDataForACL($accountId = null)
    {
        $accId = (!is_null($accountId)) ? $accountId : $this->getAccountId();

        return array(
            'id' => $accId,
            'user_id' => $this->getAccountUserId(),
            'group_id' => $this->getAccountUserGroupId(),
            'users_id' => $this->getUsersAccount(),
            'groups_id' => $this->getGroupsAccount(),
            'otheruser_edit' => $this->getAccountOtherUserEdit(),
            'othergroup_edit' => $this->getAccountOtherGroupEdit()
        );
    }

    /**
     * @return int|null
     */
    public function getAccountId()
    {
        return $this->_accountId;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId)
    {
        $this->_accountId = (int)$accountId;
    }

    /**
     * @return int
     */
    public function getAccountUserId()
    {
        return $this->_accountUserId;
    }

    /**
     * @param int $accountUserId
     */
    public function setAccountUserId($accountUserId)
    {
        $this->_accountUserId = $accountUserId;
    }

    /**
     * @return int
     */
    public function getAccountUserGroupId()
    {
        return $this->_accountUserGroupId;
    }

    /**
     * @param int $accountUserGroupId
     */
    public function setAccountUserGroupId($accountUserGroupId)
    {
        $this->_accountUserGroupId = $accountUserGroupId;
    }

    /**
     * Obtiene el listado usuarios con acceso a una cuenta.
     * Lo almacena en la cache de sesión como array de cuentas
     *
     * @return array Con los registros con id de cuenta como clave e id de usuario como valor
     */
    public function getUsersAccount()
    {
        $accId = $this->getAccountId();

        $cacheUsers = &$_SESSION['cache']['usersId'];

        if (!is_array($cacheUsers)) {
            $cacheUsers = array($accId => array(), 'expires' => 0);
        }

        if (!isset($cacheUsers[$accId])
            || time() > $cacheUsers['expires']
        ) {
            $cacheUsers[$accId] = UserAccounts::getUsersForAccount($accId);
            $cacheUsers['expires'] = time() + self::CACHE_EXPIRE_TIME;
        }

        return $cacheUsers[$accId];
    }

    /**
     * Obtiene el listado de grupos secundarios de una cuenta.
     * Lo almacena en la cache de sesión como array de cuentas
     *
     * @return array con los registros con id de cuenta como clave e id de grupo como valor
     */
    public function getGroupsAccount()
    {
        $accId = $this->getAccountId();
        $cacheUserGroups = &$_SESSION['cache']['userGroupsId'];

        if (!is_array($cacheUserGroups)) {
            $cacheUserGroups = array($accId => array(), 'expires' => 0);
        }

        if (!isset($cacheUserGroups[$accId])
            || time() > $cacheUserGroups['expires']
        ) {
            $cacheUserGroups[$accId] = Groups::getGroupsForAccount($accId);
            $cacheUserGroups['expires'] = time() + self::CACHE_EXPIRE_TIME;
        }

        return $cacheUserGroups[$accId];
    }

    /**
     * @return bool
     */
    public function getAccountOtherUserEdit()
    {
        return $this->_accountOtherUserEdit;
    }

    /**
     * @param bool $accountOtherUserEdit
     */
    public function setAccountOtherUserEdit($accountOtherUserEdit)
    {
        $this->_accountOtherUserEdit = $accountOtherUserEdit;
    }

    /**
     * @return bool
     */
    public function getAccountOtherGroupEdit()
    {
        return $this->_accountOtherGroupEdit;
    }

    /**
     * @param bool $accountOtherGroupEdit
     */
    public function setAccountOtherGroupEdit($accountOtherGroupEdit)
    {
        $this->_accountOtherGroupEdit = $accountOtherGroupEdit;
    }

    /**
     * Calcular el hash de los datos de una cuenta.
     * Esta función se utiliza para verificar si los datos de un formulario han sido cambiados
     * con respecto a los guardados
     *
     * @return string con el hash
     */
    public function calcChangesHash()
    {
        $groups = 0;
        $users = 0;

        if (is_array($this->getAccountUserGroupsId())) {
            $groups = implode($this->getAccountUserGroupsId());
        } elseif (is_array($this->_cacheUserGroupsId)) {
            foreach ($this->_cacheUserGroupsId as $group) {
                if (is_array($group)) {
                    // Ordenar el array para que el hash sea igual
                    sort($group, SORT_NUMERIC);
                    $groups = implode($group);
                }
            }
        }

        if (is_array($this->getAccountUsersId())) {
            $users = implode($this->getAccountUsersId());
        } elseif (is_array($this->_cacheUsersId)) {
            foreach ($this->_cacheUsersId as $user) {
                if (is_array($user)) {
                    // Ordenar el array para que el hash sea igual
                    sort($user, SORT_NUMERIC);
                    $users = implode($user);
                }
            }
        }

        if ($this->getAccountModHash()) {
            $hashItems = $this->getAccountModHash() . (int)$users . (int)$groups;
            //error_log("HASH MySQL: ".$hashItems);
        } else {
            $hashItems = $this->getAccountName() .
                $this->getAccountCategoryId() .
                $this->getAccountCustomerId() .
                $this->getAccountLogin() .
                $this->getAccountUrl() .
                $this->getAccountNotes() .
                $this->getAccountOtherUserEdit() .
                $this->getAccountOtherGroupEdit() .
                (int)$users .
                (int)$groups;
            //error_log("HASH PHP: ".$hashItems);
        }

        return md5($hashItems);
    }

    /**
     * @return array
     */
    public function getAccountUserGroupsId()
    {
        return $this->_accountUserGroupsId;
    }

    /**
     * @param array $accountUserGroupsId
     */
    public function setAccountUserGroupsId($accountUserGroupsId)
    {
        $this->_accountUserGroupsId = $accountUserGroupsId;
    }

    /**
     * @return array
     */
    public function getAccountUsersId()
    {
        return $this->_accountUsersId;
    }

    /**
     * @param array $accountUsersId
     */
    public function setAccountUsersId($accountUsersId)
    {
        $this->_accountUsersId = $accountUsersId;
    }

    /**
     * @return string
     */
    public function getAccountModHash()
    {
        return $this->_accountModHash;
    }

    /**
     * @param string $accountModHash
     */
    public function setAccountModHash($accountModHash)
    {
        $this->_accountModHash = $accountModHash;
    }

    /**
     * @return string
     */
    public function getAccountName()
    {
        return $this->_accountName;
    }

    /**
     * @param string $accountName
     */
    public function setAccountName($accountName)
    {
        $this->_accountName = $accountName;
    }

    /**
     * @return int
     */
    public function getAccountCategoryId()
    {
        return $this->_accountCategoryId;
    }

    /**
     * @param int $accountCategoryId
     */
    public function setAccountCategoryId($accountCategoryId)
    {
        $this->_accountCategoryId = $accountCategoryId;
    }

    /**
     * @return int
     */
    public function getAccountCustomerId()
    {
        return $this->_accountCustomerId;
    }

    /**
     * @param int $accountCustomerId
     */
    public function setAccountCustomerId($accountCustomerId)
    {
        $this->_accountCustomerId = $accountCustomerId;
    }

    /**
     * @return string
     */
    public function getAccountLogin()
    {
        return $this->_accountLogin;
    }

    /**
     * @param string $accountLogin
     */
    public function setAccountLogin($accountLogin)
    {
        $this->_accountLogin = $accountLogin;
    }

    /**
     * @return string
     */
    public function getAccountUrl()
    {
        return $this->_accountUrl;
    }

    /**
     * @param string $accountUrl
     */
    public function setAccountUrl($accountUrl)
    {
        $this->_accountUrl = $accountUrl;
    }

    /**
     * @return string
     */
    public function getAccountNotes()
    {
        return $this->_accountNotes;
    }

    /**
     * @param string $accountNotes
     */
    public function setAccountNotes($accountNotes)
    {
        $this->_accountNotes = $accountNotes;
    }

    /**
     * Obtener los datos de una cuenta para mostrar la clave
     * Esta funcion realiza la consulta a la BBDD y devuelve los datos.
     */
    protected abstract function getAccountPassData();

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     */
    protected abstract function getAccountsPassData();
}