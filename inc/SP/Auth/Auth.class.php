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

namespace SP\Auth;

use SP\Config\Config;
use SP\Storage\DB;
use SP\Log\Email;
use SP\Html\Html;
use SP\Core\Init;
use SP\Log\Log;
use SP\Mgmt\User\UserLdap;
use SP\Mgmt\User\UserMigrate;
use SP\Mgmt\User\UserPassRecover;
use SP\Mgmt\User\UserUtil;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 */
class Auth
{
    static $userName;
    static $userEmail;

    /**
     * Autentificación de usuarios con LDAP.
     *
     * @param string $userLogin con el login del usuario
     * @param string $userPass  con la clave del usuario
     * @return int|bool Número de error o boolean
     */
    public static function authUserLDAP($userLogin, $userPass)
    {
        if (!Checks::ldapIsAvailable() || !Checks::ldapIsEnabled() || !Ldap::checkLDAPParams()) {
            return false;
        }

        $ldapGroupAccess = false;
        $message['action'] = __FUNCTION__;

        // Conectamos al servidor realizamos la conexión con el usuario proxy
        try {
            Ldap::ldapConnect();
            Ldap::ldapBind();
            Ldap::getUserDN($userLogin);
        } catch (\Exception $e) {
            return false;
        }

        $userDN = Ldap::$ldapSearchData[0]['dn'];

        // Realizamos la conexión con el usuario real y obtenemos los atributos
        try {
            Ldap::ldapBind($userDN, $userPass);
            $attribs = Ldap::getLDAPAttr();
        } catch (\Exception $e) {
            return ldap_errno(Ldap::getConn());
        }

        // Comprobamos si la cuenta está bloqueada o expirada
        if (isset($attribs['expire']) && $attribs['expire'] > 0) {
            return 701;
        }

        if (Ldap::getLdapGroup() !== '*') {
            // Comprobamos que el usuario está en el grupo indicado buscando en los atributos del usuario
            if (isset($attribs['group'])) {
                if (is_array($attribs['group'])) {
                    foreach ($attribs['group'] as $group) {
                        if (is_int($group)) {
                            continue;
                        }

                        // Comprobamos que el usuario está en el grupo indicado
                        if (self::checkLDAPGroup($group)) {
                            $ldapGroupAccess = true;
                            break;
                        }
                    }
                } else {
                    $ldapGroupAccess = self::checkLDAPGroup($attribs['group']);
                }
                // Comprobamos que el usuario está en el grupo indicado buscando en los atributos del grupo
            } else {
                $ldapGroupAccess = (Ldap::searchUserInGroup($userDN) || LdapADS::searchADUserInGroup($userLogin));
            }
        } else {
            $ldapGroupAccess = true;
        }

        if ($ldapGroupAccess === false) {
            $log = new Log(__FUNCTION__);
            $log->addDescription(_('Usuario no pertenece al grupo'));
            $log->addDetails(_('Usuario'), $userDN);
            $log->writeLog();

            return 702;
        }

        self::$userName = (isset($attribs['name'])) ? $attribs['name'] : $userLogin;
        self::$userEmail = (isset($attribs['mail'])) ? $attribs['mail'] : '';

        return true;
    }

    /**
     * Comprobar si el grupo de LDAP está habilitado.
     *
     * @param string $group con el nombre del grupo
     * @return bool
     */
    private static function checkLDAPGroup($group)
    {
        $ldapGroup = strtolower(Config::getValue('ldap_group'));
        $groupName = array();

        preg_match('/^cn=([\w\s-]+),.*/i', $group, $groupName);

        if (strtolower($groupName[1]) == $ldapGroup || strtolower($group) == $ldapGroup) {
            return true;
        }

        return false;
    }

    /**
     * Autentificación de usuarios con MySQL.
     *
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado desde phpPMS,
     * se ejecuta el proceso para actualizar la clave.
     *
     * @param string $userLogin con el login del usuario
     * @param string $userPass  con la clave del usuario
     * @return bool
     */
    public static function authUserMySQL($userLogin, $userPass)
    {
        if (UserMigrate::checkUserIsMigrate($userLogin)) {
            if (!UserMigrate::migrateUser($userLogin, $userPass)) {
                return false;
            }
        }

        $query = 'SELECT user_login, user_pass, user_hashSalt '
            . 'FROM usrData '
            . 'WHERE user_login = :login AND user_isMigrate = 0 LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userLogin, 'login');

        $queryRes = DB::getResults($Data, __FUNCTION__);

        return ($queryRes !== false
            && $queryRes->user_pass == crypt($userPass, $queryRes->user_hashSalt));
    }

    /**
     * Proceso para la recuperación de clave.
     *
     * @param string $login con el login del usuario
     * @param string $email con el email del usuario
     * @return bool
     */
    public static function mailPassRecover($login, $email)
    {
        if (UserUtil::checkUserMail($login, $email)
            && !UserUtil::checkUserIsDisabled($login)
            && !UserLdap::checkUserIsLDAP($login)
            && !UserPassRecover::checkPassRecoverLimit($login)
        ) {
            $hash = Util::generate_random_bytes();

            $log = new Log(_('Cambio de Clave'));

            $log->addDescription(Html::strongText(_('Se ha solicitado el cambio de su clave de usuario.')));
            $log->addDescription();
            $log->addDescription(_('Para completar el proceso es necesario que acceda a la siguiente URL:'));
            $log->addDescription();
            $log->addDescription(Html::anchorText(Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time()));
            $log->addDescription('');
            $log->addDescription(_('Si no ha solicitado esta acción, ignore este mensaje.'));

            return (Email::sendEmail($log, $email, false) && UserPassRecover::addPassRecover($login, $hash));
        } else {
            return false;
        }
    }

    /**
     * Comprobar el token de seguridad
     *
     * @param $actionId int El id de la accion
     * @param $token    string El token de seguridad
     * @return bool
     */
    public static function checkAuthToken($actionId, $token)
    {
        $query = 'SELECT authtoken_id FROM authTokens ' .
            'WHERE authtoken_actionId = :actionId ' .
            'AND authtoken_token = :token ' .
            'LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($actionId, 'actionId');
        $Data->addParam($token, 'token');

        DB::getQuery($Data);

        return (DB::$lastNumRows === 1);
    }
}
