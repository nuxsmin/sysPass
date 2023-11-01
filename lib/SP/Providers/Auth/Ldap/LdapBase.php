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

namespace SP\Providers\Auth\Ldap;

use Exception;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventDispatcherInterface;
use SP\Domain\Auth\Ports\LdapActionsInterface;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Domain\Auth\Ports\LdapInterface;

use function SP\__u;

/**
 * Class LdapBase
 *
 * @package SP\Providers\Auth\Ldap
 */
abstract class LdapBase implements LdapInterface
{
    protected string $server;

    /**
     * LdapBase constructor.
     *
     * @param LdapConnectionInterface $ldapConnection
     * @param LdapActionsInterface $ldapActions
     * @param LdapParams $ldapParams
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(
        protected readonly LdapConnectionInterface  $ldapConnection,
        protected readonly LdapActionsInterface     $ldapActions,
        protected readonly LdapParams               $ldapParams,
        protected readonly EventDispatcherInterface $eventDispatcher
    ) {
        $this->server = $this->pickServer();
    }

    abstract protected function pickServer(): string;

    /**
     * @param EventDispatcher $eventDispatcher
     * @param LdapConnectionInterface $ldapConnection
     * @param LdapActionsInterface $ldapActions
     * @param LdapParams|null $ldapParams
     * @return LdapInterface
     * @throws LdapException
     * @throws Exception
     */
    public static function factory(
        EventDispatcherInterface $eventDispatcher,
        LdapConnectionInterface  $ldapConnection,
        LdapActionsInterface     $ldapActions,
        ?LdapParams              $ldapParams = null
    ): LdapInterface {
        if (null !== $ldapParams) {
            $ldapConnection = $ldapConnection->mutate($ldapParams);
            $ldapActions = $ldapActions->mutate($ldapParams);
        }

        $ldapConnection->checkConnection();

        switch ($ldapParams->getType()) {
            case LdapTypeEnum::STD:
                return new LdapStd($ldapConnection, $ldapActions, $ldapParams, $eventDispatcher);
            case LdapTypeEnum::ADS:
                return new LdapMsAds($ldapConnection, $ldapActions, $ldapParams, $eventDispatcher);
            case LdapTypeEnum::AZURE:
                throw new LdapException(__u('To be implemented'));
        }

        throw LdapException::error(__u('LDAP type not set'));
    }

    public function actions(): LdapActionsInterface
    {
        return $this->ldapActions;
    }

    /**
     * @throws LdapException
     */
    public function connect(?string $bindDn = null, ?string $bindPass = null): void
    {
        $this->ldapConnection->connect($bindDn, $bindPass);
    }

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

        return $this->ldapParams->getGroup() ?? '';
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

        return $this->ldapActions->searchGroupsDn($this->getGroupObjectFilter())[0] ?? '';
    }
}
