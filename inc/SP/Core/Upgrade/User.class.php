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
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class User
 *
 * @package SP\Core\Upgrade
 */
class User
{
    /**
     * Actualizar registros con usuarios no existentes
     *
     * @param int $userId Id de usuario por defecto
     * @return bool
     */
    public static function fixUsersId($userId)
    {
        $Data = new QueryData();
        $Data->setQuery('SELECT user_id FROM usrData ORDER BY user_id');

        $users = DB::getResultsArray($Data);

        $paramsIn = trim(str_repeat(',?', count($users)), ',');
        $Data->addParam($userId);

        foreach ($users as $user) {
            $Data->addParam($user->user_id);
        }

        try {
            DB::beginTransaction();

            $query = /** @lang SQL */
                'UPDATE accounts SET account_userId = ? WHERE account_userId NOT IN (' . $paramsIn . ') OR account_userId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'UPDATE accounts SET account_userEditId = ? WHERE account_userEditId NOT IN (' . $paramsIn . ') OR account_userEditId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'UPDATE accHistory SET acchistory_userId = ? WHERE acchistory_userId NOT IN (' . $paramsIn . ') OR acchistory_userId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'UPDATE accHistory SET acchistory_userEditId = ? WHERE acchistory_userEditId NOT IN (' . $paramsIn . ') OR acchistory_userEditId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'DELETE FROM usrPassRecover WHERE userpassr_userId <> ? AND userpassr_userId NOT IN (' . $paramsIn . ')';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'DELETE FROM usrToGroups WHERE usertogroup_userId NOT IN (' . $paramsIn . ') OR usertogroup_userId IS NULL';
            $Data->setQuery($query);

            DB::getQuery($Data);

            $query = /** @lang SQL */
                'DELETE FROM accUsers WHERE accuser_userId NOT IN (' . $paramsIn . ') OR accuser_userId IS NULL';
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