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

namespace SP\Mgmt\User;

use SP\Config\Config;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class UserLdap
 *
 * @package SP
 */
class UserLdap
{
    /**
     * Crear un nuevo usuario en la BBDD con los datos de LDAP.
     * Esta función crea los usuarios de LDAP en la BBDD para almacenar infomación del mismo
     * y utilizarlo en caso de fallo de LDAP
     *
     * @param User $User
     * @return bool
     */
    public static function newUserLDAP(User $User)
    {
        $passdata = UserPass::makeUserPassHash($User->getUserPass());
        $groupId = Config::getValue('ldap_defaultgroup', 0);
        $profileId = Config::getValue('ldap_defaultprofile', 0);

        $query = 'INSERT INTO usrData SET '
            . 'user_name = :name,'
            . 'user_groupId = :groupId,'
            . 'user_login = :login,'
            . 'user_pass = :pass,'
            . 'user_hashSalt = :hashSalt,'
            . 'user_email = :email,'
            . 'user_notes = :notes,'
            . 'user_profileId = :profileId,'
            . 'user_isLdap = 1,'
            . 'user_isDisabled = :isDisabled';

        $data['name'] = $User->getUserName();
        $data['login'] = $User->getUserLogin();
        $data['pass'] = $passdata['pass'];
        $data['hashSalt'] = $passdata['salt'];
        $data['email'] = $User->getUserEmail();
        $data['notes'] = 'LDAP';
        $data['groupId'] = $groupId;
        $data['profileId'] = $profileId;
        $data['isDisabled'] = ($groupId && $profileId);

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $Log = new Log();

        if (!$groupId || !$profileId) {
            $Log->setAction(_('Activación Cuenta'));
            $Log->addDescription(_('Su cuenta está pendiente de activación.'));
            $Log->addDescription(_('En breve recibirá un email de confirmación.'));

            Email::sendEmail($Log, $User->getUserEmail(), false);
        }

        $Log->resetDescription();
        $Log->setAction(_('Nuevo usuario de LDAP'));
        $Log->addDescription(sprintf("%s (%s)", $User->getUserName(), $User->getUserLogin()));
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Actualiza los datos de los usuarios de LDAP en la BBDD.
     *
     * @return bool
     */
    public static function updateLDAPUserInDB(User $User)
    {
        $passdata = UserPass::makeUserPassHash($User->getUserPass());

        $query = 'UPDATE usrData SET '
            . 'user_pass = :pass,'
            . 'user_hashSalt = :hashSalt,'
            . 'user_name = :name,'
            . 'user_email = :email,'
            . 'user_lastUpdate = NOW(),'
            . 'user_isLdap = 1 '
            . 'WHERE user_id = :id LIMIT 1';

        $data['pass'] = $passdata['pass'];
        $data['hashSalt'] = $passdata['salt'];
        $data['name'] = $User->getUserName();
        $data['email'] = $User->getUserEmail();
        $data['id'] = UserUtil::getUserIdByLogin($User->getUserLogin());

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Comprobar si un usuario autentifica mediante LDAP
     * .
     *
     * @param string $userLogin con el login del usuario
     * @return bool
     */
    public static function checkUserIsLDAP($userLogin)
    {
        $query = 'SELECT BIN(user_isLdap) AS user_isLdap FROM usrData WHERE user_login = :login LIMIT 1';

        $data['login'] = $userLogin;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        return ($queryRes !== false && intval($queryRes->user_isLdap) === 1);
    }

    /**
     * Comprobar si los datos del usuario de LDAP están en la BBDD.
     *
     * @return bool
     */
    public static function checkLDAPUserInDB($userId)
    {
        $query = 'SELECT user_login FROM usrData WHERE user_login = :login LIMIT 1';

        $data['login'] = $userId;

        return (DB::getQuery($query, __FUNCTION__, $data) === true && DB::$lastNumRows === 1);
    }
}