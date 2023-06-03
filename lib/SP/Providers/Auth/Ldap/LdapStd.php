<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

/** @noinspection PhpComposerExtensionStubsInspection */

namespace SP\Providers\Auth\Ldap;

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;

use function SP\__u;

/**
 * Class LdapStd
 *
 * LDAP authentication based on a standard implementation
 *
 * @package SP\Auth\Ldap
 */
final class LdapStd extends LdapBase
{
    private const DEFAULT_FILTER_USER_OBJECT      = '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))';
    private const DEFAULT_FILTER_GROUP_OBJECT     = '(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group))';
    public const  DEFAULT_FILTER_USER_ATTRIBUTES  = ['samaccountname', 'cn', 'uid', 'userPrincipalName'];
    public const  DEFAULT_FILTER_GROUP_ATTRIBUTES = ['memberOf', 'groupMembership'];

    /**
     * @inheritDoc
     * @throws LdapException
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

        return '(&(|' . LdapUtil::getAttributesForFilter($attributes, $this->getGroupDn()) . ')' . $filter . ')';
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
     * @inheritDoc
     */
    public function getUserDnFilter(string $userLogin): string
    {
        if (empty($this->ldapParams->getFilterUserAttributes())) {
            $attributes = self::DEFAULT_FILTER_USER_ATTRIBUTES;
        } else {
            $attributes = $this->ldapParams->getFilterUserAttributes();
        }

        $filter = $this->getUserObjectFilter();

        return '(&(|' . LdapUtil::getAttributesForFilter($attributes, $userLogin) . ')' . $filter . ')';
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
            || in_array($this->getGroupDn(), $groupsDn, true)) {
            $this->eventDispatcher->notifyEvent(
                'ldap.check.group',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('User in group verified'))
                                ->addDetail(
                                    __u('User'),
                                    $userDn
                                )
                                ->addDetail(__u('Group'), $this->ldapParams->getGroup())
                )
            );

            return true;
        }

        return $this->checkUserInGroupByFilter($userDn);
    }

    /**
     * @param string $userDn
     *
     * @return bool
     * @throws LdapException
     */
    private function checkUserInGroupByFilter(string $userDn): bool
    {
        $filter = $this->getGroupMembershipDirectFilter($userDn);

        $searchResults = $this->ldapActions->getObjects($filter, ['dn']);

        if (isset($searchResults['count']) && (int)$searchResults['count'] === 0) {
            $this->eventDispatcher->notifyEvent(
                'ldap.check.group',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('User does not belong to the group'))
                                ->addDetail(
                                    __u('User'),
                                    $userDn
                                )
                                ->addDetail(__u('Group'), $this->getGroupFromParams())
                                ->addDetail(
                                    'LDAP FILTER',
                                    $filter
                                )
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getGroupMembershipDirectFilter(?string $userDn = null): string
    {
        $groupName = ldap_escape($this->getGroupFromParams(), null, LDAP_ESCAPE_FILTER);
        $member = $userDn !== null ? ldap_escape($userDn, null, LDAP_ESCAPE_FILTER) : '*';

        if (empty($groupName)) {
            return $this->getUserObjectFilter();
        }

        return '(&(cn=' . $groupName . ')' . '(|(memberUid=' . $member . ')(member=' . $member . ')(uniqueMember=' . $member . '))' .
               $this->getGroupObjectFilter() . ')';
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
     * Obtener el servidor de LDAP a utilizar
     *
     * @return string
     */
    protected function pickServer(): string
    {
        return $this->ldapParams->getServer();
    }
}
