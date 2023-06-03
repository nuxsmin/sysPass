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

use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventDispatcherInterface;
use SP\Core\Events\EventMessage;
use SP\DataModel\UserLoginData;
use SP\Domain\Auth\Ports\LdapAuthInterface;
use SP\Domain\Auth\Ports\LdapInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;

use function SP\__u;
use function SP\processException;

/**
 * Class LdapBase
 *
 * @package Auth\Ldap
 */
final class LdapAuth implements LdapAuthInterface
{
    private readonly LdapAuthData $ldapAuthData;

    /**
     * LdapBase constructor.
     *
     * @param LdapInterface $ldap
     * @param EventDispatcher $eventDispatcher
     * @param ConfigDataInterface $configData
     */
    public function __construct(
        private readonly LdapInterface $ldap,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ConfigDataInterface $configData
    ) {
        $this->ldapAuthData = new LdapAuthData($this->isAuthGranted());
    }

    /**
     * Indica si es requerida para acceder a la aplicación
     *
     * @return bool
     */
    public function isAuthGranted(): bool
    {
        return !$this->configData->isLdapDatabaseEnabled();
    }

    /**
     * @return LdapAuthData
     */
    public function getLdapAuthData(): LdapAuthData
    {
        return $this->ldapAuthData;
    }


    /**
     * Autentificar al usuario
     *
     * @param UserLoginData $userLoginData Datos del usuario
     *
     * @return bool
     */
    public function authenticate(UserLoginData $userLoginData): bool
    {
        try {
            $this->ldapAuthData->setServer($this->ldap->getServer());

            $this->ldap->connect();

            $this->getAttributes($userLoginData->getLoginUser());

            $this->ldap->connect($this->ldapAuthData->getDn(), $userLoginData->getLoginPass());

            $this->ldapAuthData->setFailed(false);
            $this->ldapAuthData->setAuthenticated(true);
        } catch (LdapException $e) {
            processException($e);

            $this->ldapAuthData->setStatusCode($e->getCode());
            $this->ldapAuthData->setAuthenticated(false);
            $this->ldapAuthData->setFailed(true);

            return false;
        }

        return true;
    }

    /**
     * Obtener los atributos del usuario.
     *
     * @param string $userLogin
     *
     * @return void con los atributos disponibles y sus valores
     * @throws LdapException
     */
    private function getAttributes(string $userLogin): void
    {
        $filter = $this->ldap->getUserDnFilter($userLogin);
        $attributes = $this->ldap->getLdapActions()->getAttributes($filter);

        if ($attributes->count() === 0) {
            $this->eventDispatcher->notifyEvent(
                'ldap.getAttributes',
                new Event(
                    $this,
                    EventMessage::factory()->addDescription(__u('Error while searching the user on LDAP'))->addDetail(
                        'LDAP FILTER',
                        $filter
                    )
                )
            );

            throw LdapException::error(
                __u('Error while searching the user on LDAP'),
                null,
                LdapCodeEnum::NO_SUCH_OBJECT->value
            );
        }

        if (!empty($attributes->get('fullname'))) {
            $this->ldapAuthData->setName($attributes->get('fullname'));
        } else {
            $name = trim(
                $attributes->get('name', '') . ' ' . $attributes->get('sn', '')
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
    }
}
