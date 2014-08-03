<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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
class SP_LDAP
{
    // Variabla que contiene los datos de una búsqueda
    public static $ldapSearchData;

    // Variable para determinar si conecta con Active Directory
    private static $isADS = false;
    // Variables de conexión con LDAP
    private static $ldapConn;
    private static $ldapServer;
    private static $searchBase;
    private static $bindDN;
    private static $bindPass;
    private static $ldapGroup;

    /**
     * @brief Obtener el recurso de conexión a LDAP
     * @return resource
     */
    public static function getConn()
    {
        if (is_resource(self::$ldapConn)) {
            return self::$ldapConn;
        }
    }

    /**
     * @brief Comprobar la conexión al servidor de LDAP
     * @param string $ldapServer con la dirección del servidor
     * @param string $bindDN con el usuario de conexión
     * @param string $bindPass con la clave del usuario de conexión
     * @param string $searchBase con la base para las búsquedas
     * @param string $ldapGroup con el grupo con los usuarios de acceso
     * @return bool
     */
    public static function checkLDAPConn($ldapServer, $bindDN, $bindPass, $searchBase, $ldapGroup)
    {
        self::$ldapServer = $ldapServer;
        self::$bindDN = $bindDN;
        self::$bindPass = $bindPass;
        self::$searchBase = $searchBase;
        self::$ldapGroup = $ldapGroup;

        try {
            self::ldapConnect();
            self::ldapBind();
            $numObjects = self::searchBase();
        } catch (Exception $e) {
            return false;
        }

        return $numObjects;
    }

    /**
     * @brief Realizar la conexión al servidor de LDAP
     * @throws Exception
     * @return bool
     */
    public static function ldapConnect()
    {
        $message['action'] = __FUNCTION__;

        // Conexión al servidor LDAP
        if (!self::$ldapConn = @ldap_connect(self::$ldapServer)) {
            $message['text'][] = _('No es posible conectar con el servidor de LDAP') . " '" . self::$ldapServer . "'";
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';

            SP_Log::wrLogInfo($message);

            throw new Exception(_('No es posible conectar con el servidor de LDAP'));
        }

        @ldap_set_option(self::$ldapConn, LDAP_OPT_NETWORK_TIMEOUT, 10); // Set timeout
        @ldap_set_option(self::$ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3); // Set LDAP version

        return true;
    }

    /**
     * @brief Realizar la autentificación con el servidor de LDAP
     * @param string $userDN con el DN del usuario
     * @param string $userPass con la clave del usuario
     * @throws Exception
     * @return bool
     */
    public static function ldapBind($userDN = '', $userPass = '')
    {
        $message['action'] = __FUNCTION__;

        $dn = ($userDN) ? $userDN : self::$bindDN;
        $pass = ($userPass) ? $userPass : self::$bindPass;

        if (!@ldap_bind(self::$ldapConn, $dn, $pass)) {
            $message['text'][] = _('Error al conectar (BIND)');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';
            $message['text'][] = 'LDAP DN: ' . $dn;

            SP_Log::wrLogInfo($message);

            throw new Exception(_('Error al conectar (BIND)'));
        }

        return true;
    }

    /**
     * @brief Realizar una búsqueda de objetos en la ruta indicada
     * @throws Exception
     * @return int con el número de resultados
     */
    private static function searchBase()
    {
        $message['action'] = __FUNCTION__;

        $groupDN = (!empty(self::$ldapGroup)) ? self::searchGroupDN() : '*';
        $filter = '(&(|(memberOf=' . $groupDN . ')(groupMembership=' . $groupDN . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
        $filterAttr = array("dn");

        $searchRes = @ldap_search(self::$ldapConn, self::$searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $message['text'][] = _('Error al buscar objetos en DN base');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';
            $message['text'][] = 'LDAP FILTER: ' . $filter;

            SP_Log::wrLogInfo($message);

            throw new Exception(_('Error al buscar objetos en DN base'));
        }

        return @ldap_count_entries(self::$ldapConn, $searchRes);
    }

    /**
     * @brief Obtener el RDN del grupo
     * @throws Exception
     * @return string con el RDN del grupo
     */
    private static function searchGroupDN()
    {
        $message['action'] = __FUNCTION__;
        $groupName = array();

        if (preg_match('/^cn=([\w\s-]+),.*/i', self::$ldapGroup, $groupName)) {
            $filter = '(cn=' . $groupName[1] . ')';
        } else {
            $filter = '(cn=' . self::$ldapGroup . ')';
        }

        $filterAttr = array("dn", "cn");

        $searchRes = @ldap_search(self::$ldapConn, self::$searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $message['text'][] = _('Error al buscar RDN de grupo');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';
            $message['text'][] = 'LDAP FILTER: ' . $filter;

            SP_Log::wrLogInfo($message);

            throw new Exception(_('Error al buscar RDN de grupo'));
        }

        if (@ldap_count_entries(self::$ldapConn, $searchRes) === 1) {
            $ldapSearchData = @ldap_get_entries(self::$ldapConn, $searchRes);

            if (!$ldapSearchData) {
                $message['text'][] = _('Error al buscar RDN de grupo');
                $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';

                SP_Log::wrLogInfo($message);

                throw new Exception(_('Error al buscar RDN de grupo'));
            }

            $message['text'][] = _('RDN de grupo encontrado');
            $message['text'][] = 'RDN: ' . $ldapSearchData[0]["dn"];

            SP_Log::wrLogInfo($message);

            return $ldapSearchData[0]["dn"];
        } else {
            $message['text'][] = _('Error al buscar RDN de grupo');
            $message['text'][] = 'LDAP FILTER: ' . $filter;

            SP_Log::wrLogInfo($message);

            throw new Exception(_('Error al buscar RDN de grupo'));
        }
    }

