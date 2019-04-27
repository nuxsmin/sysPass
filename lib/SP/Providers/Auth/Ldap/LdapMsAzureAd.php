<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Exceptions\SPException;
use SP\Http\Address;

/**
 * Class LdapMsAzureAd
 *
 * LDAP authentication based on Azure Active Directory
 *
 * @package SP\Auth\Ldap
 */
final class LdapMsAzureAd extends Ldap
{
    const FILTER_USER_OBJECT = '(|(objectCategory=person)(objectClass=user))';
    const FILTER_GROUP_OBJECT = '(objectCategory=group)';
    const FILTER_USER_ATTRIBUTES = ['samaccountname', 'cn', 'uid', 'userPrincipalName'];
    const FILTER_GROUP_ATTRIBUTES = ['memberOf', 'groupMembership', 'memberof:1.2.840.113556.1.4.1941:'];

    /**
     * Devolver el filtro para comprobar la pertenecia al grupo
     *
     * @return string
     * @throws SPException
     */
    public function getGroupMembershipFilter(): string
    {
        if (empty($this->ldapParams->getGroup())) {
            return self::FILTER_USER_OBJECT;
        }

        return '(&(|'
            . LdapUtil::getAttributesForFilter(
                self::FILTER_GROUP_ATTRIBUTES,
                $this->getGroupDn())
            . ')'
            . self::FILTER_USER_OBJECT
            . ')';
    }

    /**
     * Obtener el filtro para buscar el usuario
     *
     * @param string $userLogin
     *
     * @return string
     */
    public function getUserDnFilter(string $userLogin): string
    {
        return '(&(|'
            . LdapUtil::getAttributesForFilter(self::FILTER_USER_ATTRIBUTES, $userLogin)
            . ')'
            . self::FILTER_USER_OBJECT
            . ')';
    }

    /**
     * Devolver el filtro para objetos del tipo grupo
     *
     * @return string
     */
    public function getGroupObjectFilter(): string
    {
        return self::FILTER_GROUP_OBJECT;
    }

    /**
     * Buscar al usuario en un grupo.
     *
     * @param string $userDn
     * @param string $userLogin
     * @param array  $groupsDn
     *
     * @return bool
     * @throws LdapException
     */
    public function isUserInGroup(string $userDn, string $userLogin, array $groupsDn): bool
    {
        // Comprobar si está establecido el filtro de grupo o el grupo coincide con
        // los grupos del usuario
        if (empty($this->ldapParams->getGroup())
            || $this->ldapParams->getGroup() === '*'
            || in_array($this->getGroupDn(), $groupsDn)
        ) {
            $this->eventDispatcher->notifyEvent('ldap.check.group',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('User in group verified'))
                    ->addDetail(__u('User'), $userLogin)
                    ->addDetail(__u('Group'), $this->ldapParams->getGroup())));

            return true;
        }

        return $this->checkUserInGroupByFilter($userLogin, $userDn);
    }

    /**
     * @param string $userLogin
     * @param string $userDn
     *
     * @return bool
     * @throws LdapException
     */
    private function checkUserInGroupByFilter(string $userLogin, string $userDn): bool
    {
        $groupDn = $this->getGroupDn();

        $filter = '(|'
            . LdapUtil::getAttributesForFilter(self::FILTER_GROUP_ATTRIBUTES, $groupDn)
            . ')';

        $searchResults = $this->ldapActions->getObjects($filter, ['dn'], $userDn);

        if (isset($searchResults['count'])
            && (int)$searchResults['count'] === 0
        ) {
            $this->eventDispatcher->notifyEvent('ldap.check.group',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('User does not belong to the group'))
                    ->addDetail(__u('User'), $userLogin)
                    ->addDetail(__u('Group'), $groupDn)
                    ->addDetail('LDAP FILTER', $filter)));

            return false;
        }

        $this->eventDispatcher->notifyEvent('ldap.check.group',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('User in group verified'))
                ->addDetail(__u('User'), $userLogin)
                ->addDetail(__u('Group'), $groupDn)));

        return true;
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    protected function pickServer()
    {
        $server = $this->ldapParams->getServer();

        if (preg_match(Address::PATTERN_IP_ADDRESS, $server)) {
            return $server;
        }

        $dnsServerQuery = '_msdcs' . substr($server, strpos($server, '.'));

        logger(sprintf('Querying DNS zone: %s', $dnsServerQuery));

        $records = dns_get_record($dnsServerQuery, DNS_NS);

        if (empty($records)) {
            return $server;
        }

        $adServers = [];

        foreach ($records as $record) {
            $adServers[] = $record['target'];
        };

        return count($adServers) > 0 ? array_rand($adServers) : $server;
    }
}
