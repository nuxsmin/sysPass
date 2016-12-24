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
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 */
class Ldap
{
    // Variabla que contiene los datos de una búsqueda
    public static $ldapSearchData;
    // Variable para determinar si conecta con Active Directory
    protected static $_ADS = false;

    // Variables de conexión con LDAP
    protected static $_ldapConn;
    protected static $_ldapServer;
    protected static $_searchBase;
    private static $_bindDN;
    private static $_bindPass;
    private static $_ldapGroup;

    // Mapeo de los atributos
    private static $_attribsMap = array(
        'groupMembership' => 'group',
        'memberOf' => 'group',
        'displayname' => 'name',
        'fullname' => 'name',
        'givenname' => 'givenname',
        'sn' => 'sn',
        'mail' => 'mail',
        'mailPrimaryAddress' => 'mail',
        'lockoutTime' => 'expire');

    /**
     * @return mixed
     */
    public static function getLdapGroup()
    {
        return self::$_ldapGroup;
    }

    /**
     * @return mixed
     */
    public static function getLdapServer()
    {
        return self::$_ldapServer;
    }

    /**
     * Obtener el recurso de conexión a LDAP.
     *
     * @return resource
     */
    public static function getConn()
    {
        if (is_resource(self::$_ldapConn)) {
            return self::$_ldapConn;
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
        self::$_ldapServer = $ldapServer;
        self::$_bindDN = $bindDN;
        self::$_bindPass = $bindPass;
        self::$_searchBase = $searchBase;
        self::$_ldapGroup = $ldapGroup;

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
        $log = new Log(__FUNCTION__);

        // Habilitar la traza si el modo debug está habilitado
        if (Config::getValue('debug')) {
            @ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        }

        // Conexión al servidor LDAP
        if (!self::$_ldapConn = @ldap_connect(self::$_ldapServer)) {
            $log->addDescription(sprintf('%s \'%s\'', _('No es posible conectar con el servidor de LDAP'), self::$_ldapServer));
            $log->addDescription(sprintf('LDAP ERROR: %s (%d)', ldap_error(self::$_ldapConn), ldap_errno(self::$_ldapConn)));
            $log->writeLog();

            throw new \Exception(_('No es posible conectar con el servidor de LDAP'));
        }

        @ldap_set_option(self::$_ldapConn, LDAP_OPT_NETWORK_TIMEOUT, 10); // Set timeout
        @ldap_set_option(self::$_ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3); // Set LDAP version

        if (self::$_ADS) {
            @ldap_set_option(self::$_ldapConn, LDAP_OPT_REFERRALS, 0);
        }

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
        $log = new Log(__FUNCTION__);

        $dn = $userDN ?: self::$_bindDN;
        $pass = $userPass ?: self::$_bindPass;

        if (!@ldap_bind(self::$_ldapConn, $dn, $pass)) {
            $log->addDescription(_('Error al conectar (BIND)'));
            $log->addDescription(sprintf('LDAP ERROR: %s (%d)', ldap_error(self::$_ldapConn), ldap_errno(self::$_ldapConn)));
            $log->addDescription('LDAP DN: ' . $dn);
            $log->writeLog();

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
        $log = new Log(__FUNCTION__);

        $groupDN = (!empty(self::$_ldapGroup)) ? self::searchGroupDN() : '*';

        if (self::$_ADS === true) {
            $filter = '(&(|(memberOf=' . $groupDN . ')(groupMembership=' . $groupDN . ')(memberof:1.2.840.113556.1.4.1941:=' . $groupDN . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
        } else {
            $filter = '(&(|(memberOf=' . $groupDN . ')(groupMembership=' . $groupDN . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
        }

        $filterAttr = array('dn');

        $searchRes = @ldap_search(self::$_ldapConn, self::$_searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $log->addDescription(_('Error al buscar objetos en DN base'));
            $log->addDescription(sprintf('LDAP ERROR: %s (%d)', ldap_error(self::$_ldapConn), ldap_errno(self::$_ldapConn)));
            $log->addDescription('LDAP FILTER: ' . $filter);
            $log->writeLog();

            throw new \Exception(_('Error al buscar objetos en DN base'));
        }

        return @ldap_count_entries(self::$_ldapConn, $searchRes);
    }

    /**
     * Obtener el RDN del grupo.
     *
     * @throws \Exception
     * @return string con el RDN del grupo
     */
    protected static function searchGroupDN()
    {
        $log = new Log(__FUNCTION__);
        $groupName = self::getGroupName();
        $filter = $groupName ?: self::$_ldapGroup;
        $filter = '(cn=' . $filter . ')';
        $filterAttr = array('dn', 'cn');

        $searchRes = @ldap_search(self::$_ldapConn, self::$_searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $log->addDescription(_('Error al buscar RDN de grupo'));
            $log->addDescription(sprintf('%s: %s', _('Grupo'), $filter));
            $log->addDescription(sprintf('LDAP ERROR: %s (%d)', ldap_error(self::$_ldapConn), ldap_errno(self::$_ldapConn)));
            $log->addDescription('LDAP FILTER: ' . $filter);
            $log->writeLog();

            throw new \Exception(_('Error al buscar RDN de grupo'));
        }

        if (@ldap_count_entries(self::$_ldapConn, $searchRes) > 0) {
            $ldapSearchData = @ldap_get_entries(self::$_ldapConn, $searchRes);

            if (!$ldapSearchData) {
                $log->addDescription(_('Error al buscar RDN de grupo'));
                $log->addDescription(sprintf('%s: %s', _('Grupo'), $filter));
                $log->addDescription(sprintf('LDAP ERROR: %s (%d)', ldap_error(self::$_ldapConn), ldap_errno(self::$_ldapConn)));
                $log->writeLog();

                throw new \Exception(_('Error al buscar RDN de grupo'));
            }

            return $ldapSearchData[0]['dn'];
        } else {
            $log->addDescription(_('Error al buscar RDN de grupo'));
            $log->addDescription(sprintf('%s: %s', _('Grupo'), $filter));
            $log->addDescription('LDAP FILTER: ' . $filter);
            $log->writeLog();

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
        if (isset(self::$_ldapGroup)
            && preg_match('/^cn=([\w\s\d-]+)(,.*)?$/i', self::$_ldapGroup, $groupName)
        ) {
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
        self::$_ADS = Config::getValue('ldap_ads', false);
        self::$_searchBase = Config::getValue('ldap_base');
        self::$_ldapServer = (!self::$_ADS) ? Config::getValue('ldap_server') : LdapADS::getADServer(Config::getValue('ldap_server'));
        self::$_bindDN = Config::getValue('ldap_binduser');
        self::$_bindPass = Config::getValue('ldap_bindpass');
        self::$_ldapGroup = Config::getValue('ldap_group', '*');

        if (!self::$_searchBase || !self::$_ldapServer || !self::$_bindDN || !self::$_bindPass) {
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
     * @return void
     */
    public static function getUserDN($userLogin)
    {
        $log = new Log(__FUNCTION__);

        if (self::$_ADS === true) {
            $filter = '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))(objectCategory=person))';
        } else {
            $filter = '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
        }

        $filterAttr = array("dn", 'displayname', 'samaccountname', 'mail', 'memberof', 'lockouttime', 'fullname', 'groupmembership', 'mail', 'sn', 'givenname');

        $searchRes = @ldap_search(self::$_ldapConn, self::$_searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $log->addDescription(_('Error al buscar el DN del usuario'));
            $log->addDescription(sprintf('%s: %s', _('Usuario'), $userLogin));
            $log->addDescription(sprintf('LDAP ERROR: %s (%d)', ldap_error(self::$_ldapConn), ldap_errno(self::$_ldapConn)));
            $log->addDescription('LDAP FILTER: ' . $filter);
            $log->writeLog();

            throw new \Exception(_('Error al buscar el DN del usuario'));
        }

        if (@ldap_count_entries(self::$_ldapConn, $searchRes) === 1) {
            self::$ldapSearchData = @ldap_get_entries(self::$_ldapConn, $searchRes);

            if (!self::$ldapSearchData) {
                $log->addDescription(_('Error al localizar el usuario en LDAP'));
                $log->addDescription(sprintf('%s: %s', _('Usuario'), $userLogin));
                $log->addDescription(sprintf('LDAP ERROR: %s (%d)', ldap_error(self::$_ldapConn), ldap_errno(self::$_ldapConn)));
                $log->writeLog();

                throw new \Exception(_('Error al localizar el usuario en LDAP'));
            }
        } else {
            $log->addDescription(_('Error al buscar el DN del usuario'));
            $log->addDescription(sprintf('%s: %s', _('Usuario'), $userLogin));
            $log->addDescription('LDAP FILTER: ' . $filter);
            $log->writeLog();

            throw new \Exception(_('Error al buscar el DN del usuario'));
        }
    }

    /**
     * Realizar la desconexión del servidor de LDAP.
     */
    public static function unbind()
    {
        @ldap_unbind(self::$_ldapConn);
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
                                $res[$attribs[$entryAttr]] = implode(',', $attrValue);
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
        $log = new Log(__FUNCTION__);

        $ldapGroup = Config::getValue('ldap_group');

        // Comprobar el filtro de grupo y obtener el nombre
        if (empty($ldapGroup) || !$groupDN = self::getGroupName()) {
            return true;
        }

        $userDN = self::escapeLdapDN($userDN);

        $filter = '(&(cn=' . $groupDN . ')(|(member=' . $userDN . ')(uniqueMember=' . $userDN . '))(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group)))';
        $filterAttr = array('member', 'uniqueMember');

        $searchRes = @ldap_search(self::$_ldapConn, self::$_searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $log->addDescription(_('Error al buscar el grupo de usuarios'));
            $log->addDescription(sprintf('%s: %s', _('Grupo'), $ldapGroup));
            $log->addDescription(sprintf('LDAP ERROR: %s (%d)', ldap_error(self::$_ldapConn), ldap_errno(self::$_ldapConn)));
            $log->addDescription('LDAP FILTER: ' . $filter);
            $log->writeLog();

            throw new \Exception(_('Error al buscar el grupo de usuarios'));
        }

        if (@ldap_count_entries(self::$_ldapConn, $searchRes) === 0) {
            return false;
        }

        $log->addDescription(_('Usuario verificado en grupo'));
        $log->writeLog();

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
        $chars = array(
            '/(,)(?!uid|cn|ou|dc)/i',
            '/(?<!uid|cn|ou|dc)(=)/i',
            '/(")/',
            '/(;)/',
            '/(>)/',
            '/(<)/',
            '/(\+)/',
            '/(#)/',
            '/\G(\s)/',
            '/(\s)(?=\s*$)/',
            '/(\/)/'
        );

        return preg_replace($chars, '\\\$1', $dn);
    }

    /**
     * @return boolean
     */
    public static function isADS()
    {
        return self::$_ADS;
    }

}