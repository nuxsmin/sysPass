<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Account;

use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountHistoryData;

defined('APP_ROOT') || die();

/**
 * Interface AccountInterface con la definición de métodos comunes a las cuentas
 */
interface AccountInterface
{
    /**
     * @return AccountExtData|AccountData|AccountHistoryData
     */
    public function getData();

    /**
     * @param bool $encryptPass Si se encripta la clave de la cuenta
     *
     * @return mixed
     */
    public function createAccount($encryptPass = true);

    /**
     * @param $id
     *
     * @return mixed
     */
    public function deleteAccount($id);
}