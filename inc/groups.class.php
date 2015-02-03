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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones sobre los grupos de usuarios.
 */
class SP_Groups
{
    static $queryRes;
    static $groupId;
    static $groupName;
    static $groupDescription;
    static $queryLastId;

    /**
     * Obtener los datos de un grupo.
     *
     * @param int $id con el Id del grupo a consultar
     * @return array con el nombre de la columna como clave y los datos como valor
     */
    public static function getGroupData($id = 0)
    {
        $group = array('usergroup_id' => 0,
            'usergroup_name' => '',
            'usergroup_description' => '',
            'action' => 1);

        if ($id > 0) {
            $userGroups = self::getGroups($id);

            if ($userGroups) {
                foreach ($userGroups[0] as $name => $value) {
                    $group[$name] = $value;
                }
                $group['action'] = 2;
            }
        }

        return $group;
    }

    /**
     * Obtener los grupos de usuarios.
     *
     * @param int $groupId      opcional, con el Id del grupo a consultar
     * @param bool $returnArray opcional, si se debe de devolver un array asociativo
     * @return false|array con la lista de grupos
     */
    public static function getGroups($groupId = NULL, $returnArray = false)
    {
        $query = "SELECT usergroup_id,"
            . "usergroup_name,"
            . "usergroup_description "
            . "FROM usrGroups ";


        if (!is_null($groupId)) {
            $query .= "WHERE usergroup_id = " . (int)$groupId . " LIMIT 1";
        } else {
            $query .= "ORDER BY usergroup_name";
        }

        $queryRes = DB::getResults($query, __FUNCTION__, true);

        if ($queryRes === false) {
            return false;
        }

        if ($returnArray === true) {
            foreach ($queryRes as $group) {
                $groups[$group->usergroup_name] = $group->usergroup_id;
            }

            return $groups;
        }

        return $queryRes;
    }

    /**
     * Comprobar si un grupo existe en la BBDD.
     *
     * @return bool
     */
    public static function checkGroupExist()
    {
        $groupId = (int)self::$groupId;
        $groupName = strtoupper(self::$groupName);

        if ($groupId) {
            $query = "SELECT usergroup_name 
                        FROM usrGroups
                        WHERE UPPER(usergroup_name) = '" . DB::escape($groupName) . "' 
                        AND usergroup_id != " . (int)$groupId;
        } else {
            $query = "SELECT usergroup_name 
                        FROM usrGroups
                        WHERE UPPER(usergroup_name) = '" . DB::escape($groupName) . "'";
        }

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        if (count(DB::$last_result) >= 1) {
            return false;
        }

        return true;
    }

