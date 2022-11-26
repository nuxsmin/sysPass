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

namespace SP\Providers\Auth\Ldap;

use SP\Core\Events\EventDispatcher;
use SP\DataModel\UserLoginData;
use SP\Domain\Config\Ports\ConfigDataInterface;

/**
 * Class LdapBase
 *
 * @package Auth\Ldap
 */
final class LdapAuth implements LdapAuthInterface
{
    protected string            $userLogin;
    protected LdapAuthData      $ldapAuthData;
    protected EventDispatcher   $eventDispatcher;
    protected string            $server;
    private LdapInterface       $ldap;
    private ConfigDataInterface $configData;

    /**
     * LdapBase constructor.
     *
     * @param  LdapInterface  $ldap
     * @param  EventDispatcher  $eventDispatcher
     * @param  \SP\Domain\Config\Ports\ConfigDataInterface  $configData
     */
    public function __construct(
        LdapInterface $ldap,
        EventDispatcher $eventDispatcher,
        ConfigDataInterface $configData
    ) {
        $this->ldap = $ldap;
        $this->eventDispatcher = $eventDispatcher;
        $this->configData = $configData;

        $this->ldapAuthData = new LdapAuthData();
    }

    /**
     * @return LdapAuthData
     */
    public function getLdapAuthData(): LdapAuthData
    {
        return $this->ldapAuthData;
    }

    /**
     * @return string
     */
    public function getUserLogin(): ?string
    {
        return $this->userLogin;
    }

    /**
     * @param  string  $userLogin
     */
    public function setUserLogin(string $userLogin): void
    {
        $this->userLogin = strtolower($userLogin);
    }

    /**
     * Autentificar al usuario
     *
     * @param  UserLoginData  $userLoginData  Datos del usuario
     *
     * @return bool
     */
    public function authenticate(UserLoginData $userLoginData): bool
    {
        try {
            $this->ldapAuthData->setAuthoritative($this->isAuthGranted());
            $this->ldapAuthData->setServer($this->ldap->getServer());

            $this->setUserLogin($userLoginData->getLoginUser());

            $this->ldap->connect();

            $this->getAttributes($userLoginData->getLoginUser());

            $this->ldap->bind($this->ldapAuthData->getDn(), $userLoginData->getLoginPass());
        } catch (LdapException $e) {
            processException($e);

            $this->ldapAuthData->setStatusCode($e->getCode());

            return false;
        }

        return true;
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @return boolean
     */
    public function isAuthGranted(): bool
    {
        return !$this->configData->isLdapDatabaseEnabled();
    }

    /**
     * Obtener los atributos del usuario.
     *
     * @param  string  $userLogin
     *
     * @return LdapAuthData con los atributos disponibles y sus valores
     * @throws LdapException
     */
    public function getAttributes(string $userLogin): LdapAuthData
    {
        $attributes = $this->ldap->getLdapActions()
            ->getAttributes($this->ldap->getUserDnFilter($userLogin));

        if (!empty($attributes->get('fullname'))) {
            $this->ldapAuthData->setName($attributes->get('fullname'));
        } else {
            $name = trim(
                $attributes->get('name', '')
                .' '
                .$attributes->get('sn', '')
            );

            $this->ldapAuthData->setName($name);
        }

        $mail = $attributes->get('mail');

        if ($mail !== null) {
            $this->ldapAuthData->setEmail(is_array($mail) ? $mail[0] : $mail);
        }

        $this->ldapAuthData->setDn($attributes->get('dn'));
        $this->ldapAuthData->setExpire($attributes->get('expire'));

        $this->ldapAuthData->setInGroup(
            $this->ldap->isUserInGroup(
                $attributes['dn'],
                $userLogin,
                (array)$attributes->get('group')
            )
        );

        return $this->ldapAuthData;
    }
}
