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

namespace SP\Mgmt\Users;

use SP\Config\Config;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\QueryData;

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
        $groupId = Config::getConfig()->getLdapDefaultGroup();
        $profileId = Config::getConfig()->getLdapDefaultProfile();

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

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($User->getUserName(), 'name');
        $Data->addParam($User->getUserLogin(), 'login');
        $Data->addParam($User->getUserEmail(), 'email');
        $Data->addParam(_('Usuario de LDAP'), 'notes');
        $Data->addParam($groupId, 'groupId');
        $Data->addParam($profileId, 'profileId');
        $Data->addParam(($groupId === 0 || $profileId === 0) ? 1 : 0, 'isDisabled');
        $Data->addParam($passdata['pass'], 'pass');
        $Data->addParam($passdata['salt'], 'hashSalt');

        if (DB::getQuery($Data) === false) {
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
            . 'WHERE user_login = :login LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($User->getUserLogin(), 'login');
        $Data->addParam($User->getUserName(), 'name');
        $Data->addParam($User->getUserEmail(), 'email');
        $Data->addParam($passdata['pass'], 'pass');
        $Data->addParam($passdata['salt'], 'hashSalt');

        return DB::getQuery($Data);
    }

    /**
     * Comprobar si un usuario autentifica mediante LDAP
     *
     * @param string $userLogin con el login del usuario
     * @return bool
     */
    public static function checkUserIsLDAP($userLogin)
    {
        $query = 'SELECT BIN(user_isLdap) AS user_isLdap FROM usrData WHERE user_login = :login LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userLogin, 'login');

        $queryRes = DB::getResults($Data);

        return ($queryRes !== false && intval($queryRes->user_isLdap) === 1);
    }

    /**
     * Comprobar si los datos del usuario de LDAP están en la BBDD.
     *
     * @param $userLogin
     * @return bool
     */
    public static function checkLDAPUserInDB($userLogin)
    {
        $query = 'SELECT user_login FROM usrData WHERE user_login = :login LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userLogin, 'login');

        return (DB::getQuery($Data) === true && DB::$lastNumRows === 1);
    }
}