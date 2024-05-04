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

/** @noinspection PhpComposerExtensionStubsInspection */

namespace SP\Domain\Auth\Providers\Ldap;

use Laminas\Ldap\Exception\LdapException as LaminasLdapException;
use Laminas\Ldap\Ldap as LaminasLdap;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;

use function SP\__u;

/**
 * Class LdapConnection
 *
 * @package SP\Domain\Auth\Providers\Ldap
 */
final class LdapConnection implements LdapConnectionInterface
{
    private const TIMEOUT_SECONDS    = 10;
    private const RECONNECT_ATTEMPTS = 3;

    /**
     * @throws LdapException
     */
    public function __construct(
        private readonly LaminasLdap              $ldap,
        private readonly LdapParams               $ldapParams,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly bool                     $debug = false
    ) {
        $this->setUp();
    }

    /**
     * @throws LdapException
     */
    private function setUp(): void
    {
        if ($this->debug) {
            @ldap_set_option(
                null,
                LDAP_OPT_DEBUG_LEVEL,
                7
            );
        }

        try {
            $this->ldap->setOptions([
                                        'host' => $this->ldapParams->getPort(),
                                        'port' => $this->ldapParams->getPort(),
                                        'useStartTls' => $this->ldapParams->isTlsEnabled(),
                                        'username' => $this->ldapParams->getBindDn(),
                                        'password' => $this->ldapParams->getBindPass(),
                                        'networkTimeout' => self::TIMEOUT_SECONDS,
                                        'reconnectAttempts' => self::RECONNECT_ATTEMPTS,
                                    ]);
        } catch (LaminasLdapException $e) {
            throw LdapException::error($e->getMessage(), null, $e->getCode(), $e);
        }
    }

    /**
     * @throws LdapException
     */
    public function mutate(LdapParams $ldapParams): LdapConnectionInterface
    {
        return new self($this->ldap, $ldapParams, $this->eventDispatcher, $this->debug);
    }

    /**
     * Comprobar la conexión al servidor de LDAP.
     *
     * @throws LdapException
     */
    public function checkConnection(): void
    {
        $this->connect();

        $this->eventDispatcher->notify(
            'ldap.check.connection',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(__u('LDAP connection OK'))
            )
        );
    }

    /**
     * Connects to LDAP server using authentication
     *
     * @param string|null $bindDn con el DN del usuario
     * @param string|null $bindPass con la clave del usuario
     *
     * @throws LdapException
     */
    public function connect(
        ?string $bindDn = null,
        ?string $bindPass = null
    ): void {
        $username = $bindDn ?: $this->ldapParams->getBindDn();
        $password = $bindPass ?: $this->ldapParams->getBindPass();

        try {
            $this->ldap->bind(
                $username,
                $password
            );
        } catch (LaminasLdapException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->eventDispatcher->notify(
                'ldap.bind',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('LDAP connection error'))
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
}
