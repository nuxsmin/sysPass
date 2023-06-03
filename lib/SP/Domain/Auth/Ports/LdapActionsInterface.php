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

namespace SP\Domain\Auth\Ports;


use SP\Providers\Auth\Ldap\AttributeCollection;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;

/**
 * Class LdapActions
 *
 * @package SP\Providers\Auth\Ldap
 */
interface LdapActionsInterface
{
    /**
     * Obtener el RDN del grupo.
     *
     * @param string $groupFilter
     *
     * @return array Groups' DN
     * @throws LdapException
     */
    public function searchGroupsDn(string $groupFilter): array;

    /**
     * @param string $filter
     *
     * @return AttributeCollection
     * @throws LdapException
     */
    public function getAttributes(string $filter): AttributeCollection;

    /**
     * Obtener los objetos según el filtro indicado
     *
     * @param string $filter
     * @param array $attributes
     * @param string|null $searchBase
     *
     * @return array
     * @throws LdapException
     */
    public function getObjects(
        string  $filter,
        array   $attributes,
        ?string $searchBase = null
    ): array;

    public function mutate(LdapParams $ldapParams): LdapActionsInterface;
}
