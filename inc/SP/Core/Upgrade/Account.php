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

namespace SP\Core\Upgrade;

use SP\Core\Exceptions\SPException;
use SP\Core\TaskFactory;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class Account
 *
 * @package SP\Core\Upgrade
 */
class Account
{
    /**
     * Actualizar registros con usuarios no existentes
     *
     * @return bool
     */
    public static function fixAccountsId()
    {
        TaskFactory::$Message->setTask(__FUNCTION__);
        TaskFactory::$Message->setMessage(__('Actualizando IDs de cuentas'));
        TaskFactory::sendTaskMessage();

        try {
            DB::beginTransaction();

            $Data = new QueryData();
            $query = /** @lang SQL */
                'DELETE FROM accUsers WHERE accuser_accountId NOT IN (SELECT account_id FROM accounts) OR accuser_accountId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'DELETE FROM accGroups WHERE accgroup_accountId NOT IN (SELECT account_id FROM accounts) OR accgroup_accountId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            DB::endTransaction();

            return true;
        } catch (SPException $e) {
            DB::rollbackTransaction();

            return false;
        }
    }
}