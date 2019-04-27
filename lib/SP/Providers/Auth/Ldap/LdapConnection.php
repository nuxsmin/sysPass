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

use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;


/**
 * Class LdapConnection
 *
 * @package SP\Providers\Auth\Ldap
 */
final class LdapConnection implements LdapConnectionInterface
{
    const TIMEOUT = 10;
    /**
     * @var resource
     */
    private $ldapHandler;
    /**
     * @var LdapParams
     */
    private $ldapParams;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var bool
     */
    private $isConnected = false;
    /**
     * @var bool
     */
    private $isBound = false;
    /**
     * @var bool
     */
    private $isTls;
    /**
     * @var bool
     */
    private $debug;
    /**
     * @var string
     */
    private $server;

    /**
     * LdapBase constructor.
     *
     * @param LdapParams      $ldapParams
     * @param EventDispatcher $eventDispatcher
     * @param bool            $debug
     */
    public function __construct(LdapParams $ldapParams, EventDispatcher $eventDispatcher, $debug = false)
    {
        $this->ldapParams = $ldapParams;
        $this->eventDispatcher = $eventDispatcher;
        $this->debug = (bool)$debug;
    }

    /**
     * Comprobar la conexión al servidor de LDAP.
     *
     * @throws LdapException
     */
    public function checkConnection()
    {
        try {
            $this->connectAndBind();

            $this->eventDispatcher->notifyEvent('ldap.check.connection',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('LDAP connection OK')))
            );
        } catch (LdapException $e) {
            throw $e;
        }
    }

    /**
     * @return resource
     * @throws LdapException
     */
    public function connectAndBind()
    {
        if (!$this->isConnected && !$this->isBound) {
            $this->isConnected = $this->connect();
            $this->isBound = $this->bind();
        }

        return $this->ldapHandler;
    }

    /**
     * Realizar la conexión al servidor de LDAP.
     *
     * @return bool
     * @throws LdapException
     */
    public function connect(): bool
    {
        if ($this->isConnected) {
            return true;
        }

        $this->checkParams();

        // Habilitar la traza si el modo debug está habilitado
        if ($this->debug) {
            @ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
        }

        $this->ldapHandler = @ldap_connect($this->getServer(), $this->ldapParams->getPort());

        // Conexión al servidor LDAP
        if (!is_resource($this->ldapHandler)) {
            $this->eventDispatcher->notifyEvent('ldap.connect',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Unable to connect to LDAP server'))
                    ->addDetail(__u('Server'), $this->getServer()))
            );

            throw new LdapException(__u('Unable to connect to LDAP server'));
        }

        @ldap_set_option($this->ldapHandler, LDAP_OPT_NETWORK_TIMEOUT, self::TIMEOUT);
        @ldap_set_option($this->ldapHandler, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($this->ldapHandler, LDAP_OPT_REFERRALS, 0);

        $this->isTls = $this->connectTls();

        return true;
    }

    /**
     * Comprobar si los parámetros necesario de LDAP están establecidos.
     *
     * @throws LdapException
     */
    public function checkParams()
    {
        if (!$this->ldapParams->getSearchBase()
            || !$this->getServer()
            || !$this->ldapParams->getBindDn()
        ) {
            $this->eventDispatcher->notifyEvent('ldap.check.params',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('LDAP parameters are not set'))));

            throw new LdapException(__u('LDAP parameters are not set'));
        }
    }

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server ?: $this->ldapParams->getServer();
    }

    /**
     * @param string $server
     *
     * @return LdapConnection
     */
    public function setServer(string $server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Connect through TLS
     *
     * @throws LdapException
     */
    private function connectTls(): bool
    {
        if ($this->ldapParams->isTlsEnabled()) {
            $result = @ldap_start_tls($this->ldapHandler);

            if ($result === false) {
                $this->eventDispatcher->notifyEvent('ldap.connect.tls',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Unable to connect to LDAP server'))
                        ->addDetail(__u('Server'), $this->getServer())
                        ->addDetail('TLS', __u('ON'))
                        ->addDetail('LDAP ERROR', self::getLdapErrorMessage($this->ldapHandler))));

                throw new LdapException(__u('Unable to connect to LDAP server'));
            }

            return true;
        }

        return false;
    }

    /**
     * Registrar error de LDAP y devolver el mensaje de error
     *
     * @param $ldapHandler
     *
     * @return string
     */
    public static function getLdapErrorMessage($ldapHandler)
    {
        return sprintf('%s (%d)', ldap_error($ldapHandler), ldap_errno($ldapHandler));
    }

    /**
     * Realizar la autentificación con el servidor de LDAP.
     *
     * @param string $bindDn   con el DN del usuario
     * @param string $bindPass con la clave del usuario
     *
     * @return bool
     * @throws LdapException
     */
    public function bind(string $bindDn = null, string $bindPass = null): bool
    {
        $dn = $bindDn ?: $this->ldapParams->getBindDn();
        $pass = $bindPass ?: $this->ldapParams->getBindPass();

        if (@ldap_bind($this->ldapHandler, $dn, $pass) === false) {
            $this->eventDispatcher->notifyEvent('ldap.bind',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Connection error (BIND)'))
                    ->addDetail('LDAP ERROR', self::getLdapErrorMessage($this->ldapHandler))
                    ->addDetail('LDAP DN', $dn))
            );

            throw new LdapException(
                __u('Connection error (BIND)'),
                LdapException::ERROR,
                self::getLdapErrorMessage($this->ldapHandler),
                $this->getErrorCode()
            );
        }

        return true;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        if (is_resource($this->ldapHandler)) {
            return ldap_errno($this->ldapHandler);
        }

        return -1;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    /**
     * @return bool
     */
    public function isBound(): bool
    {
        return $this->isBound;
    }

    /**
     * @return LdapParams
     */
    public function getLdapParams(): LdapParams
    {
        return $this->ldapParams;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Realizar la desconexión del servidor de LDAP.
     */
    public function unbind(): bool
    {
        if (($this->isConnected || $this->isBound)
            && @ldap_unbind($this->ldapHandler) === false
        ) {
            $this->eventDispatcher->notifyEvent('ldap.unbind',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error while disconnecting from LDAP server'))
                    ->addDetail('LDAP ERROR', self::getLdapErrorMessage($this->ldapHandler)))
            );

            return false;
        }

        return true;
    }
}