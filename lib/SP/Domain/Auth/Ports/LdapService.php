<?php
/*
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

namespace SP\Domain\Auth\Ports;


use SP\Domain\Auth\Providers\Ldap\LdapException;

/**
 * Interface LdapInterface
 *
 * @package SP\Domain\Auth\Providers\Ldap
 */
interface LdapService
{
    /**
     * Obtener el filtro para buscar el usuario
     *
     * @param string $userLogin
     *
     * @return string
     */
    public function getUserDnFilter(string $userLogin): string;

    /**
     * Return the filter to check the group membership from user's attributes
     *
     * @return string
     */
    public function getGroupMembershipIndirectFilter(): string;

    /**
     * Return the filter to check the group membership from group's attributes
     *
     * @param string|null $userDn
     *
     * @return string
     */
    public function getGroupMembershipDirectFilter(?string $userDn = null): string;

    /**
     * Buscar al usuario en un grupo.
     *
     * @param string $userDn
     * @param string $userLogin
     * @param array $groupsDn
     *
     * @return bool
     */
    public function isUserInGroup(string $userDn, string $userLogin, array $groupsDn): bool;

    /**
     * Devolver el filtro para objetos del tipo grupo
     *
     * @return string
     */
    public function getGroupObjectFilter(): string;

    /**
     * @param string|null $bindDn
     * @param string|null $bindPass
     *
     * @throws LdapException
     **/
    public function connect(?string $bindDn = null, ?string $bindPass = null): void;

    /**
     * @return LdapActionsService
     */
    public function actions(): LdapActionsService;

    /**
     * @return string
     */
    public function getServer(): string;
}
