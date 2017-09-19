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
}