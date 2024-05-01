<?php
declare(strict_types=1);
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

use SP\Domain\Auth\Providers\Ldap\AttributeCollection;
use SP\Domain\Auth\Providers\Ldap\LdapException;
use SP\Domain\Auth\Providers\Ldap\LdapParams;
use SP\Domain\Auth\Providers\Ldap\LdapResults;

/**
 * Class LdapActions
 *
 * @package SP\Domain\Auth\Providers\Ldap
 */
interface LdapActionsService
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
     * Get LDAP search results
     *
     * @throws LdapException
     */
    public function getObjects(
        string  $filter,
        array $attributes = [],
        ?string $searchBase = null
    ): LdapResults;

    public function mutate(LdapParams $ldapParams): LdapActionsService;
}
