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

namespace SP\Domain\Auth\Services;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\ProfileData;
use SP\Domain\Auth\Dtos\LoginResponseDto;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Ports\LoginAuthHandlerService;
use SP\Domain\Auth\Ports\LoginMasterPassService;
use SP\Domain\Auth\Ports\LoginService;
use SP\Domain\Auth\Ports\LoginUserService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\Ports\UserService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Providers\Auth\AuthProviderService;
use SP\Providers\Auth\AuthResult;

use function SP\__u;

/**
 * Class Login
 */
final class Login extends LoginBase implements LoginService
{
    private readonly UserLoginDto $userLoginDto;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        Application                              $application,
        TrackService                             $trackService,
        RequestInterface                         $request,
        private readonly AuthProviderService     $authProviderService,
        private readonly LanguageInterface       $language,
        private readonly UserService             $userService,
        private readonly LoginUserService        $loginUserService,
        private readonly LoginMasterPassService  $loginMasterPassService,
        private readonly UserProfileService      $userProfileService,
        private readonly LoginAuthHandlerService $loginAuthHandlerService
    ) {
        parent::__construct($application, $trackService, $request);

        $this->userLoginDto = new UserLoginDto();
        $this->authProviderService->initialize();
    }

    /**
     * @inheritDoc
     *
     * @return LoginResponseDto
     * @throws AuthException
     */
    public function doLogin(?string $from = null): LoginResponseDto
    {
        try {
            $user = $this->request->analyzeString('user');
            $pass = $this->request->analyzeEncrypted('pass');

            if (empty($user) || empty($pass)) {
                $this->addTracking();

                throw AuthException::info(__u('Wrong login'), __FUNCTION__, LoginStatus::INVALID_LOGIN->value);
            }

            $this->userLoginDto->setLoginUser($user);
            $this->userLoginDto->setLoginPass($pass);

            $this->checkTracking();

            $userDataDto = $this->authProviderService->doAuth($this->userLoginDto, [$this, 'handleAuthResponse']);

            if ($userDataDto === null) {
                throw ServiceException::error(
                    __u('Internal error'),
                    __u('Authoritative provider didn\'t return the user\'s data')
                );
            }

            $checkUser = $this->loginUserService->checkUser($userDataDto);

            if ($checkUser->getStatus() !== LoginStatus::PASS) {
                return $checkUser;
            }

            $this->loginMasterPassService->loadMasterPass($this->userLoginDto, $userDataDto);
            $this->setUserSession($userDataDto);
            $this->loadUserPreferences();

            return new LoginResponseDto(LoginStatus::OK, $this->getUriForRoute($from ?? 'index'));
        } catch (ServiceException $e) {
            throw AuthException::from($e);
        }
    }

    /**
     * @throws ServiceException
     */
    private function setUserSession(UserDataDto $userDataDto): void
    {
        try {
            $this->userService->updateLastLoginById($userDataDto->getId());

//        if ($this->context->getTrasientKey(UserMasterPass::SESSION_MASTERPASS_UPDATED)) {
//            $this->context->setTrasientKey('user_master_pass_last_update', time());
//        }

            $this->context->setUserData($userDataDto);
            $this->context->setUserProfile(
                $this->userProfileService
                    ->getById($userDataDto->getUserProfileId())
                    ->hydrate(ProfileData::class)
            );
            $this->context->setLocale($userDataDto->getPreferences()->getLang());

            $this->eventDispatcher->notify(
                'login.session.load',
                new Event($this, EventMessage::factory()->addDetail(__u('User'), $userDataDto->getLogin()))
            );
        } catch (ConstraintException|NoSuchItemException|QueryException $e) {
            throw ServiceException::from($e);
        }
    }

    private function loadUserPreferences(): void
    {
        $this->language->setLanguage(true);
        $this->context->setAuthCompleted(true);
        $this->eventDispatcher->notify('login.preferences.load', new Event($this));
    }

    /**
     * @inheritDoc
     *
     * @throws AuthException
     * @uses LoginAuthHandlerService::authBrowser()
     * @uses LoginAuthHandlerService::authDatabase()
     * @uses LoginAuthHandlerService::authLdap()
     */
    public function handleAuthResponse(AuthResult $authResult): void
    {
        $authType = $authResult->getAuthType()->value;

        if (method_exists($this->loginAuthHandlerService, $authType)) {
            $authData = $authResult->getAuthData();

            $this->loginAuthHandlerService->{$authType}($authData, $this->userLoginDto);
        }
    }
}
