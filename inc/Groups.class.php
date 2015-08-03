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

        return (DB::getQuery($query, __FUNCTION__, $data) === false || DB::$lastNumRows >= 1);
    }

    /**
     * Añadir un nuevo grupo.
     *
     * @param $users array Los usuario del grupo
     * @return bool
     */
    public static function addGroup($users)
    {
        $query = 'INSERT INTO usrGroups SET usergroup_name = :name, usergroup_description = :description';

        $data['name'] = self::$groupName;
        $data['description'] = self::$groupDescription;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        self::$queryLastId = DB::$lastId;

        $Log = new Log(_('Nuevo Grupo'));

        if (!Groups::addUsersForGroup(self::$queryLastId, $users)) {
            $Log->addDescription(_('Error al añadir los usuarios del grupo'));
        }

        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Grupo')), self::$groupName));
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Crear asociación de grupos con usuarios.
     *
     * @param int   $groupId con los grupos del usuario
     * @param array $usersId Los usuarios del grupo
     * @return bool
     */
    public static function addUsersForGroup($groupId, $usersId)
    {
        if (!is_array($usersId)) {
            return true;
        }

        $values = '';

        // Obtenemos los grupos actuales
        $groupsExcluded = self::getUsersForGroup($groupId);

        foreach ($usersId as $userId) {
            // Excluimos los grupos actuales
            if (isset($groupsExcluded) && is_array($groupsExcluded) && in_array($userId, $groupsExcluded)) {
                continue;
            }

            $values[] = '(' . (int)$userId . ',' . (int)$groupId . ')';
        }

        if (!is_array($values)) {
            return true;
        }

        $query = 'INSERT INTO usrToGroups (usertogroup_userId, usertogroup_groupId) VALUES ' . implode(',', $values);

        return DB::getQuery($query, __FUNCTION__);
    }

    /**
     * Obtiene el listado de grupos de un usuario.
     *
     * @param int $groupId con el Id del usuario
     * @return array con el Id de grupo
     */
    public static function getUsersForGroup($groupId)
    {
        $users = array();

        $query = 'SELECT usertogroup_userId FROM usrToGroups WHERE usertogroup_groupId = :id';

        $data['id'] = $groupId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return array();
        }

        foreach ($queryRes as $group) {
            $users[] = $group->usertogroup_userId;
        }

        return $users;
    }

    /**
     * Modificar un grupo.
     *
     * @param $users array Los usuario del grupo
     * @return bool
     */
    public static function updateGroup($users)
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

        $Log = new Log(_('Nuevo Grupo'));

        if (!Groups::updateUsersForGroup(self::$groupId, $users)) {
            $Log->addDescription(_('Error al actualizar los usuarios del grupo'));
        }

        $Log->addDescription(sprintf('%s : %s > %s', Html::strongText(_('Grupo')), $groupName, self::$groupName));
        $Log->writeLog();

        Email::sendEmail($Log);

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
     * Obtener el id de un grupo por a partir del nombre.
     *
     * @param int $name con el nombre del grupo
     * @return false|string con el nombre del grupo
     */
    public static function getGroupIdByName($name)
    {
        $query = 'SELECT usergroup_id FROM usrGroups WHERE usergroup_name = :name LIMIT 1';

        $data['name'] = $name;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->usergroup_id;
    }

    /**
     * Actualizar la asociación de grupos con usuarios.
     *
     * @param int   $groupId con el Id del usuario
     * @param array $usersId con los usuarios del grupo
     * @return bool
     */
    public static function updateUsersForGroup($groupId, $usersId)
    {
        if (self::deleteUsersForGroup($groupId, $usersId)) {
            return self::addUsersForGroup($groupId, $usersId);
        }

        return false;
    }

    /**
     * Eliminar la asociación de grupos con usuarios.
     *
     * @param int   $groupId con el Id del grupo
     * @param array $usersId opcional con los usuarios del grupo
     * @return bool
     */
    public static function deleteUsersForGroup($groupId, $usersId = null)
    {
        $queryExcluded = '';

        // Excluimos los grupos actuales
        if (is_array($usersId)) {
            array_map('intval', $usersId);

            $queryExcluded = 'AND usertogroup_userId NOT IN (' . implode(',', $usersId) . ')';
        }

        $query = 'DELETE FROM usrToGroups WHERE usertogroup_groupId = :id ' . $queryExcluded;

        $data['id'] = $groupId;

        return DB::getQuery($query, __FUNCTION__, $data);
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

        $Log = new Log(_('Eliminar Grupo'));

        if (!Groups::deleteUsersForGroup(self::$groupId)) {
            $Log->addDescription(_('Error al eliminar los usuarios del grupo'));
        }

        $Log->addDescription(sprintf('%s : %s', Html::strongText(_('Grupo')), $groupName));
        $Log->writeLog();

        Email::sendEmail($Log);

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
        $query = 'SELECT user_groupId as groupId FROM usrData WHERE user_groupId = :idu ' .
            'UNION ALL SELECT usertogroup_groupId as groupId FROM usrToGroups WHERE usertogroup_groupId = :idg';

        $data['idu'] = self::$groupId;
        $data['idg'] = self::$groupId;

        DB::getQuery($query, __FUNCTION__, $data);

        return DB::$lastNumRows;
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

        return DB::$lastNumRows;
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

        return DB::$lastNumRows;
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
            $groups[] = (int)$group->accgroup_groupId;
        }

        return $groups;
    }
}
