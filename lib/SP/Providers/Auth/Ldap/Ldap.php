<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
    protected $ldapParams;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var LdapActions
     */
    protected $ldapActions;
    /**
     * @var LdapConnection
     */
    protected $ldapConnection;

    /**
     * LdapBase constructor.
     *
     * @param LdapConnection  $ldapConnection
     * @param EventDispatcher $eventDispatcher
     *
     * @throws LdapException
     */
    public function __construct(LdapConnection $ldapConnection, EventDispatcher $eventDispatcher)
    {
        $this->ldapConnection = $ldapConnection;

        $this->ldapParams = $ldapConnection->getLdapParams();
        $this->ldapParams->setServer($this->pickServer());

        $this->eventDispatcher = $eventDispatcher;
        $this->ldapActions = new LdapActions($ldapConnection, $eventDispatcher);
    }

    /**
     * Obtener el servidor de LDAP a utilizar
     *
     * @return mixed
     */
    protected abstract function pickServer();

    /**
     * @return LdapConnection
     */
    public function getLdapConnection(): LdapConnection
    {
        return $this->ldapConnection;
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
    protected function connect()
    {
        return $this->ldapConnection->connectAndBind();
    }

    /**
     * @return string
     */
    protected function getGroupFromParams(): string
    {
        if (strpos($this->ldapParams->getGroup(), 'cn') === 0) {
            return LdapUtil::getGroupName($this->ldapParams->getGroup());
        }

        return $this->ldapParams->getGroup();
    }

    /**
     * @return string
     * @throws LdapException
     */
    protected function getGroupDn(): string
    {
        if (strpos($this->ldapParams->getGroup(), 'cn') === 0) {
            return $this->ldapParams->getGroup();
        }

        return $this->ldapActions->searchGroupsDn($this->getGroupObjectFilter())[0];
    }
}