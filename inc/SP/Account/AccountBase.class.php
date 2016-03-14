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

use SP\DataModel\AccountData;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Groups\GroupAccountsUtil;

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
     * @var AccountData
     */
    protected $accountData;
    /**
     * @var int Id de la cuenta padre.
     */
    private $accountParentId;
    /**
     * @var string Hash con los datos de la cuenta para verificación de cambios.
     */
    private $accountModHash;
    /**
     * @var int Indica si la cuenta es un registro del hitórico.
     */
    private $accountIsHistory = 0;
    /**
     * @var array Los Ids de los grupos con acceso a la cuenta
     */
    private $cacheUserGroupsId;
    /**
     * @var array Los Ids de los usuarios con acceso a la cuenta
     */
    private $cacheUsersId;

    /**
     * Constructor
     *
     * @param AccountData $accountData
     */
    public function __construct(AccountData $accountData = null)
    {
        $this->accountData = (!is_null($accountData)) ? $accountData : new AccountData();
    }

    /**
     * @return int
     */
    public function getAccountIsHistory()
    {
        return $this->accountIsHistory;
    }

    /**
     * @param int $accountIsHistory
     */
    public function setAccountIsHistory($accountIsHistory)
    {
        $this->accountIsHistory = $accountIsHistory;
    }

    /**
     * @return int
     */
    public function getAccountParentId()
    {
        return $this->accountParentId;
    }

    /**
     * @param int $accountParentId
     */
    public function setAccountParentId($accountParentId)
    {
        $this->accountParentId = $accountParentId;
    }

    /**
     * Devolver datos de la cuenta para comprobación de accesos.
     *
     * @param int $accountId con el id de la cuenta
     * @return AccountData objeto con los datos de la cuenta
     */
    public function getAccountDataForACL($accountId = null)
    {
        $accId = (!is_null($accountId)) ? $accountId : $this->accountData->getAccountId();

        $this->accountData->setAccountId($accId);

        return $this->accountData;
    }

    /**
     * Obtiene el listado usuarios con acceso a una cuenta.
     * Lo almacena en la cache de sesión como array de cuentas
     *
     * @return array Con los registros con id de cuenta como clave e id de usuario como valor
     */
    public function getUsersAccount()
    {
        $accId = $this->accountData->getAccountId();

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
        $accId = $this->accountData->getAccountId();
        $cacheUserGroups = &$_SESSION['cache']['userGroupsId'];

        if (!is_array($cacheUserGroups)) {
            $cacheUserGroups = array($accId => array(), 'expires' => 0);
        }

        if (!isset($cacheUserGroups[$accId])
            || time() > $cacheUserGroups['expires']
        ) {
            $cacheUserGroups[$accId] = GroupAccountsUtil::getGroupsForAccount($accId);
            $cacheUserGroups['expires'] = time() + self::CACHE_EXPIRE_TIME;
        }

        return $cacheUserGroups[$accId];
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

        if (is_array($this->accountData->getAccountUserGroupsId())) {
            $groups = implode($this->accountData->getAccountUserGroupsId());
        } elseif (is_array($this->cacheUserGroupsId)) {
            foreach ($this->cacheUserGroupsId as $group) {
                if (is_array($group)) {
                    // Ordenar el array para que el hash sea igual
                    sort($group, SORT_NUMERIC);
                    $groups = implode($group);
                }
            }
        }

        if (is_array($this->accountData->getAccountUsersId())) {
            $users = implode($this->accountData->getAccountUsersId());
        } elseif (is_array($this->cacheUsersId)) {
            foreach ($this->cacheUsersId as $user) {
                if (is_array($user)) {
                    // Ordenar el array para que el hash sea igual
                    sort($user, SORT_NUMERIC);
                    $users = implode($user);
                }
            }
        }

        if ($this->getAccountModHash()) {
            $hashItems = $this->getAccountModHash() . (int)$users . (int)$groups;
        } else {
            $hashItems = $this->accountData->getAccountName() .
                $this->accountData->getAccountCategoryId() .
                $this->accountData->getAccountCustomerId() .
                $this->accountData->getAccountLogin() .
                $this->accountData->getAccountUrl() .
                $this->accountData->getAccountNotes() .
                implode('', array_keys($this->accountData->getTags())) .
                (int)$this->accountData->getAccountOtherUserEdit() .
                (int)$this->accountData->getAccountOtherGroupEdit() .
                (int)$users .
                (int)$groups;
        }

        return md5($hashItems);
    }


    /**
     * @return string
     */
    public function getAccountModHash()
    {
        return $this->accountModHash;
    }

    /**
     * @param string $accountModHash
     */
    public function setAccountModHash($accountModHash)
    {
        $this->accountModHash = $accountModHash;
    }

    /**
     * @return AccountData
     */
    public function getAccountData()
    {
        return $this->accountData;
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