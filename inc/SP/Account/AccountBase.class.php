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

namespace SP\Account;

use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountHistoryData;
use SP\Mgmt\Groups\GroupAccountsUtil;

defined('APP_ROOT') || die();

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
     * @var AccountData|AccountExtData|AccountHistoryData
     */
    protected $accountData;
    /**
     * @var int Id de la cuenta padre.
     */
    private $accountParentId;
    /**
     * @var int Indica si la cuenta es un registro del histórico.
     */
    private $accountIsHistory = 0;

    /**
     * Constructor
     *
     * @param AccountData $accountData
     */
    public function __construct(AccountData $accountData = null)
    {
        $this->accountData = (null !== $accountData) ? $accountData : new AccountData();
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
     * @return AccountExtData objeto con los datos de la cuenta
     */
    public function getAccountDataForACL($accountId = null)
    {
        if (null !== $accountId) {
            $this->accountData->setAccountId($accountId);
        } else {
            $this->accountData->getAccountId();
        }

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
            $cacheUsers = [$accId => [], 'expires' => 0];
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
            $cacheUserGroups = [$accId => [], 'expires' => 0];
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
     * @return AccountData|AccountExtData
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