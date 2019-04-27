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


/**
 * Interface LdapInterface
 *
 * @package SP\Providers\Auth\Ldap
 */
interface LdapInterface
{
    const PAGE_SIZE = 500;

    /**
     * Obtener el filtro para buscar el usuario
     *
     * @param string $userLogin
     *
     * @return string
     */
    public function getUserDnFilter(string $userLogin): string;

    /**
     * Devolver el filtro para comprobar la pertenecia al grupo
     *
     * @return string
     */
    public function getGroupMembershipFilter(): string;

    /**
     * Buscar al usuario en un grupo.
     *
     * @param string $userDn
     * @param string $userLogin
     * @param array  $groupsDn
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
     * Connects and binds to an LDAP server
     *
     * @throws LdapException
     */
    public function connect();

    /**
     * @param string $bindDn
     * @param string $bindPass
     *
     * @return bool
     */
    public function bind(string $bindDn = null, string $bindPass = null): bool;

    /**
     * @return LdapActions
     */
    public function getLdapActions(): LdapActions;

    /**
     * @return string
     */
    public function getServer(): string;
}