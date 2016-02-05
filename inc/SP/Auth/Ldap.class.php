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
use SP\Log\Log;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 */
class Ldap
{
    // Variabla que contiene los datos de una búsqueda
    public static $ldapSearchData;
    // Variable para determinar si conecta con Active Directory
    protected static $isADS = false;

    // Variables de conexión con LDAP
    protected static $ldapConn;
    protected static $ldapServer;
    protected static $searchBase;
    private static $bindDN;
    private static $bindPass;
    private static $ldapGroup;

    // Mapeo de los atributos
    private static $_attribsMap = array(
        'groupMembership' => 'group',
        'memberOf' => 'group',
        'displayname' => 'name',
        'fullname' => 'name',
        'mail' => 'mail',
        'lockoutTime' => 'expire');

    /**
     * @return mixed
     */
    public static function getLdapGroup()
    {
        return self::$ldapGroup;
    }

    /**
     * @return mixed
     */
    public static function getLdapServer()
    {
        return self::$ldapServer;
    }

    /**
     * Obtener el recurso de conexión a LDAP.
     *
     * @return resource
     */
    public static function getConn()
    {
        if (is_resource(self::$ldapConn)) {
            return self::$ldapConn;
        }
    }

    /**
     * Comprobar la conexión al servidor de LDAP.
     *
     * @param string $ldapServer con la dirección del servidor
     * @param string $bindDN     con el usuario de conexión
     * @param string $bindPass   con la clave del usuario de conexión
     * @param string $searchBase con la base para las búsquedas
     * @param string $ldapGroup  con el grupo con los usuarios de acceso
     * @return false|int Con el número de entradas encontradas
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
        } catch (\Exception $e) {
            return false;
        }

        return $numObjects;
    }

    /**
     * Realizar la conexión al servidor de LDAP.
     *
     * @throws \Exception
     * @return bool
     */
    public static function ldapConnect()
    {
        $Log = new Log(__FUNCTION__);

        // Habilitar la traza si el modo debug está habilitado
        if (Config::getConfig()->isDebug()){
            @ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        }

        // Conexión al servidor LDAP
        if (!self::$ldapConn = @ldap_connect(self::$ldapServer)) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(sprintf('%s \'%s\'', _('No es posible conectar con el servidor de LDAP'), self::$ldapServer));
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error(self::$ldapConn), ldap_errno(self::$ldapConn)));
            $Log->writeLog();

            throw new \Exception(_('No es posible conectar con el servidor de LDAP'));
        }

        @ldap_set_option(self::$ldapConn, LDAP_OPT_NETWORK_TIMEOUT, 10); // Set timeout
        @ldap_set_option(self::$ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3); // Set LDAP version

        return true;
    }

    /**
     * Realizar la autentificación con el servidor de LDAP.
     *
     * @param string $userDN   con el DN del usuario
     * @param string $userPass con la clave del usuario
     * @throws \Exception
     * @return bool
     */
    public static function ldapBind($userDN = '', $userPass = '')
    {
        $Log = new Log(__FUNCTION__);

        $dn = ($userDN) ? $userDN : self::$bindDN;
        $pass = ($userPass) ? $userPass : self::$bindPass;

        if (!@ldap_bind(self::$ldapConn, $dn, $pass)) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al conectar (BIND)'));
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error(self::$ldapConn), ldap_errno(self::$ldapConn)));
            $Log->addDetails('LDAP DN', $dn);
            $Log->writeLog();

            throw new \Exception(_('Error al conectar (BIND)'));
        }

        return true;
    }

    /**
     * Realizar una búsqueda de objetos en la ruta indicada.
     *
     * @throws \Exception
     * @return int con el número de resultados
     */
    private static function searchBase()
    {
        $Log = new Log(__FUNCTION__);

        $groupDN = (!empty(self::$ldapGroup)) ? self::searchGroupDN() : '*';
        $filter = '(&(|(memberOf=' . $groupDN . ')(groupMembership=' . $groupDN . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
        $filterAttr = array("dn");

        $searchRes = @ldap_search(self::$ldapConn, self::$searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar objetos en DN base'));
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error(self::$ldapConn), ldap_errno(self::$ldapConn)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new \Exception(_('Error al buscar objetos en DN base'));
        }

        return @ldap_count_entries(self::$ldapConn, $searchRes);
    }

    /**
     * Obtener el RDN del grupo.
     *
     * @throws \Exception
     * @return string con el RDN del grupo
     */
    protected static function searchGroupDN()
    {
        $Log = new Log(__FUNCTION__);
        $groupName = self::getGroupName();
        $filter = ($groupName) ? $groupName : self::$ldapGroup;
        $filter = '(cn=' . $filter . ')';
        $filterAttr = array("dn", "cn");

        $searchRes = @ldap_search(self::$ldapConn, self::$searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar RDN de grupo'));
            $Log->addDetails(_('Grupo'), $filter);
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error(self::$ldapConn), ldap_errno(self::$ldapConn)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new \Exception(_('Error al buscar RDN de grupo'));
        }

        if (@ldap_count_entries(self::$ldapConn, $searchRes) === 1) {
            $ldapSearchData = @ldap_get_entries(self::$ldapConn, $searchRes);

            if (!$ldapSearchData) {
                $Log->setLogLevel(Log::ERROR);
                $Log->addDescription(_('Error al buscar RDN de grupo'));
                $Log->addDetails(_('Grupo'), $filter);
                $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error(self::$ldapConn), ldap_errno(self::$ldapConn)));
                $Log->writeLog();

                throw new \Exception(_('Error al buscar RDN de grupo'));
            }

            return $ldapSearchData[0]["dn"];
        } else {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar RDN de grupo'));
            $Log->addDetails(_('Grupo'), $filter);
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new \Exception(_('Error al buscar RDN de grupo'));
        }
    }

    /**
     * Obtener el nombre del grupo a partir del CN
     *
     * @return bool
     */
    private static function getGroupName()
    {
        if (isset(self::$ldapGroup) && preg_match('/^cn=([\w\s-]+),.*/i', self::$ldapGroup, $groupName)) {
            return $groupName[1];
        }

        return false;
    }

    /**
     * Comprobar si los parámetros necesario de LDAP están establecidos.
     *
     * @return bool
     */
    public static function checkLDAPParams()
    {
        self::$isADS = Config::getConfig()->isLdapAds();
        self::$searchBase = Config::getConfig()->getLdapBase();
        self::$ldapServer = (!self::$isADS) ? Config::getConfig()->getLdapServer() : LdapADS::getADServer(Config::getConfig()->getLdapServer());
        self::$bindDN = Config::getConfig()->getLdapBindUser();
        self::$bindPass = Config::getConfig()->getLdapBindPass();
        self::$ldapGroup = Config::getConfig()->getLdapGroup();

        if (!self::$searchBase || !self::$ldapServer || !self::$bindDN || !self::$bindPass) {
            Log::writeNewLog(__FUNCTION__, _('Los parámetros de LDAP no están configurados'));

            return false;
        }

        return true;
    }

    /**
     * Obtener el RDN del usuario que realiza el login.
     *
     * @param string $userLogin con el login del usuario
     * @throws \Exception
     */
    public static function getUserDN($userLogin)
    {
        $Log = new Log(__FUNCTION__);

        if (self::$isADS === true) {
            $filter = '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))(objectCategory=person))';
        } else {
            $filter = '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
        }

        $filterAttr = array("dn", "displayname", "samaccountname", "mail", "memberof", "lockouttime", "fullname", "groupmembership", "mail");

        $searchRes = @ldap_search(self::$ldapConn, self::$searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar el DN del usuario'));
            $Log->addDetails(_('Usuario'), $userLogin);
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error(self::$ldapConn), ldap_errno(self::$ldapConn)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new \Exception(_('Error al buscar el DN del usuario'));
        }

        if (@ldap_count_entries(self::$ldapConn, $searchRes) === 1) {
            self::$ldapSearchData = @ldap_get_entries(self::$ldapConn, $searchRes);

            if (!self::$ldapSearchData) {
                $Log->setLogLevel(Log::ERROR);
                $Log->addDescription(_('Error al localizar el usuario en LDAP'));
                $Log->addDetails(_('Usuario'), $userLogin);
                $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error(self::$ldapConn), ldap_errno(self::$ldapConn)));
                $Log->writeLog();

                throw new \Exception(_('Error al localizar el usuario en LDAP'));
            }
        } else {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar el DN del usuario'));
            $Log->addDetails(_('Usuario'), $userLogin);
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new \Exception(_('Error al buscar el DN del usuario'));
        }
    }

    /**
     * Realizar la desconexión del servidor de LDAP.
     */
    public static function unbind()
    {
        @ldap_unbind(self::$ldapConn);
    }

    /**
     * Obtener los atributos del usuario.
     *
     * @return array con los atributos disponibles y sus valores
     */
    public static function getLDAPAttr()
    {
        $attribs = self::$_attribsMap;
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
     * Buscar al usuario en un grupo.
     *
     * @param string $userDN con el RDN del usuario
     * @throws \Exception
     * @return bool
     */
    public static function searchUserInGroup($userDN)
    {
        $Log = new Log(__FUNCTION__);

        $ldapGroup = Config::getConfig()->getLdapGroup();

        // Comprobar el filtro de grupo y obtener el nombre
        if (empty($ldapGroup) || !$groupDN = self::getGroupName()) {
            return true;
        }

        $userDN = self::escapeLdapDN($userDN);

        $filter = '(&(cn=' . $groupDN . ')(|(member=' . $userDN . ')(uniqueMember=' . $userDN . '))(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group)))';
        $filterAttr = array("member", "uniqueMember");

        $searchRes = @ldap_search(self::$ldapConn, self::$searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar el grupo de usuarios'));
            $Log->addDetails(_('Grupo'), $ldapGroup);
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error(self::$ldapConn), ldap_errno(self::$ldapConn)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new \Exception(_('Error al buscar el grupo de usuarios'));
        }

        if (@ldap_count_entries(self::$ldapConn, $searchRes) === 0) {
            return false;
        }

        $Log->addDescription(_('Usuario verificado en grupo'));
        $Log->writeLog();

        return true;
    }

    /**
     * Escapar carácteres especiales en el RDN de LDAP.
     *
     * @param string $dn con el RDN del usuario
     * @return string
     */
    private static function escapeLdapDN($dn)
    {
        $chars = array('/(,)(?!uid|cn|ou|dc)/i', '/(?<!uid|cn|ou|dc)(=)/i', '/(")/', '/(;)/', '/(>)/', '/(<)/', '/(\+)/', '/(#)/', '/\G(\s)/', '/(\s)(?=\s*$)/', '/(\/)/');
        return preg_replace($chars, '\\\$1', $dn);
    }

}