    /**
     * @brief Comprobar si los parámetros necesario de LDAP están establecidos
     * @return bool
     */
    public static function checkLDAPParams()
    {
        self::$searchBase = SP_Config::getValue('ldap_base');
        self::$ldapServer = SP_Config::getValue('ldap_server');
        self::$bindDN = SP_Config::getValue('ldap_binduser');
        self::$bindPass = SP_Config::getValue('ldap_bindpass');
        self::$ldapGroup = SP_Config::getValue('ldap_group', '*');
        self::$isADS = SP_Config::getValue('ldap_ads', false);

        if (!self::$searchBase || !self::$ldapServer || !self::$bindDN || !self::$bindPass) {
            $message['action'] = __FUNCTION__;
            $message['text'][] = _('Los parámetros de LDAP no están configurados');

            SP_Log::wrLogInfo($message);

            return false;
        }

        return true;
    }

    /**
     * @brief Obtener el RDN del usuario que realiza el login
     * @param string $userLogin con el login del usuario
     * @throws Exception
     * @return none
     */
    public static function getUserDN($userLogin)
    {
        $message['action'] = __FUNCTION__;

        if (self::$isADS === true) {
            $filter = '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))(objectCategory=person))';
        } else {
            $filter = '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
        }

        $filterAttr = array("dn", "displayname", "samaccountname", "mail", "memberof", "lockouttime", "fullname", "groupmembership", "mail");

        $searchRes = @ldap_search(self::$ldapConn, self::$searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $message['text'][] = _('Error al buscar el DN del usuario');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';
            $message['text'][] = 'LDAP FILTER: ' . $filter;

            SP_Log::wrLogInfo($message);

            throw new Exception(_('Error al buscar el DN del usuario'));
        }

        if (@ldap_count_entries(self::$ldapConn, $searchRes) === 1) {
            self::$ldapSearchData = @ldap_get_entries(self::$ldapConn, $searchRes);

            if (!self::$ldapSearchData) {
                $message['text'][] = _('Error al localizar el usuario en LDAP');
                $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';

                SP_Log::wrLogInfo($message);

                throw new Exception(_('Error al localizar el usuario en LDAP'));
            }
        } else {
            $message['text'][] = _('Error al buscar el DN del usuario');
            $message['text'][] = 'LDAP FILTER: ' . $filter;

            SP_Log::wrLogInfo($message);

            throw new Exception(_('Error al buscar el DN del usuario'));
        }
    }

    /**
     * @brief Realizar la desconexión del servidor de LDAP
     * @return none
     */
    public static function unbind()
    {
        @ldap_unbind(self::$ldapConn);
    }

    /**
     * @brief Obtener los atributos del usuario
     * @param array $attribs con los atributos a obtener
     * @return array con los atributos disponibles y sus valores
     */
    public static function getLDAPAttr($attribs)
    {
        $res = array();

        foreach (self::$ldapSearchData as $entryValue) {
            if (is_array($entryValue)) {
                foreach ($entryValue as $entryAttr => $attrValue) {
                    if (is_array($attrValue)) {
                        if (array_key_exists($entryAttr, $attribs)) {
                            if ($attrValue['count'] > 1) {
                                // Almacenamos un array de valores
                                $res[$attribs[$entryAttr]] = $attrValue;
                            } else {
                                // Almacenamos  1 solo valor
                                $res[$attribs[$entryAttr]] = $attrValue[0];
                            }
                        }
                    }
                }
            }
        }

        return $res;
    }

    /**
     * @brief Buscar al usuario en un grupo
     * @param string $userDN con el RDN del usuario
     * @throws Exception
     * @return bool
     */
    public static function searchUserInGroup($userDN)
    {
        $message['action'] = __FUNCTION__;

        $ldapGroup = SP_Config::getValue('ldap_group');

        // El filtro de grupo no está establecido
        if (empty($ldapGroup)){
            return true;
        }

        // Obtenemos el DN del grupo
        if (!$groupDN = self::searchGroupDN()) {
            return false;
        }

        $userDN = self::escapeLdapDN($userDN);

        $filter = '(&(' . $groupDN . ')(|(member=' . $userDN . ')(uniqueMember=' . $userDN . '))(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group)))';
        $filterAttr = array("member", "uniqueMember");

        $searchRes = @ldap_search(self::$ldapConn, self::$searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $message['text'][] = _('Error al buscar el grupo de usuarios');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';
            $message['text'][] = 'LDAP FILTER: ' . $filter;

            SP_Log::wrLogInfo($message);

            throw new Exception(_('Error al buscar el grupo de usuarios'));
        }

        if (!@ldap_count_entries(self::$ldapConn, $searchRes) === 1) {
            $message['text'][] = _('No se encontró el grupo con ese nombre');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';
            $message['text'][] = 'LDAP FILTER: ' . $filter;

            SP_Log::wrLogInfo($message);

            throw new Exception(_('No se encontró el grupo con ese nombre'));
        }

        return true;
    }

    /**
     * @brief Escapar carácteres especiales en el RDN de LDAP
     * @param string $dn con el RDN del usuario
     * @return string
     */
    private static function escapeLdapDN($dn)
    {
        $chars = array('/(,)(?!cn|ou|dc)/', '/(?<!cn|ou|dc)(=)/', '/(")/', '/(;)/', '/(>)/', '/(<)/', '/(\+)/', '/(#)/', '/\G(\s)/', '/(\s)(?=\s*$)/', '/(\/)/');
        return preg_replace($chars, '\\\$1', $dn);
    }
}
