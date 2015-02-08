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
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 */
class SP_Auth
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
        if (!SP_Util::ldapIsAvailable() || !SP_Util::ldapIsEnabled() || !SP_LDAP::checkLDAPParams()) {
            return false;
        }

        $message['action'] = __FUNCTION__;

        $ldapGroupAccess = false;

        // Conectamos al servidor realizamos la conexión con el usuario proxy
        try {
            SP_LDAP::ldapConnect();
            SP_LDAP::ldapBind();
            SP_LDAP::getUserDN($userLogin);
        } catch (Exception $e) {
            return false;
        }

        $userDN = SP_LDAP::$ldapSearchData[0]['dn'];
        // Mapeo de los atributos
        $attribsMap = array(
            'groupmembership' => 'group',
            'memberof' => 'group',
            'displayname' => 'name',
            'fullname' => 'name',
            'mail' => 'mail',
            'lockouttime' => 'expire');

        // Realizamos la conexión con el usuario real y obtenemos los atributos
        try {
            SP_LDAP::ldapBind($userDN, $userPass);
            $attribs = SP_LDAP::getLDAPAttr($attribsMap);
        } catch (Exception $e) {
            return ldap_errno(SP_LDAP::getConn());
        }

        // Comprobamos si la cuenta está bloqueada o expirada
        if (isset($attribs['expire']) && $attribs['expire'] > 0) {
            return 701;
        }

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
            try {
                SP_LDAP::searchUserInGroup($userDN);
            } catch (Exception $e){
                $ldapGroupAccess = false;
            }
        }

        if ($ldapGroupAccess === false){
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
        $ldapGroup = strtolower(SP_Config::getValue('ldap_group'));
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
        if (SP_Users::checkUserIsMigrate($userLogin)) {
            if (!SP_Users::migrateUser($userLogin, $userPass)) {
                return false;
            }
        }

        $query = "SELECT user_login,"
            . "user_pass "
            . "FROM usrData "
            . "WHERE user_login = '" . DB::escape($userLogin) . "' "
            . "AND user_isMigrate = 0 "
            . "AND user_pass = SHA1(CONCAT(user_hashSalt,'" . DB::escape($userPass) . "')) LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        if (count(DB::$last_result) == 0) {
            return false;
        }

        return true;
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
        if (SP_Users::checkUserMail($login, $email)
            && !SP_Users::checkUserIsDisabled($login)
            && !SP_Users::checkUserIsLDAP($login)
            && !SP_Users::checkPassRecoverLimit($login)
        ) {
            $hash = SP_Util::generate_random_bytes();

            $message['action'] = _('Cambio de Clave');
            $message['text'][] = SP_Html::strongText(_('Se ha solicitado el cambio de su clave de usuario.'));
            $message['text'][] = '';
            $message['text'][] = _('Para completar el proceso es necesario que acceda a la siguiente URL:');
            $message['text'][] = '';
            $message['text'][] = SP_Html::anchorText(SP_Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time());
            $message['text'][] = '';
            $message['text'][] = _('Si no ha solicitado esta acción, ignore este mensaje.');

            return (SP_Common::sendEmail($message, $email, false) && SP_Users::addPassRecover($login, $hash));
        } else {
            return false;
        }
    }
}
