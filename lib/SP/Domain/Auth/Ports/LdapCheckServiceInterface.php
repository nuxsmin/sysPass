<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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


use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;

/**
 * Class LdapCheckService
 *
 * @package SP\Domain\Import\Services
 */
interface LdapCheckServiceInterface
{
    /**
     * @param  LdapParams  $ldapParams
     *
     * @throws LdapException
     */
    public function checkConnection(LdapParams $ldapParams): void;

    /**
     * @throws \SP\Providers\Auth\Ldap\LdapException
     */
    public function getObjects(bool $includeGroups = true): array;

    /**
     * Obtener los datos de una búsqueda de LDAP de un atributo
     *
     * @param  array  $data
     * @param  string[]  $attributes
     *
     * @return array
     */
    public function ldapResultsMapper(array $data, array $attributes = ['dn']): array;

    /**
     * @throws \SP\Providers\Auth\Ldap\LdapException
     */
    public function getObjectsByFilter(string $filter): array;
}
