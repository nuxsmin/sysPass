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

use Laminas\Ldap\Exception\LdapException as LaminasLdapException;
use Laminas\Ldap\Ldap as LaminasLdap;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Ports\LdapConnectionHandler;
use SP\Domain\Core\Events\EventDispatcherInterface;

use function SP\__u;

/**
 * Class LdapConnection
 */
final class LdapConnection implements LdapConnectionHandler
{
    private const TIMEOUT_SECONDS    = 10;
    private const RECONNECT_ATTEMPTS = 3;

    private bool $isSetup = false;

    public function __construct(
        private readonly LaminasLdap              $ldap,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly bool                     $debug = false
    ) {
    }

    /**
     * Connects to LDAP server using authentication
     *
     * @param LdapParams $ldapParams
     * @param string|null $username
     * @param string|null $password
     *
     * @throws LdapException
     */
    public function connect(LdapParams $ldapParams, ?string $username = null, ?string $password = null): void
    {
        if (!$this->isSetup) {
            $this->setUp($ldapParams);
        }

        try {
            $this->ldap->bind($username ?: $ldapParams->getBindDn(), $password ?: $ldapParams->getBindPass());

            $this->eventDispatcher->notify(
                'ldap.check.connection',
                new Event($this, EventMessage::build(__u('LDAP connection OK')))
            );
        } catch (LaminasLdapException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->eventDispatcher->notify(
                'ldap.bind',
                new Event(
                    $this,
                    EventMessage::build(__u('LDAP connection error'))
                                ->addDetail('LDAP ERROR', $this->ldap->getLastError())
                                ->addDetail('LDAP DN', $username)
                )
            );

            throw LdapException::error(
                __u('LDAP connection error'),
                $this->ldap->getLastError(),
                $this->ldap->getLastErrorCode()
            );
        }
    }

    /**
     * @throws LdapException
     */
    private function setUp(LdapParams $ldapParams): void
    {
        if ($this->debug) {
            @ldap_set_option(null, LDAP_OPT_DEBUG_LEVEL, 7);
        }

        try {
            $this->ldap->setOptions(
                [
                    'host' => $ldapParams->getPort(),
                    'port' => $ldapParams->getPort(),
                    'useStartTls' => $ldapParams->isTlsEnabled(),
                    'username' => $ldapParams->getBindDn(),
                    'password' => $ldapParams->getBindPass(),
                    'networkTimeout' => self::TIMEOUT_SECONDS,
                    'reconnectAttempts' => self::RECONNECT_ATTEMPTS,
                ]
            );

            $this->isSetup = true;
        } catch (LaminasLdapException $e) {
            throw LdapException::error($e->getMessage(), null, $e->getCode(), $e);
        }
    }
}
