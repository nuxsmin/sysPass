<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Domain\Auth\Providers\Ldap;

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Adapters\Address;

use function SP\__u;
use function SP\logger;

/**
 * Class LdapAds
 *
 * LDAP authentication based on Active Directory
 *
 * @package SP\Auth\Ldap
 */
final class LdapMsAds extends LdapBase
{
    public const DEFAULT_FILTER_USER_OBJECT      = '(&(!(UserAccountControl:1.2.840.113556.1.4.804:=32))(|(objectCategory=person)(objectClass=user)))';
    public const DEFAULT_FILTER_GROUP_OBJECT     = '(objectCategory=group)';
    public const DEFAULT_FILTER_USER_ATTRIBUTES  = ['samaccountname', 'cn', 'uid', 'userPrincipalName'];
    public const DEFAULT_FILTER_GROUP_ATTRIBUTES = ['memberOf', 'groupMembership', 'memberof:1.2.840.113556.1.4.1941:'];

    /**
     * @inheritDoc
     * @throws SPException
     */
    public function getGroupMembershipIndirectFilter(): string
    {
        $filter = $this->getUserObjectFilter();

        if (empty($this->ldapParams->getGroup())) {
            return $filter;
        }

        if (empty($this->ldapParams->getFilterGroupAttributes())) {
            $attributes = self::DEFAULT_FILTER_GROUP_ATTRIBUTES;
        } else {
            $attributes = $this->ldapParams->getFilterGroupAttributes();
        }

        return sprintf("(&(|%s)%s)", LdapUtil::getAttributesForFilter($attributes, $this->getGroupDn()), $filter);
    }

    /**
     * @return string
     */
    private function getUserObjectFilter(): string
    {
        if (empty($this->ldapParams->getFilterUserObject())) {
            return self::DEFAULT_FILTER_USER_OBJECT;
        }

        return $this->ldapParams->getFilterUserObject();
    }

    /**
     * O@inheritDoc
     */
    public function getUserDnFilter(string $userLogin): string
    {
        if (empty($this->ldapParams->getFilterUserAttributes())) {
            $attributes = self::DEFAULT_FILTER_USER_ATTRIBUTES;
        } else {
            $attributes = $this->ldapParams->getFilterUserAttributes();
        }

        return sprintf(
            "(&(|%s)%s)",
            LdapUtil::getAttributesForFilter($attributes, $userLogin),
            $this->getUserObjectFilter()
        );
    }

    /**
     * @inheritDoc
     */
    public function getGroupObjectFilter(): string
    {
        if (empty($this->ldapParams->getFilterGroupObject())) {
            return self::DEFAULT_FILTER_GROUP_OBJECT;
        }

        return $this->ldapParams->getFilterGroupObject();
    }

    /**
     * @inheritDoc
     * @throws LdapException
     */
    public function isUserInGroup(string $userDn, string $userLogin, array $groupsDn): bool
    {
        // Comprobar si está establecido el filtro de grupo o el grupo coincide con
        // los grupos del usuario
        if (empty($this->ldapParams->getGroup())
            || $this->ldapParams->getGroup() === '*'
            || in_array($this->getGroupDn(), $groupsDn, true)
        ) {
            $this->eventDispatcher->notify(
                'ldap.check.group',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('User in group verified'))
                                ->addDetail(__u('User'), $userLogin)
                                ->addDetail(__u('Group'), $this->ldapParams->getGroup())
                )
            );

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
        $filter = $this->getGroupMembershipDirectFilter();

        if ($this->ldapActions->getObjects($filter, ['dn'], $userDn)->getCount() === 0) {
            $this->eventDispatcher->notify(
                'ldap.check.group',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('User does not belong to the group'))
                                ->addDetail(__u('User'), $userLogin)
                                ->addDetail(__u('Group'), $groupDn)
                                ->addDetail('LDAP FILTER', $filter)
                )
            );

            return false;
        }

        $this->eventDispatcher->notify(
            'ldap.check.group',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(__u('User in group verified'))
                            ->addDetail(__u('User'), $userLogin)
                            ->addDetail(__u('Group'), $groupDn)
            )
        );

        return true;
    }

    /**
     * @inheritDoc
     * @throws LdapException
     */
    public function getGroupMembershipDirectFilter(?string $userDn = null): string
    {
        if (empty($this->ldapParams->getFilterGroupAttributes())) {
            $attributes = self::DEFAULT_FILTER_GROUP_ATTRIBUTES;
        } else {
            $attributes = $this->ldapParams->getFilterGroupAttributes();
        }

        return sprintf("(|%s)", LdapUtil::getAttributesForFilter($attributes, $this->getGroupDn()));
    }

    protected function pickServer(): string
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
        }

        return count($adServers) > 0 ? array_rand($adServers) : $server;
    }
}
