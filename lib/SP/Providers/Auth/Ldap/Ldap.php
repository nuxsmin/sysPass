<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Providers\Auth\Ldap;

use SP\Core\Events\EventDispatcher;

/**
 * Class Ldap
 *
 * @package SP\Providers\Auth\Ldap
 */
abstract class Ldap implements LdapInterface
{
    /**
     * @var LdapParams
     */
    protected LdapParams $ldapParams;
    /**
     * @var EventDispatcher
     */
    protected EventDispatcher $eventDispatcher;
    /**
     * @var LdapActions
     */
    protected LdapActions $ldapActions;
    /**
     * @var LdapConnectionInterface
     */
    protected LdapConnectionInterface $ldapConnection;
    /**
     * @var string
     */
    protected string $server;

    /**
     * LdapBase constructor.
     *
     * @param  LdapConnectionInterface  $ldapConnection
     * @param  \SP\Providers\Auth\Ldap\LdapActions  $ldapActions
     * @param  EventDispatcher  $eventDispatcher
     */
    public function __construct(
        LdapConnectionInterface $ldapConnection,
        LdapActions $ldapActions,
        EventDispatcher $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->ldapActions = $ldapActions;
        $this->ldapConnection = $ldapConnection;
        $this->ldapParams = $ldapConnection->getLdapParams();
        $this->server = $this->pickServer();
        $this->ldapConnection->setServer($this->server);
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    abstract protected function pickServer(): string;

    /**
     * @param  LdapParams  $ldapParams
     * @param  EventDispatcher  $eventDispatcher
     * @param  bool  $debug
     *
     * @return LdapInterface
     * @throws LdapException
     */
    public static function factory(
        LdapParams $ldapParams,
        EventDispatcher $eventDispatcher,
        bool $debug
    ): LdapInterface {
        $ldapConnection = new LdapConnection($ldapParams, $eventDispatcher, $debug);
        $ldapConnection->checkConnection();

        $ldapActions = new LdapActions($ldapConnection, $eventDispatcher);

        switch ($ldapParams->getType()) {
            case LdapTypeInterface::LDAP_STD:
                return new LdapStd($ldapConnection, $ldapActions, $eventDispatcher);
            case LdapTypeInterface::LDAP_ADS:
                return new LdapMsAds($ldapConnection, $ldapActions, $eventDispatcher);
        }

        throw new LdapException(__u('LDAP type not set'));
    }

    /**
     * @return LdapActions
     */
    public function getLdapActions(): LdapActions
    {
        return $this->ldapActions;
    }

    /**
     * Realizar la conexión al servidor de LDAP.
     *
     * @return resource
     * @throws LdapException
     */
    public function connect()
    {
        return $this->ldapConnection->connectAndBind();
    }

    /**
     * @param  string|null  $bindDn
     * @param  string|null  $bindPass
     *
     * @return bool
     */
    public function bind(?string $bindDn = null, ?string $bindPass = null): bool
    {
        return $this->ldapConnection->bind($bindDn, $bindPass);
    }

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @return string
     */
    protected function getGroupFromParams(): string
    {
        if (stripos($this->ldapParams->getGroup(), 'cn') === 0) {
            return LdapUtil::getGroupName($this->ldapParams->getGroup()) ?: '';
        }

        return $this->ldapParams->getGroup();
    }

    /**
     * @return string
     * @throws LdapException
     */
    protected function getGroupDn(): string
    {
        if (stripos($this->ldapParams->getGroup(), 'cn') === 0) {
            return $this->ldapParams->getGroup();
        }

        return $this->ldapActions->searchGroupsDn($this->getGroupObjectFilter())[0];
    }
}
