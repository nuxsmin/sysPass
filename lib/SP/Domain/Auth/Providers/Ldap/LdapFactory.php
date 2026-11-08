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

use SP\Domain\Auth\Dtos\LdapParams;

use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapConnectionHandler;
use SP\Domain\Auth\Ports\LdapService;
use SP\Domain\Auth\Ports\LdapServiceFactory;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Log\LoggerInterface;
use SP\Domain\Core\Log\NullLogger;

/**
 * Creates LDAP services from runtime connection parameters.
 */
final readonly class LdapFactory implements LdapServiceFactory
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LdapConnectionHandler    $ldapConnection,
        private LdapActionsService       $ldapActionsService,
        private LoggerInterface          $logger = new NullLogger()
    ) {
    }

    public function create(LdapParams $ldapParams): LdapService
    {
        return LdapBase::factory(
            $this->eventDispatcher,
            $this->ldapConnection,
            $this->ldapActionsService,
            $ldapParams,
            $this->logger
        );
    }
}
