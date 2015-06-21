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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones sobre los grupos de usuarios.
 */
class Groups
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
     * @param int  $groupId     opcional, con el Id del grupo a consultar
     * @param bool $returnArray opcional, si se debe de devolver un array asociativo
     * @return false|array con la lista de grupos
     */
    public static function getGroups($groupId = null, $returnArray = false)
    {
        $query = "SELECT usergroup_id,"
            . "usergroup_name,"
            . "usergroup_description "
            . "FROM usrGroups ";

        $data = null;

        if (!is_null($groupId)) {
            $query .= "WHERE usergroup_id = :id LIMIT 1";
            $data['id'] = $groupId;
        } else {
            $query .= "ORDER BY usergroup_name";
        }

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

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
            $query = "SELECT usergroup_name FROM usrGroups WHERE UPPER(usergroup_name) = :name AND usergroup_id != :id";
            $data['id'] = $groupId;
        } else {
            $query = "SELECT usergroup_name FROM usrGroups WHERE UPPER(usergroup_name) = :name";
        }

        $data['name'] = $groupName;

        return (DB::getQuery($query, __FUNCTION__, $data) === false || DB::$last_num_rows >= 1);
    }

    /**
     * Añadir un nuevo grupo.
     *
     * @return bool
     */
    public static function addGroup()
    {
        $query = 'INSERT INTO usrGroups SET usergroup_name = :name, usergroup_description = :description';

        $data['name'] = self::$groupName;
        $data['description'] = self::$groupDescription;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        $message['action'] = _('Nuevo Grupo');
        $message['text'][] = Html::strongText(_('Grupo') . ': ') . self::$groupName;

        Log::wrLogInfo($message);
        Common::sendEmail($message);

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

        $query = 'UPDATE usrGroups SET usergroup_name = :name, usergroup_description = :description WHERE usergroup_id = :id';

        $data['name'] = self::$groupName;
        $data['description'] = self::$groupDescription;
        $data['id'] = self::$groupId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        $message['action'] = _('Modificar Grupo');
        $message['text'][] = Html::strongText(_('Grupo') . ': ') . $groupName . ' > ' . self::$groupName;

        Log::wrLogInfo($message);
        Common::sendEmail($message);

        return true;
    }

    /**
     * Obtener el nombre de un grupo por a partir del Id.
     *
     * @param int $id con el Id del grupo
     * @return false|string con el nombre del grupo
     */
    public static function getGroupNameById($id)
    {
        $query = 'SELECT usergroup_name FROM usrGroups WHERE usergroup_id = :id LIMIT 1';

        $data['id'] = $id;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->usergroup_name;
    }

    /**
     * Eliminar un grupo.
     *
     * @return bool
     */
    public static function deleteGroup()
    {
        $groupName = self::getGroupNameById(self::$groupId);

        $query = 'DELETE FROM usrGroups WHERE usergroup_id = :id LIMIT 1';

        $data['id'] = self::$groupId;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        $message['action'] = _('Eliminar Grupo');
        $message['text'][] = Html::strongText(_('Grupo') . ': ') . $groupName;

        Log::wrLogInfo($message);
        Common::sendEmail($message);

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
     * @return int con el número total de cuentas
     */
    private static function getGroupInUsers()
    {
        $query = 'SELECT user_groupId FROM usrData WHERE user_groupId = :id';

        $data['id'] = self::$groupId;

        DB::getQuery($query, __FUNCTION__, $data);

        return DB::$last_num_rows;
    }

    /**
     * Obtener el número de cuentas que usan un grupo como primario.
     *
     * @return int con el número total de cuentas
     */
    private static function getGroupInAccounts()
    {
        $query = 'SELECT account_userGroupId FROM accounts WHERE account_userGroupId = :id';

        $data['id'] = self::$groupId;

        DB::getQuery($query, __FUNCTION__, $data);

        return DB::$last_num_rows;
    }

    /**
     * Obtener el número de cuentas que usan un grupo como secundario.
     *
     * @return false|int con el número total de cuentas
     */
    private static function getGroupInAccountsSec()
    {
        $query = 'SELECT accgroup_groupId FROM accGroups WHERE accgroup_groupId = :id';

        $data['id'] = self::$groupId;

        DB::getQuery($query, __FUNCTION__, $data);

        return DB::$last_num_rows;
    }

    /**
     * Obtiene el listado con el nombre de los grupos de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|array con los nombres de los grupos ordenados
     */
    public static function getGroupsNameForAccount($accountId)
    {
        $query = 'SELECT usergroup_id,'
            . 'usergroup_name '
            . 'FROM accGroups '
            . 'JOIN usrGroups ON accgroup_groupId = usergroup_id '
            . 'WHERE accgroup_accountId = :id';

        $data['id'] = $accountId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

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
     * @param int   $accountId con el Id de la cuenta
     * @param array $groupsId  con los grupos de la cuenta
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
     * @param int   $accountId con el Id de la cuenta
     * @param array $groupsId  opcional con los grupos de la cuenta
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

        $data['id'] = $accountId;

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Crear asociación de grupos con cuentas.
     *
     * @param int   $accountId con el Id de la cuenta
     * @param array $groupsId  con los grupos de la cuenta
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

        return DB::getQuery($query, __FUNCTION__);
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

        $data['id'] = $accountId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return array();
        }

        foreach ($queryRes as $group) {
            $groups[] = $group->accgroup_groupId;
        }

        return $groups;
    }

}
