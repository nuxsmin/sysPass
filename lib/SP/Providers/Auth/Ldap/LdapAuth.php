<?php
/*
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

namespace SP\Providers\Auth\Ldap;

use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Ports\LdapAuthService;
use SP\Domain\Auth\Ports\LdapService;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;

use function SP\__u;
use function SP\processException;

/**
 * Class LdapBase
 *
 * @implements LdapService<LdapAuthData>
 */
final readonly class LdapAuth implements LdapAuthService
{
    /**
     * LdapBase constructor.
     *
     * @param LdapService $ldap
     * @param EventDispatcher $eventDispatcher
     * @param ConfigDataInterface $configData
     */
    public function __construct(
        private LdapService              $ldap,
        private EventDispatcherInterface $eventDispatcher,
        private ConfigDataInterface      $configData
    ) {
    }

    /**
     * Authenticate using user's data
     *
     * @param UserLoginDto $userLoginDto
     * @return LdapAuthData
     */
    public function authenticate(UserLoginDto $userLoginDto): LdapAuthData
    {
        $ldapAuthData = new LdapAuthData($this->isAuthGranted());

        try {
            $ldapAuthData->setServer($this->ldap->getServer());

            $this->ldap->connect();

            $this->getAttributes($userLoginDto->getLoginUser(), $ldapAuthData);

            // Comprobamos si la cuenta está bloqueada o expirada
            if ($ldapAuthData->getExpire() > 0) {
                $ldapAuthData->setStatusCode(LdapAuthService::ACCOUNT_EXPIRED);

                return $ldapAuthData->fail();
            } elseif (!$ldapAuthData->isInGroup()) {
                $ldapAuthData->setStatusCode(LdapAuthService::ACCOUNT_NO_GROUPS);

                return $ldapAuthData->fail();
            }

            $this->ldap->connect($ldapAuthData->getDn(), $userLoginDto->getLoginPass());

            return $ldapAuthData->success();
        } catch (LdapException $e) {
            processException($e);

            $ldapAuthData->setStatusCode($e->getCode());
        }

        return $ldapAuthData->fail();
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
     * Obtener los atributos del usuario.
     *
     * @param string $userLogin
     * @param LdapAuthData $ldapAuthData
     * @return void con los atributos disponibles y sus valores
     * @throws LdapException
     */
    private function getAttributes(string $userLogin, LdapAuthData $ldapAuthData): void
    {
        $filter = $this->ldap->getUserDnFilter($userLogin);
        $attributes = $this->ldap->actions()->getAttributes($filter);

        if ($attributes->count() === 0) {
            $this->eventDispatcher->notify(
                'ldap.getAttributes',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Error while searching the user on LDAP'))
                                ->addDetail(
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
            $ldapAuthData->setName($attributes->get('fullname'));
        } else {
            $name = trim(
                sprintf('%s %s', $attributes->get('name', ''), $attributes->get('sn', ''))
            );

            $ldapAuthData->setName($name);
        }

        $mail = $attributes->get('mail');

        if ($mail !== null) {
            $ldapAuthData->setEmail(is_array($mail) ? $mail[0] : $mail);
        }

        $ldapAuthData->setDn($attributes->get('dn'));
        $ldapAuthData->setExpire($attributes->get('expire'));
        $ldapAuthData->setInGroup(
            $this->ldap->isUserInGroup(
                $attributes['dn'],
                $userLogin,
                (array)$attributes->get('group')
            )
        );
    }
}
