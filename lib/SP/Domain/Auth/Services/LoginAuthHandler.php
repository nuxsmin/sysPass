<?php
declare(strict_types=1);
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

namespace SP\Domain\Auth\Services;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Ports\LdapAuthService;
use SP\Domain\Auth\Ports\LoginAuthHandlerService;
use SP\Domain\Auth\Providers\AuthType;
use SP\Domain\Auth\Providers\Browser\BrowserAuthData;
use SP\Domain\Auth\Providers\Database\DatabaseAuthData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Auth\Providers\Ldap\LdapAuthData;
use SP\Domain\Auth\Providers\Ldap\LdapCodeEnum;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserLoginRequest;
use SP\Domain\User\Ports\UserService;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;

use function SP\__;
use function SP\__u;

/**
 * Class LoginAuthHandler
 */
final class LoginAuthHandler extends LoginBase implements LoginAuthHandlerService
{
    private readonly ConfigDataInterface $configData;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        Application                  $application,
        TrackService                 $trackService,
        RequestService $request,
        private readonly UserService $userService
    ) {
        parent::__construct($application, $trackService, $request);

        $this->configData = $this->config->getConfigData();
    }

    /**
     * @inheritDoc
     */
    public function authDatabase(DatabaseAuthData $authData, UserLoginDto $userLoginDto): void
    {
        $eventMessage = EventMessage::factory()
                                    ->addDetail(__u('Type'), AuthType::Database->value)
                                    ->addDetail(__u('User'), $userLoginDto->getLoginUser());

        if (!$authData->isOk()) {
            if ($authData->isAuthoritative() === false) {
                $eventMessage->addDescription(__u('Non authoritative auth'));

                $this->eventDispatcher->notify('login.auth.database', new Event($this, $eventMessage));

                return;
            }

            $this->addTracking();

            $eventMessage->addDescription(__u('Wrong login'));

            $this->eventDispatcher->notify('login.auth.database', new Event($this, $eventMessage));

            throw AuthException::info(__u('Wrong login'), __FUNCTION__, LoginStatus::INVALID_LOGIN->value);
        }

        $this->eventDispatcher->notify('login.auth.database', new Event($this, $eventMessage));
    }

    /**
     * @inheritDoc
     */
    public function authBrowser(BrowserAuthData $authData, UserLoginDto $userLoginDto): void
    {
        $authType = $this->request->getServer('AUTH_TYPE') ?: __('N/A');

        $eventMessage = EventMessage::factory()
                                    ->addDetail(__u('Type'), AuthType::Browser->value)
                                    ->addDetail(__u('User'), $userLoginDto->getLoginUser())
                                    ->addDetail(
                                        __u('Authentication'),
                                        sprintf('%s (%s)', $authType, $authData->getName())
                                    );

        if (!$authData->isOk()) {
            if ($authData->isAuthoritative() === false) {
                $eventMessage->addDescription(__u('Non authoritative auth'));

                $this->eventDispatcher->notify('login.auth.browser', new Event($this, $eventMessage));

                return;
            }

            $this->addTracking();

            $eventMessage->addDescription(__u('Wrong login'));

            $this->eventDispatcher->notify('login.auth.browser', new Event($this, $eventMessage));

            throw AuthException::info(__u('Wrong login'), __FUNCTION__, LoginStatus::INVALID_LOGIN->value);
        }

        if ($this->configData->isAuthBasicAutoLoginEnabled()) {
            try {
                if (!$this->userService->checkExistsByLogin($userLoginDto->getLoginUser())) {
                    $userLoginRequest = new UserLoginRequest(
                        $userLoginDto->getLoginUser(),
                        $userLoginDto->getLoginPass()
                    );

                    $this->userService->createOnLogin($userLoginRequest);
                }

                $this->eventDispatcher->notify('login.auth.browser', new Event($this, $eventMessage));
            } catch (ConstraintException|DuplicatedItemException|QueryException $e) {
                throw AuthException::error(
                    __u('Internal error'),
                    __FUNCTION__,
                    Service::STATUS_INTERNAL_ERROR,
                    $e
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function authLdap(LdapAuthData $authData, UserLoginDto $userLoginDto): void
    {
        $eventMessage = EventMessage::factory()
                                    ->addDetail(__u('Type'), AuthType::Ldap->value)
                                    ->addDetail(__u('LDAP Server'), $authData->getServer())
                                    ->addDetail(__u('User'), $userLoginDto->getLoginUser());

        if (!$authData->isOk()) {
            if ($authData->isAuthoritative() === false) {
                $eventMessage->addDescription(__u('Non authoritative auth'));

                $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

                return;
            }

            if ($authData->getStatusCode() === LdapCodeEnum::INVALID_CREDENTIALS->value) {
                $eventMessage->addDescription(__u('Wrong login'));

                $this->addTracking();

                $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

                throw AuthException::info(__u('Wrong login'), __FUNCTION__, LoginStatus::INVALID_LOGIN->value);
            }

            if ($authData->getStatusCode() === LdapAuthService::ACCOUNT_EXPIRED) {
                $eventMessage->addDescription(__u('Account expired'));

                $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

                throw  AuthException::info(__u('Account expired'), __FUNCTION__, LoginStatus::USER_DISABLED->value);
            }

            if ($authData->getStatusCode() === LdapAuthService::ACCOUNT_NO_GROUPS) {
                $eventMessage->addDescription(__u('User has no associated groups'));

                $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

                throw AuthException::info(
                    __u('User has no associated groups'),
                    __FUNCTION__,
                    LoginStatus::USER_DISABLED->value
                );
            }

            $eventMessage->addDescription(__u('Internal error'));

            $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

            throw AuthException::info(__u('Internal error'), __FUNCTION__, Service::STATUS_INTERNAL_ERROR);
        }

        $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

        try {
            $userLoginRequest = new UserLoginRequest(
                $userLoginDto->getLoginUser(),
                $userLoginDto->getLoginPass(),
                $authData->getName(),
                $authData->getEmail(),
                true
            );

            if ($this->userService->checkExistsByLogin($userLoginDto->getLoginUser())) {
                $this->userService->updateOnLogin($userLoginRequest);
            } else {
                $this->userService->createOnLogin($userLoginRequest);
            }
        } catch (ConstraintException|DuplicatedItemException|QueryException $e) {
            throw AuthException::error(__u('Internal error'), __FUNCTION__, Service::STATUS_INTERNAL_ERROR, $e);
        }
    }
}
