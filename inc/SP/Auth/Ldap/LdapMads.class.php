<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Auth\Ldap;

use Auth\Ldap\LdapBase;
use SP\Core\Exceptions\SPException;
use SP\Log\Log;

/**
 * Class LdapAds
 *
 * @package SP\Auth\Ldap
 */
class LdapMads extends LdapBase
{

    /**
     * Devolver el filtro para comprobar la pertenecia al grupo
     *
     * @return mixed
     */
    protected function getGroupDnFilter()
    {
        $groupDN = (!empty($this->group)) ? $this->searchGroupDN() : '*';


        return '(&(|(memberOf=' . $groupDN . ')(groupMembership=' . $groupDN . ')(memberof:1.2.840.113556.1.4.1941:=' . $groupDN . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject)))';
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    protected function pickServer()
    {
        if (preg_match('/[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/', $this->server)) {
            return $this->server;
        }

        $serverDomain = '';
        $serverFQDN = explode('.', $this->server);

        for ($i = 1; $i <= count($serverFQDN) - 1; $i++) {
            $serverDomain .= $serverFQDN[$i] . '.';
        }

        $dnsServerQuery = '_msdcs.' . $serverDomain;
        $records = dns_get_record($dnsServerQuery, DNS_NS);

        if (count($records) === 0) {
            return $this->server;
        }

        $ads = [];

        foreach ($records as $record) {
            $ads[] = $record['target'];
        };

        return count($ads) > 0 ? $ads[mt_rand(0, count($ads) - 1)] : $this->server;
    }

    /**
     * Obtener el filtro para buscar el usuario
     *
     * @return mixed
     */
    protected function getUserDnFilter()
    {
        return '(&(|(samaccountname=' . $this->userLogin . ')(cn=' . $this->userLogin . ')(uid=' . $this->userLogin . '))(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))(objectCategory=person))';
    }

    /**
     * Buscar al usuario en un grupo.
     *
     * @throws SPException
     * @return bool
     */
    protected function searchUserInGroup()
    {
        $Log = new Log(__FUNCTION__);

        $groupDN = $this->getGroupName() ?: $this->group;

        // Comprobar si está establecido el filtro de grupo o el grupo coincide con
        // los grupos del usuario
        if (!$this->group
            || $this->group === '*'
            || in_array($groupDN, $this->LdapUserData->getGroups())
        ) {
            $Log->addDescription(_('Usuario verificado en grupo'));
            $Log->writeLog();

            return true;
        }

        $filter = '(memberof:1.2.840.113556.1.4.1941:=' . $groupDN . ')';

        $searchRes = @ldap_search($this->ldapHandler, $this->searchBase, $filter, ['sAMAccountName']);

        if (!$searchRes) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al buscar el grupo de usuarios'));
            $Log->addDetails(_('Grupo'), $groupDN);
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, $Log->getDescription());
        }

        if (@ldap_count_entries($this->ldapHandler, $searchRes) === 0) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('No se encontró el grupo con ese nombre'));
            $Log->addDetails(_('Grupo'), $groupDN);
            $Log->addDetails('LDAP ERROR', sprintf('%s (%d)', ldap_error($this->ldapHandler), ldap_errno($this->ldapHandler)));
            $Log->addDetails('LDAP FILTER', $filter);
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, $Log->getDescription());
        }

        foreach (ldap_get_entries($this->ldapHandler, $searchRes) as $entry) {
            if ($this->userLogin === $entry['samaccountname'][0]) {
                $Log->addDescription(_('Usuario verificado en grupo'));
                $Log->writeLog();

                return true;
            }
        }

        $Log->addDescription(_('Usuario no pertenece al grupo'));
        $Log->addDetails(_('Usuario'), $this->LdapUserData->getDn());
        $Log->addDetails(_('Grupo'), $groupDN);

        return false;
    }
}