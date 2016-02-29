<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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

use SP\DataModel\GroupData;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class GroupAccounts
 *
 * @package SP\Account
 */
class GroupAccounts
{

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return GroupData[]
     */
    public static function getGroupsInfoForAccount($accountId)
    {
        $query = 'SELECT usergroup_id,
            usergroup_name
            FROM accGroups
            JOIN usrGroups ON accgroup_groupId = usergroup_id
            WHERE accgroup_accountId = :id
            ORDER BY usergroup_name';

        $Data = new QueryData();
        $Data->setMapClassName('\SP\DataModel\GroupData');
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        DB::setReturnArray();

        return DB::getResults($Data);
    }

    /**
     * Actualizar la asociación de grupos con cuentas.
     *
     * @param int $accountId con el Id de la cuenta
     * @param array $groupsId con los grupos de la cuenta
     * @return bool
     */
    public static function updateGroupsForAccount($accountId, $groupsId)
    {
        if (self::deleteGroupsForAccount($accountId, $groupsId)) {
            return self::addGroupsForAccount($accountId, $groupsId);
        }

        return false;
    }

    /**
     * Eliminar la asociación de grupos con cuentas.
     *
     * @param int $accountId con el Id de la cuenta
     * @param array $groupsId opcional con los grupos de la cuenta
     * @return bool
     */
    public static function deleteGroupsForAccount($accountId, $groupsId = null)
    {
        $queryExcluded = '';

        // Excluimos los grupos actuales
        if (is_array($groupsId)) {
            array_map('intval', $groupsId);

            $queryExcluded = 'AND accgroup_groupId NOT IN (' . implode(',', $groupsId) . ')';
        }

        $query = 'DELETE FROM accGroups WHERE accgroup_accountId = :id ' . $queryExcluded;

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        return DB::getQuery($Data);
    }

    /**
     * Crear asociación de grupos con cuentas.
     *
     * @param int $accountId con el Id de la cuenta
     * @param array $groupsId con los grupos de la cuenta
     * @return bool
     */
    public static function addGroupsForAccount($accountId, $groupsId)
    {
        if (!is_array($groupsId)) {
            return true;
        }

        $values = '';

        // Obtenemos los grupos actuales
        $groupsExcluded = self::getGroupsForAccount($accountId);

        foreach ($groupsId as $groupId) {
            // Excluimos los grupos actuales
            if (isset($groupsExcluded) && is_array($groupsExcluded) && in_array($groupId, $groupsExcluded)) {
                continue;
            }

            $values[] = '(' . (int)$accountId . ',' . (int)$groupId . ')';
        }

        if (!is_array($values)) {
            return true;
        }

        $query = 'INSERT INTO accGroups (accgroup_accountId, accgroup_groupId) VALUES ' . implode(',', $values);

        $Data = new QueryData();
        $Data->setQuery($query);

        return DB::getQuery($Data);
    }

    /**
     * Obtiene el listado de grupos de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|array con el Id de grupo
     */
    public static function getGroupsForAccount($accountId)
    {
        $query = 'SELECT accgroup_groupId FROM accGroups WHERE accgroup_accountId = :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        DB::setReturnArray();

        $groups = [];

        foreach (DB::getResults($Data) as $group) {
            $groups[] = (int)$group->accgroup_groupId;
        }

        return $groups;
    }

    /**
     * Obtener los grupos de usuarios de una búsqueda
     *
     * @param $limitCount
     * @param int $limitStart
     * @param string $search
     * @return array
     */
    public static function getGroupsMgmtSearch($limitCount, $limitStart = 0, $search = '')
    {
        $query = 'SELECT usergroup_id,'
            . 'usergroup_name,'
            . 'usergroup_description '
            . 'FROM usrGroups';

        $Data = new QueryData();

        if (!empty($search)) {
            $search = '%' . $search . '%';
            $query .= ' WHERE usergroup_name LIKE ? OR usergroup_description LIKE ?';

            $Data->addParam($search);
            $Data->addParam($search);
        }

        $query .= ' ORDER BY usergroup_name';
        $query .= ' LIMIT ?, ?';

        $Data->addParam($limitStart);
        $Data->addParam($limitCount);

        $Data->setQuery($query);

        DB::setReturnArray();
        DB::setFullRowCount();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        $queryRes['count'] = DB::$lastNumRows;

        return $queryRes;
    }
}