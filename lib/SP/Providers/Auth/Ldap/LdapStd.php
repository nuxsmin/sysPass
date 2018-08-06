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
 * Class LdapStd
 *
 * Autentificación basada en LDAP estándard
 *
 * @package SP\Auth\Ldap
 */
final class LdapStd extends LdapBase
{
    const FILTER_USER_OBJECT = '(|(objectClass=inetOrgPerson)(objectClass=person)(objectClass=simpleSecurityObject))';
    const FILTER_GROUP_OBJECT = '(|(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=group))';

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

        return '(&(|(memberOf=' . $groupDN . ')(groupMembership=' . $groupDN . '))' . self::FILTER_USER_OBJECT . ')';
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    protected function pickServer()
    {
        return $this->ldapParams->getServer();
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

        $userDN = ldap_escape($this->ldapAuthData->getDn());
        $groupName = $this->getGroupName() ?: $this->ldapParams->getGroup();

        $filter = '(&(cn=' . ldap_escape($groupName) . ')(|(member=' . $userDN . ')(uniqueMember=' . $userDN . '))' . self::FILTER_GROUP_OBJECT . ')';

        $searchResults = $this->getResults($filter, ['member', 'uniqueMember']);

        if ($searchResults === false) {
            $this->eventDispatcher->notifyEvent('ldap.check.group',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al buscar el grupo de usuarios'))
                    ->addDetail(__u('Usuario'), $this->ldapAuthData->getDn())
                    ->addDetail(__u('Grupo'), $groupName)
                    ->addDetail('LDAP ERROR', $this->getLdapErrorMessage())
                    ->addDetail('LDAP FILTER', $filter))
            );

            throw new LdapException(__u('Error al buscar el grupo de usuarios'));
        }

        $this->eventDispatcher->notifyEvent('ldap.check.group',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Usuario no pertenece al grupo'))
                ->addDetail(__u('Usuario'), $this->ldapAuthData->getDn())
                ->addDetail(__u('Grupo'), $groupName))
        );

        return true;
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