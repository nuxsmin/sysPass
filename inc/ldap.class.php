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
class SP_LDAP {
    private static $ldapConn;
    private static $ldapServer;
    private static $searchBase;
    private static $bindDN;
    private static $bindPass;
    private static $ldapGroup;
    
    public static $ldapSearchData;

    /**
     * @brief Obtener el recurso de conexión a LDAP
     * @return resource
     */
    public static function getConn(){
        if (is_resource(self::$ldapConn)){
            return self::$ldapConn;
        }
    }
    
    /**
     * @brief Comprobar la conexión al servidor de LDAP
     * @return bool
     */
    public static function checkLDAPConn(){

    }
    
    /**
     * @brief Comprobar si los parámetros necesario de LDAP están establecidos
     * @return bool
     */
    public static function checkLDAPParams(){
        self::$searchBase = SP_Config::getValue('ldapbase');
        self::$ldapServer = SP_Config::getValue('ldapserver');
        self::$bindDN = SP_Config::getValue('ldapbinduser');
        self::$bindPass = SP_Config::getValue('ldapbindpass');
        self::$ldapGroup = SP_Config::getValue('ldapgroup');

        if (!self::$searchBase || !self::$ldapServer || !self::$ldapGroup || !self::$bindDN || !self::$bindPass) {
            $message['action'] = __FUNCTION__;
            $message['text'][] = _('Los parámetros de LDAP no están configurados');
            
            SP_Common::wrLogInfo($message);
            
            return FALSE;
        }
        
        return TRUE;
    }

    /**
     * @brief Realizar la conexión al servidor de LDAP
     * @param string $server con la dirección del servidor
     * @return bool
     */
    public static function connect(){
        $message['action'] = __FUNCTION__;
        
        // Conexión al servidor LDAP
        if (!self::$ldapConn = @ldap_connect(self::$ldapServer)) {
            $message['text'][] = _('No es posible conectar con el servidor de LDAP') . " '" . self::$ldapServer . "'";
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';
            
            SP_Common::wrLogInfo($message);
            
            throw new Exception(_('No es posible conectar con el servidor de LDAP'));
        }
        
        @ldap_set_option(self::$ldapConn, LDAP_OPT_NETWORK_TIMEOUT, 10); // Set timeout
        @ldap_set_option(self::$ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3); // Set LDAP version
        
        return TRUE;
    }

    /**
     * @brief Realizar la autentificación con el servidor de LDAP
     * @param string $dn con el DN del usuario
     * @param string $pass con la clave del usuario
     * @return bool
     */
    public static function bind($userDN = '', $userPass = ''){
        $message['action'] = __FUNCTION__;
        
        $dn = ( $userDN ) ? $userDN : self::$bindDN;
        $pass = ( $userPass ) ? $userPass : self::$bindPass;
        
        if (!@ldap_bind(self::$ldapConn, $dn, $pass)) {
            $message['text'][] = _('Error al conectar (BIND)');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';
            $message['text'][] = 'LDAP DN: ' . $dn;

            SP_Common::wrLogInfo($message);
            
            throw new Exception(_('Error al conectar (BIND)'));
        }
        
        return TRUE;
    }
    
    /**
     * @brief Obtener el RDN del usuario que realiza el login
     * @param string $userLogin con el login del usuario
     * @return none
     */
    public static function getUserDN($userLogin){
        $message['action'] = __FUNCTION__;
        
        $filter = '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
        $filterAttr = array("dn", "displayname", "samaccountname", "mail", "memberof", "lockouttime", "fullname", "groupmembership", "mail");

        $searchRes = @ldap_search(self::$ldapConn, self::$searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $message['text'][] = _('Error al buscar el DN del usuario');
            $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';
            $message['text'][] = 'LDAP FILTER: ' . $filter;

            SP_Common::wrLogInfo($message);
            
            throw new Exception(_('Error al buscar el DN del usuario'));
        }

        if (@ldap_count_entries(self::$ldapConn, $searchRes) === 1) {
            self::$ldapSearchData = @ldap_get_entries(self::$ldapConn, $searchRes);

            if (!self::$ldapSearchData) {
                $message['text'][] = _('Error al localizar el usuario en LDAP');
                $message['text'][] = 'LDAP ERROR: ' . ldap_error(self::$ldapConn) . '(' . ldap_errno(self::$ldapConn) . ')';

                SP_Common::wrLogInfo($message);
                
                throw new Exception(_('Error al localizar el usuario en LDAP'));
            }

           //return $searchUser[0]["dn"];
        } else {
            $message['text'][] = _('Error al buscar el DN del usuario');
            $message['text'][] = 'LDAP FILTER: ' . $filter;

            SP_Common::wrLogInfo($message);
            
            throw new Exception(_('Error al buscar el DN del usuario'));
        }
    }
    
    /**
     * @brief Realizar la desconexión del servidor de LDAP
     * @return none
     */
    public static function unbind(){
        @ldap_unbind(self::$ldapConn);
    }
    
    public static function getLDAPAttr($attribs){
        $res = array();
       
        foreach (self::$ldapSearchData as $entryValue) {
            if (is_array($entryValue)) {
                foreach ($entryValue as $entryAttr => $attrValue) {
                    if (is_array($attrValue)) {
                        if (array_key_exists($entryAttr, $attribs)){
                            if ( $attrValue['count'] > 1 ){
                                $res[$attribs[$entryAttr]] = $attrValue;
                            } else{
                                $res[$attribs[$entryAttr]] = $attrValue[0];
                            }
                        }
                    }
                }
            }
        }
        
        return $res;
    }
}