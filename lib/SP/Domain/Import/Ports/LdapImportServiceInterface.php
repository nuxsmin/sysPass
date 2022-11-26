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

namespace SP\Domain\Import\Ports;

use SP\Domain\Import\Services\LdapImportParams;
use SP\Providers\Auth\Ldap\LdapException;
use SP\Providers\Auth\Ldap\LdapParams;

/**
 * Class UserLdapService
 *
 * @package SP\Domain\User\Services
 */
interface LdapImportServiceInterface
{
    public function getTotalObjects(): int;

    public function getSyncedObjects(): int;

    public function getErrorObjects(): int;

    /**
     * Sincronizar usuarios de LDAP
     *
     * @throws LdapException
     */
    public function importGroups(LdapParams $ldapParams, LdapImportParams $ldapImportParams): void;

    /**
     * @throws LdapException
     */
    public function importUsers(LdapParams $ldapParams, LdapImportParams $ldapImportParams): void;
}
