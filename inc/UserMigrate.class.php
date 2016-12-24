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
 * Class UserMigrate para la migración de usuarios
 *
 * @package SP
 */
class UserMigrate
{

    /**
     * Comprobar si un usuario está migrado desde phpPMS.
     *
     * @param string $userLogin con el login del usuario
     * @return bool
     */
    public static function checkUserIsMigrate($userLogin)
    {
        $query = 'SELECT BIN(user_isMigrate) AS user_isMigrate FROM usrData WHERE user_login = :login LIMIT 1';

        $data['login'] = $userLogin;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        return ($queryRes !== false && $queryRes->user_isMigrate == 1);
    }

    /**
     * Actualizar la clave de un usuario desde phpPMS.
     *
     * @param string $userLogin con el login del usuario
     * @param string $userPass  con la clave del usuario
     * @param string $hash      con el hash de la clave actual
     * @return bool Esta función actualiza la clave de un usuario que ha sido migrado desde phpPMS
     *
     * Esta función actualiza la clave de un usuario que ha sido migrado desde phpPMS
     */
    public static function migrateUser($userLogin, $userPass, $hash = '')
    {
        $passdata = UserPass::makeUserPassHash($userPass);

        $query = 'UPDATE usrData SET '
            . 'user_pass = :pass,'
            . 'user_hashSalt = :salt,'
            . 'user_lastUpdate = NOW(),'
            . 'user_isMigrate = 0 '
            . 'WHERE user_login = :login '
            . 'AND user_isMigrate = 1 '
            . 'AND (user_pass = SHA1(CONCAT(user_hashSalt,:passOld)) '
            . 'OR user_pass = MD5(:passOldMd5) OR user_pass = :passCur) LIMIT 1';

        $data['pass'] = $passdata['pass'];
        $data['salt'] = $passdata['salt'];
        $data['login'] = $userLogin;
        $data['passOld'] = $userPass;
        $data['passOldMd5'] = $userPass;
        $data['passCur'] = $hash;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $rows = DB::$lastNumRows;

        $log = new Log(__FUNCTION__);
        $log->addDescription(_('Usuario actualizado'));
        $log->addDescription('Login: ' . $userLogin);
        $log->writeLog();

        Email::sendEmail($log);

        return $rows === 1;
    }

    /**
     * Migrar el grupo de los usuarios a la nueva tabla
     */
    public static function migrateUsersGroup()
    {
        $query = 'SELECT user_id, user_groupId FROM usrData';

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $user) {
            if (!Groups::addUsersForGroup(array($user->user_groupId), $user->user_id)) {
                Log::writeNewLog(_('Migrar Grupos'), sprintf('%s (%s)', _('Error al migrar grupo del usuario'), $user->user_id));
            }
        }

        return true;
    }

    /**
     * Establecer el campo isMigrate de cada usuario
     */
    public static function setMigrateUsers()
    {
        $query = 'UPDATE usrData SET user_isMigrate = 1';

        return DB::getQuery($query, __FUNCTION__);
    }
}