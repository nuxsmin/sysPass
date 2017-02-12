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

namespace SP\Mgmt\Groups;

defined('APP_ROOT') || die();

use SP\DataModel\GroupData;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class GroupAccountsUtil
 *
 * @package SP\Mgmt\Groups
 */
class GroupAccountsUtil
{
    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return GroupData[]
     */
    public static function getGroupsInfoForAccount($accountId)
    {
        $query = /** @lang SQL */
            'SELECT usergroup_id,
            usergroup_name
            FROM accGroups
            JOIN usrGroups ON accgroup_groupId = usergroup_id
            WHERE accgroup_accountId = ?
            ORDER BY usergroup_name';

        $Data = new QueryData();
        $Data->setMapClassName(GroupData::class);
        $Data->setQuery($query);
        $Data->addParam($accountId);

        return DB::getResultsArray($Data);
    }

    /**
     * Obtiene el listado de grupos de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return array Con los ids de los grupos
     */
    public static function getGroupsForAccount($accountId)
    {
        $GroupAccountsData = GroupAccounts::getItem()->getByAccountId($accountId);

        $groups = [];

        foreach ($GroupAccountsData as $Group) {
            $groups[] = (int)$Group->getAccgroupGroupId();
        }

        return $groups;
    }
}