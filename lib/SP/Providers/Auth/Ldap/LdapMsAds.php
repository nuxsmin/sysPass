<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Providers\Auth\Ldap;

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;

/**
 * Class LdapAds
 *
 * Autentificación basada en Active Directory
 *
 * @package SP\Auth\Ldap
 */
final class LdapMsAds extends LdapBase
{
    const FILTER_USER_OBJECT = '(|(objectCategory=person)(objectClass=user))';
    const FILTER_GROUP_OBJECT = '(objectCategory=group)';

    /**
     * Devolver el filtro para comprobar la pertenecia al grupo
     *
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getGroupMembershipFilter()
    {
        if (empty($this->ldapParams->getGroup())) {
            return self::FILTER_USER_OBJECT;
        }

        $groupDN = ldap_escape($this->searchGroupDN());

        return '(&(|(memberOf=' . $groupDN . ')(groupMembership=' . $groupDN . ')(memberof:1.2.840.113556.1.4.1941:=' . $groupDN . '))' . self::FILTER_USER_OBJECT . ')';
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    protected function pickServer()
    {
        $server = $this->ldapParams->getServer();

        if (preg_match('/[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/', $server)) {
            return $server;
        }

        $serverDomain = '';
        $serverFQDN = explode('.', $server);

        for ($i = 1; $i <= count($serverFQDN) - 1; $i++) {
            $serverDomain .= $serverFQDN[$i] . '.';
        }

        $dnsServerQuery = '_msdcs.' . $serverDomain;
        $records = dns_get_record($dnsServerQuery, DNS_NS);

        if (count($records) === 0) {
            return $server;
        }

        $ads = [];

        foreach ($records as $record) {
            $ads[] = $record['target'];
        };

        $nAds = count($ads);

        return $nAds > 0 ? $ads[mt_rand(0, $nAds)] : $server;
    }

    /**
     * Obtener el filtro para buscar el usuario
     *
     * @return mixed
     */
    protected function getUserDnFilter()
    {
        $userLogin = ldap_escape($this->userLogin);

        return '(&(|(samaccountname=' . $userLogin . ')(cn=' . $userLogin . ')(uid=' . $userLogin . '))' . self::FILTER_USER_OBJECT . ')';
    }

    /**
     * Buscar al usuario en un grupo.
     *
     * @throws LdapException
     * @return bool
     */
    protected function searchUserInGroup()
    {
        // Comprobar si está establecido el filtro de grupo o el grupo coincide con
        // los grupos del usuario
        if (!$this->ldapParams->getGroup()
            || $this->ldapParams->getGroup() === '*'
            || in_array($this->ldapAuthData->getGroupDn(), $this->ldapAuthData->getGroups())
        ) {
            $this->eventDispatcher->notifyEvent('ldap.check.group',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Usuario verificado en grupo')))
            );

            return true;
        }

        $groupDN = $this->ldapAuthData->getGroupDn();
        $filter = '(memberof:1.2.840.113556.1.4.1941:=' . ldap_escape($groupDN) . ')';

        $searchResults = $this->getResults($filter, ['sAMAccountName']);

        if ($searchResults === false) {
            $this->eventDispatcher->notifyEvent('ldap.check.group',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al buscar el grupo de usuarios'))
                    ->addDetail(__u('Grupo'), $groupDN)
                    ->addDetail('LDAP ERROR', $this->getLdapErrorMessage())
                    ->addDetail('LDAP FILTER', $filter))
            );

            throw new LdapException(__u('Error al buscar el grupo de usuarios'));
        }

        foreach ($searchResults as $entry) {
            if (is_array($entry)) {
                if ($this->userLogin === strtolower($entry['samaccountname'][0])) {
                    $this->eventDispatcher->notifyEvent('ldap.check.group',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Usuario verificado en grupo'))
                            ->addDetail(__u('Grupo'), $groupDN))
                    );

                    return true;
                }
            }
        }

        $this->eventDispatcher->notifyEvent('ldap.check.group',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Usuario no pertenece al grupo'))
                ->addDetail(__u('Usuario'), $this->ldapAuthData->getDn())
                ->addDetail(__u('Grupo'), $groupDN))
        );

        return false;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function connect()
    {
        parent::connect();

        @ldap_set_option($this->ldapHandler, LDAP_OPT_REFERRALS, 0);

        return true;
    }

    /**
     * Devolver el filtro para objetos del tipo grupo
     *
     * @return mixed
     */
    protected function getGroupObjectFilter()
    {
        return self::FILTER_GROUP_OBJECT;
    }
}