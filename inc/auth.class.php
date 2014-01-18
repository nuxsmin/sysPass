<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
class SP_Auth {

    static $userLogin;
    static $userPass;
    static $userName;
    static $userEmail;

    /**
     * @brief Autentificación de usuarios con LDAP
     * @return bool
     */
    public static function authUserLDAP() {
        if (SP_Config::getValue('ldapenabled', 0) === 0 || !SP_Util::ldapIsAvailable()) {
            return FALSE;
        }

        $searchBase = SP_Config::getValue('ldapbase');
        $ldapserver = SP_Config::getValue('ldapserver');
        $ldapgroup = SP_Config::getValue('ldapgroup');
        $bindDN = SP_Config::getValue('ldapbinduser');
        $bindPass = SP_Config::getValue('ldapbindpass');

        if (!$searchBase || !$ldapserver || !$ldapgroup || !$bindDN || !$bindPass) {
            return FALSE;
        }

        $ldapAccess = FALSE;
        $message['action'] = __FUNCTION__;

        // Conexión al servidor LDAP
        if (!$ldapConn = @ldap_connect($ldapserver)) {
            $message['text'][] = _('No es posible conectar con el servidor de LDAP') . " '" . $ldapserver . "'";
            $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

            SP_Common::wrLogInfo($message);
            return FALSE;
        }

        @ldap_set_option($ldapConn, LDAP_OPT_NETWORK_TIMEOUT, 10); // Set timeout
        @ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3); // Set LDAP version

        if (!@ldap_bind($ldapConn, $bindDN, $bindPass)) {
            $message['text'][] = _('Error al conectar (bind)');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

            SP_Common::wrLogInfo($message);
            return FALSE;
        }

        $filter = '(&(|(samaccountname=' . self::$userLogin . ')(cn=' . self::$userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)))';
        $filterAttr = array("dn", "displayname", "samaccountname", "mail", "memberof", "lockouttime", "fullname", "groupmembership", "mail");

        $searchRes = @ldap_search($ldapConn, $searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $message['text'][] = _('Error al buscar el DN del usuario');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

            SP_Common::wrLogInfo($message);
            return FALSE;
        }

        if (@ldap_count_entries($ldapConn, $searchRes) === 1) {
            $searchUser = @ldap_get_entries($ldapConn, $searchRes);

            if (!$searchUser) {
                $message['text'][] = _('Error al localizar el usuario en LDAP');
                $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

                SP_Common::wrLogInfo($message);
                return FALSE;
            }

            $userDN = $searchUser[0]["dn"];
        }

        if (@ldap_bind($ldapConn, $userDN, self::$userPass)) {
            @ldap_unbind($ldapConn);

            foreach ($searchUser as $entryValue) {
                if (is_array($entryValue)) {
                    foreach ($entryValue as $entryAttr => $attrValue) {
                        if (is_array($attrValue)) {
                            if ($entryAttr == "groupmembership" || $entryAttr == "memberof") {
                                foreach ($attrValue as $group) {
                                    if (is_int($group)) {
                                        continue;
                                    }

                                    preg_match('/^cn=([\w\s-]+),.*/i', $group, $groupName);

                                    // Comprobamos que el usuario está en el grupo indicado
                                    if ($groupName[1] == $ldapgroup || $group == $ldapgroup) {
                                        $ldapAccess = TRUE;
                                        break;
                                    }
                                }
                            } elseif ($entryAttr == "displayname" | $entryAttr == "fullname") {
                                self::$userName = $attrValue[0];
                            } elseif ($entryAttr == "mail") {
                                self::$userEmail = $attrValue[0];
                            } elseif ($entryAttr == "lockouttime") {
                                if ($attrValue[0] > 0)
                                    return FALSE;
                            }
                        }
                    }
                }
            }
        } else {
            $message['text'][] = _('Error al conectar con el usuario');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error($ldapConn) . '(' . ldap_errno($ldapConn) . ')';

            SP_Common::wrLogInfo($message);
            return ldap_errno($ldapConn);
        }

        return $ldapAccess;
    }

    /**
     * @brief Autentificación de usuarios con MySQL
     * @return bool
     * 
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado desde phpPMS,
     * se ejecuta el proceso para actualizar la clave.
     */
    public static function authUserMySQL() {
        if (SP_Users::checkUserIsMigrate(self::$userLogin)) {
            if (!SP_Users::migrateUser(self::$userLogin, self::$userPass)) {
                return FALSE;
            }
        }

        $query = "SELECT user_login,"
                . "user_pass "
                . "FROM usrData "
                . "WHERE user_login = '" . DB::escape(self::$userLogin) . "' "
                . "AND user_isMigrate = 0 "
                . "AND user_pass = SHA1(CONCAT(user_hashSalt,'" . DB::escape(self::$userPass) . "')) LIMIT 1";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        if (count(DB::$last_result) == 0) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @brief Comprobar si un usuario está deshabilitado
     * @return bool
     */
    public static function checkUserIsDisabled() {
        $query = "SELECT user_isDisabled "
                . "FROM usrData "
                . "WHERE user_login = '" . DB::escape(self::$userLogin) . "' LIMIT 1";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === FALSE) {
            return FALSE;
        }

        if ($queryRes->user_isDisabled == 0) {
            return FALSE;
        }

        return TRUE;
    }
}
