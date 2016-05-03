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
 * Class LdapADS para gestión de LDAP de ADS
 *
 * @package SP
 */
class LdapADS extends Ldap
{
    /**
     * Obtener un servidor de AD aleatorio
     *
     * @param $server string El servidor de AD
     * @return string
     */
    public static function getADServer($server)
    {
        if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $server)){
            return $server;
        }

        $serverDomain = '';
        $serverFQDN = explode('.', $server);

        for ($i = 1; $i <= count($serverFQDN) - 1; $i++){
            $serverDomain .= $serverFQDN[$i] . '.';
        }

        $dnsServerQuery = '_msdcs.' . $serverDomain;
        $records = dns_get_record($dnsServerQuery, DNS_NS);

        if (count($records) === 0) {
            return parent::$_ldapServer;
        }

        foreach ($records as $record) {
            $ads[] = $record['target'];
        };

        return $ads[rand(0, count($ads) - 1)];
    }

    /**
     * Buscar al usuario en un grupo.
     *
     * @param string $userLogin con el login del usuario
     * @throws \Exception
     * @return bool
     */
    public static function searchADUserInGroup($userLogin)
    {
        if (Ldap::$_ADS === false) {
            return false;
        }

        $log = new Log(__FUNCTION__);

        $ldapGroup = Config::getValue('ldap_group');

        // El filtro de grupo no está establecido
        if (empty($ldapGroup)) {
            return true;
        }

        // Obtenemos el DN del grupo
        if (!$groupDN = Ldap::searchGroupDN()) {
            return false;
        }

        $filter = '(memberof:1.2.840.113556.1.4.1941:=' . $groupDN . ')';
        $filterAttr = array("sAMAccountName");

        $searchRes = @ldap_search(Ldap::$_ldapConn, Ldap::$_searchBase, $filter, $filterAttr);

        if (!$searchRes) {
            $log->addDescription(_('Error al buscar el grupo de usuarios'));
            $log->addDescription('LDAP ERROR: ' . ldap_error(Ldap::$_ldapConn) . '(' . ldap_errno(Ldap::$_ldapConn) . ')');
            $log->addDescription('LDAP FILTER: ' . $filter);
            $log->writeLog();

            throw new \Exception(_('Error al buscar el grupo de usuarios'));
        }

        if (@ldap_count_entries(Ldap::$_ldapConn, $searchRes) === 0) {
            $log->addDescription(_('No se encontró el grupo con ese nombre'));
            $log->addDescription('LDAP ERROR: ' . ldap_error(Ldap::$_ldapConn) . '(' . ldap_errno(Ldap::$_ldapConn) . ')');
            $log->addDescription('LDAP FILTER: ' . $filter);
            $log->writeLog();

            throw new \Exception(_('No se encontró el grupo con ese nombre'));
        }

        foreach (ldap_get_entries(Ldap::$_ldapConn, $searchRes) as $entry) {
            if ($userLogin === $entry['samaccountname'][0]) {
                return true;
            }
        }

        return false;
    }
}