    /**
     * Añadir un nuevo grupo.
     *
     * @return bool
     */
    public static function addGroup()
    {
        $query = "INSERT INTO usrGroups SET
                    usergroup_name = '" . DB::escape(self::$groupName) . "',
                    usergroup_description = '" . DB::escape(self::$groupDescription) . "'";

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        $message['action'] = _('Nuevo Grupo');
        $message['text'][] = SP_Html::strongText(_('Grupo') . ': ') . self::$groupName;

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * Modificar un grupo.
     *
     * @return bool
     */
    public static function updateGroup()
    {
        $groupName = self::getGroupNameById(self::$groupId);

        $query = "UPDATE usrGroups SET 
                    usergroup_name = '" . DB::escape(self::$groupName) . "',
                    usergroup_description = '" . DB::escape(self::$groupDescription) . "' 
                    WHERE usergroup_id = " . (int)self::$groupId;

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        $message['action'] = _('Modificar Grupo');
        $message['text'][] = SP_Html::strongText(_('Grupo') . ': ') . $groupName . ' > ' . self::$groupName;

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * Eliminar un grupo.
     *
     * @return bool
     */
    public static function deleteGroup()
    {
        $groupName = self::getGroupNameById(self::$groupId);

        $query = "DELETE FROM usrGroups "
            . "WHERE usergroup_id = " . (int)self::$groupId . " LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        $message['action'] = _('Eliminar Grupo');
        $message['text'][] = SP_Html::strongText(_('Grupo') . ': ') . $groupName;

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * Comprobar si un grupo está en uso por usuarios o cuentas.
     *
     * @return array con el número de usuarios/cuentas que usan el grupo
     */
    public static function checkGroupInUse()
    {
        $count['users'] = self::getGroupInUsers();
        $count['accounts'] = self::getGroupInAccounts() + self::getGroupInAccountsSec();
        return $count;
    }

    /**
     * Obtener el número de usuarios que usan un grupo.
     *
     * @return false|int con el número total de cuentas
     */
    private static function getGroupInUsers()
    {
        $query = "SELECT COUNT(*) as uses "
            . "FROM usrData "
            . "WHERE user_groupId = " . (int)self::$groupId;

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->uses;
    }

    /**
     * Obtener el número de cuentas que usan un grupo como primario.
     *
     * @return false|int con el número total de cuentas
     */
    private static function getGroupInAccounts()
    {
        $query = "SELECT COUNT(*) as uses "
            . "FROM accounts "
            . "WHERE account_userGroupId = " . (int)self::$groupId;

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->uses;
    }

    /**
     * Obtener el número de cuentas que usan un grupo como secundario.
     *
     * @return false|int con el número total de cuentas
     */
    private static function getGroupInAccountsSec()
    {
        $query = "SELECT COUNT(*) as uses "
            . "FROM accGroups "
            . "WHERE accgroup_groupId = " . (int)self::$groupId;

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->uses;
    }

    /**
     * Obtener el nombre de un grupo por a partir del Id.
     *
     * @param int $id con el Id del grupo
     * @return false|string con el nombre del grupo
     */
    public static function getGroupNameById($id)
    {
        $query = "SELECT usergroup_name "
            . "FROM usrGroups "
            . "WHERE usergroup_id = " . (int)$id . " LIMIT 1";

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->usergroup_name;
    }

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|array con los nombres de los grupos ordenados
     */
    public static function getGroupsNameForAccount($accountId)
    {
        $query = "SELECT usergroup_id,"
            . "usergroup_name "
            . "FROM accGroups "
            . "JOIN usrGroups ON accgroup_groupId = usergroup_id "
            . "WHERE accgroup_accountId = " . (int)$accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, true);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $groups) {
            $groupsName[$groups->usergroup_id] = $groups->usergroup_name;
        }

        asort($groupsName, SORT_STRING);

        return $groupsName;
    }

    /**
     * Actualizar la asociación de grupos con cuentas.
     *
     * @param int $accountId  con el Id de la cuenta
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
     * @param int $accountId  con el Id de la cuenta
     * @param array $groupsId opcional con los grupos de la cuenta
     * @return bool
     */
    public static function deleteGroupsForAccount($accountId, $groupsId = NULL)
    {
        $queryExcluded = '';

        // Excluimos los grupos actuales
        if (is_array($groupsId)) {
            $queryExcluded = ' AND accgroup_groupId NOT IN (' . implode(',', $groupsId) . ')';
        }

        $query = 'DELETE FROM accGroups '
            . 'WHERE accgroup_accountId = ' . (int)$accountId . $queryExcluded;

        //error_log($query);

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        return true;
    }

    /**
     * Crear asociación de grupos con cuentas.
     *
     * @param int $accountId  con el Id de la cuenta
     * @param array $groupsId con los grupos de la cuenta
     * @return bool
     */
    public static function addGroupsForAccount($accountId, $groupsId)
    {
        $values = '';

        // Obtenemos los grupos actuales
        $currentGroups = self::getGroupsForAccount($accountId);

        if (is_array($currentGroups)) {
            foreach ($currentGroups as $group) {
                $groupsExcluded[] = $group->accgroup_groupId;
            }
        }

        foreach ($groupsId as $groupId) {
            // Excluimos los grupos actuales
            if (is_array($groupsExcluded) && in_array($groupId, $groupsExcluded)) {
                continue;
            }

            $values[] = '(' . $accountId . ',' . $groupId . ')';
        }

        if (!is_array($values)) {
            return true;
        }

        $query = 'INSERT INTO accGroups (accgroup_accountId, accgroup_groupId) '
            . 'VALUES ' . implode(',', $values);

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        return true;
    }

    /**
     * Obtiene el listado de grupos de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|array con el Id de grupo
     */
    public static function getGroupsForAccount($accountId)
    {
        $query = "SELECT accgroup_groupId "
            . "FROM accGroups "
            . "WHERE accgroup_accountId = " . (int)$accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, true);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes;
    }

}
