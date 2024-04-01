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

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\UserLoginData;
use SP\Domain\Auth\Dtos\LoginResponse;
use SP\Domain\Auth\Ports\LdapAuthService;
use SP\Domain\Auth\Ports\LoginService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Crypt\Ports\TemporaryMasterPassService;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Models\UserPreferences;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\Ports\UserPassServiceInterface;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Domain\User\Services\UserLoginRequest;
use SP\Domain\User\Services\UserPassService;
use SP\Http\Uri;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Providers\Auth\AuthProviderInterface;
use SP\Providers\Auth\Browser\BrowserAuthData;
use SP\Providers\Auth\Database\DatabaseAuthData;
use SP\Providers\Auth\Ldap\LdapAuthData;
use SP\Providers\Auth\Ldap\LdapCodeEnum;
use SP\Util\PasswordUtil;

use function SP\__u;

/**
 * Class Login
 */
final class Login extends Service implements LoginService
{
    private const STATUS_INVALID_LOGIN         = 1;
    private const STATUS_INVALID_MASTER_PASS   = 2;
    private const STATUS_USER_DISABLED         = 3;
    private const STATUS_NEED_OLD_PASS         = 5;
    private const STATUS_MAX_ATTEMPTS_EXCEEDED = 6;
    private const STATUS_PASS_RESET            = 7;
    private const STATUS_PASS                  = 0;
    private const STATUS_NONE                  = 100;

    private UserLoginData       $userLoginData;
    private ConfigDataInterface $configData;
    private TrackRequest        $trackRequest;
    private ?string             $from = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        Application                                  $application,
        private readonly AuthProviderInterface       $authProvider,
        private readonly LanguageInterface           $language,
        private readonly TrackService                $trackService,
        private readonly RequestInterface            $request,
        private readonly UserServiceInterface        $userService,
        private readonly UserPassRecoverService      $userPassRecoverService,
        private readonly TemporaryMasterPassService  $temporaryMasterPassService,
        private readonly UserPassServiceInterface    $userPassService,
        private readonly UserProfileServiceInterface $userProfileService
    ) {
        parent::__construct($application);

        $this->configData = $this->config->getConfigData();
        $this->userLoginData = new UserLoginData();
        $this->trackRequest = $this->trackService->buildTrackRequest(__CLASS__);
        $this->authProvider->initialize();
    }

    /**
     * Ejecutar las acciones de login
     *
     * @return LoginResponse
     * @throws AuthException
     * @throws SPException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     * @uses Login::authBrowser()
     * @uses Login::authDatabase()
     * @uses Login::authLdap()
     *
     */
    public function doLogin(): LoginResponse
    {
        $user = $this->request->analyzeString('user');
        $pass = $this->request->analyzeEncrypted('pass');

        if (empty($user) || empty($pass)) {
            $this->addTracking();

            throw new AuthException(
                __u('Wrong login'),
                SPException::INFO,
                __FUNCTION__,
                self::STATUS_INVALID_LOGIN
            );
        }

        $this->userLoginData->setLoginUser($user);
        $this->userLoginData->setLoginPass($pass);

        if ($this->trackService->checkTracking($this->trackRequest)) {
            $this->addTracking();

            throw new AuthException(
                __u('Attempts exceeded'),
                SPException::INFO,
                null,
                self::STATUS_MAX_ATTEMPTS_EXCEEDED
            );
        }

        $result = $this->authProvider->doAuth($this->userLoginData);

        if ($result !== false) {
            // Ejecutar la acción asociada al tipo de autentificación
            foreach ($result as $authResult) {
                if (method_exists($this, $authResult->getAuthName())) {
                    $granted = $this->{$authResult->getAuthName()}($authResult->getData());

                    if ($granted) {
                        break;
                    }
                }
            }
        } else {
            $this->addTracking();

            throw new AuthException(
                __u('Wrong login'),
                SPException::INFO,
                __FUNCTION__,
                self::STATUS_INVALID_LOGIN
            );
        }

        if (($loginResponse = $this->checkUser())->getStatus() !== self::STATUS_NONE) {
            return $loginResponse;
        }

        $this->loadMasterPass();
        $this->setUserSession();
        $this->loadUserPreferences();
        $this->cleanUserData();

        $uri = new Uri('index.php');

        if (!empty($this->from)) {
            $uri->addParam('r', $this->from);
        } else {
            $uri->addParam('r', 'index');
        }

        return new LoginResponse(self::STATUS_PASS, $uri->getUri());
    }

    /**
     * Añadir un seguimiento
     *
     * @throws AuthException
     */
    private function addTracking(): void
    {
        try {
            $this->trackService->add($this->trackRequest);
        } catch (Exception $e) {
            throw new AuthException(
                __u('Internal error'),
                SPException::ERROR,
                null,
                Service::STATUS_INTERNAL_ERROR
            );
        }
    }

    /**
     * Comprobar estado del usuario
     *
     * @return LoginResponse
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws AuthException
     */
    private function checkUser(): LoginResponse
    {
        $userLoginResponse = $this->userLoginData->getUserLoginResponse();

        if ($userLoginResponse !== null) {
            // Comprobar si el usuario está deshabilitado
            if ($userLoginResponse->getIsDisabled()) {
                $this->eventDispatcher->notify(
                    'login.checkUser.disabled',
                    new Event(
                        $this,
                        EventMessage::factory()
                                    ->addDescription(__u('User disabled'))
                                    ->addDetail(__u('User'), $userLoginResponse->getLogin())
                    )
                );

                $this->addTracking();

                throw new AuthException(
                    __u('User disabled'),
                    SPException::INFO,
                    null,
                    self::STATUS_USER_DISABLED
                );
            }

            // Check whether a user's password change has been requested
            if ($userLoginResponse->getIsChangePass()) {
                $this->eventDispatcher->notify(
                    'login.checkUser.changePass',
                    new Event($this, EventMessage::factory()->addDetail(__u('User'), $userLoginResponse->getLogin()))
                );

                $hash = PasswordUtil::generateRandomBytes(16);

                $this->userPassRecoverService->add($userLoginResponse->getId(), $hash);

                $uri = new Uri('index.php');
                $uri->addParam('r', 'userPassReset/reset/' . $hash);

                return new LoginResponse(self::STATUS_PASS_RESET, $uri->getUri());
            }
        }

        return new LoginResponse(self::STATUS_NONE);
    }

    /**
     * Cargar la clave maestra o solicitarla
     *
     * @throws AuthException
     * @throws SPException
     */
    private function loadMasterPass(): void
    {
        $masterPass = $this->request->analyzeEncrypted('mpass');
        $oldPass = $this->request->analyzeEncrypted('oldpass');

        try {
            if ($masterPass) {
                if ($this->temporaryMasterPassService->checkTempMasterPass($masterPass)) {
                    $this->eventDispatcher->notify(
                        'login.masterPass.temporary',
                        new Event($this, EventMessage::factory()->addDescription(__u('Using temporary password')))
                    );

                    $masterPass = $this->temporaryMasterPassService->getUsingKey($masterPass);
                }

                if ($this->userPassService->updateMasterPassOnLogin(
                        $masterPass,
                        $this->userLoginData
                    )->getStatus() !== UserPassService::MPASS_OK
                ) {
                    $this->eventDispatcher->notify(
                        'login.masterPass',
                        new Event($this, EventMessage::factory()->addDescription(__u('Wrong master password')))
                    );

                    $this->addTracking();

                    throw new AuthException(
                        __u('Wrong master password'),
                        SPException::INFO,
                        null,
                        self::STATUS_INVALID_MASTER_PASS
                    );
                }

                $this->eventDispatcher->notify(
                    'login.masterPass',
                    new Event($this, EventMessage::factory()->addDescription(__u('Master password updated')))
                );
            } elseif ($oldPass) {
                if ($this->userPassService->updateMasterPassFromOldPass(
                        $oldPass,
                        $this->userLoginData
                    )->getStatus() !== UserPassService::MPASS_OK
                ) {
                    $this->eventDispatcher->notify(
                        'login.masterPass',
                        new Event($this, EventMessage::factory()->addDescription(__u('Wrong master password')))
                    );

                    $this->addTracking();

                    throw new AuthException(
                        __u('Wrong master password'),
                        SPException::INFO,
                        null,
                        self::STATUS_INVALID_MASTER_PASS
                    );
                }

                $this->eventDispatcher->notify(
                    'login.masterPass',
                    new Event($this, EventMessage::factory()->addDescription(__u('Master password updated')))
                );
            } else {
                switch ($this->userPassService->loadUserMPass($this->userLoginData)->getStatus()) {
                    case UserPassService::MPASS_CHECKOLD:
                        throw new AuthException(
                            __u('Your previous password is needed'),
                            SPException::INFO,
                            null,
                            self::STATUS_NEED_OLD_PASS
                        );
                    case UserPassService::MPASS_NOTSET:
                    case UserPassService::MPASS_CHANGED:
                    case UserPassService::MPASS_WRONG:
                        $this->addTracking();

                        throw new AuthException(
                            __u('The Master Password either is not saved or is wrong'),
                            SPException::INFO,
                            null,
                            self::STATUS_INVALID_MASTER_PASS
                        );
                }
            }
        } catch (CryptoException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            throw new AuthException(
                __u('Internal error'),
                SPException::ERROR,
                $e->getMessage(),
                Service::STATUS_INTERNAL_ERROR,
                $e
            );
        }
    }

    /**
     * Cargar la sesión del usuario
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    private function setUserSession(): void
    {
        $userLoginResponse = $this->userLoginData->getUserLoginResponse();

        // Actualizar el último login del usuario
        $this->userService->updateLastLoginById($userLoginResponse->getId());

        if ($this->context->getTrasientKey('mpass_updated')) {
            $userLoginResponse->setLastUpdateMPass(time());
        }

        // Cargar las variables de ussuario en la sesión
        $this->context->setUserData($userLoginResponse);
        $this->context->setUserProfile(
            $this->userProfileService->getById($userLoginResponse->getUserProfileId())->getProfile()
        );
        $this->context->setLocale($userLoginResponse->getPreferences()->getLang());

        if ($this->configData->isDemoEnabled()) {
            $userLoginResponse->setPreferences(new UserPreferences());
        }

        $this->eventDispatcher->notify(
            'login.session.load',
            new Event($this, EventMessage::factory()->addDetail(__u('User'), $userLoginResponse->getLogin()))
        );
    }

    /**
     * Cargar las preferencias del usuario y comprobar si usa 2FA
     */
    private function loadUserPreferences(): void
    {
        $this->language->setLanguage(true);
        $this->context->setAuthCompleted(true);
        $this->eventDispatcher->notify('login.preferences.load', new Event($this));
    }

    /**
     * Limpiar datos de usuario
     */
    private function cleanUserData(): void
    {
        $this->userLoginData->setUserLoginResponse();
    }

    /**
     * @param string|null $from
     */
    public function setFrom(?string $from): void
    {
        $this->from = $from;
    }

    /**
     * Autentificación LDAP
     *
     * @param LdapAuthData $authData
     *
     * @return bool
     * @throws SPException
     * @throws AuthException
     */
    private function authLdap(LdapAuthData $authData): bool
    {
        if ($authData->getStatusCode() > LdapCodeEnum::SUCCESS->value) {
            $eventMessage = EventMessage::factory()
                                        ->addDetail(__u('Type'), __FUNCTION__)
                                        ->addDetail(__u('LDAP Server'), $authData->getServer())
                                        ->addDetail(__u('User'), $this->userLoginData->getLoginUser());

            if ($authData->getStatusCode() === LdapCodeEnum::INVALID_CREDENTIALS->value) {
                $eventMessage->addDescription(__u('Wrong login'));

                $this->addTracking();

                $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

                throw new AuthException(
                    __u('Wrong login'),
                    SPException::INFO,
                    __FUNCTION__,
                    self::STATUS_INVALID_LOGIN
                );
            }

            if ($authData->getStatusCode() === LdapAuthService::ACCOUNT_EXPIRED) {
                $eventMessage->addDescription(__u('Account expired'));

                $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

                throw new AuthException(
                    __u('Account expired'),
                    SPException::INFO,
                    __FUNCTION__,
                    self::STATUS_USER_DISABLED
                );
            }

            if ($authData->getStatusCode() === LdapAuthService::ACCOUNT_NO_GROUPS) {
                $eventMessage->addDescription(__u('User has no associated groups'));

                $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

                throw new AuthException(
                    __u('User has no associated groups'),
                    SPException::INFO,
                    __FUNCTION__,
                    self::STATUS_USER_DISABLED
                );
            }

            if ($authData->getStatusCode() === LdapCodeEnum::NO_SUCH_OBJECT->value
                || $authData->isAuthoritative() === false
            ) {
                $eventMessage->addDescription(__u('Non authoritative auth'));

                $this->eventDispatcher->notify('login.auth.ldap', new Event($this, $eventMessage));

                return false;
            }

            $eventMessage->addDescription(__u('Internal error'));

            $this->eventDispatcher->notify(
                'login.auth.ldap',
                new Event($this, $eventMessage)
            );

            throw new AuthException(
                __u('Internal error'),
                SPException::INFO,
                __FUNCTION__,
                Service::STATUS_INTERNAL_ERROR
            );
        }

        $this->eventDispatcher->notify(
            'login.auth.ldap',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDetail(__u('Type'), __FUNCTION__)
                            ->addDetail(__u('LDAP Server'), $authData->getServer())
            )
        );

        try {
            $userLoginRequest = new UserLoginRequest();
            $userLoginRequest->setLogin($this->userLoginData->getLoginUser());

            if ($this->configData->isLdapDatabaseEnabled()) {
                $userLoginRequest->setPassword($this->userLoginData->getLoginPass());
            } else {
                // Use a random password when database fallback is disabled
                $userLoginRequest->setPassword(PasswordUtil::randomPassword());
            }

            $userLoginRequest->setEmail($authData->getEmail());
            $userLoginRequest->setName($authData->getName());
            $userLoginRequest->setIsLdap(true);

            // Verificamos si el usuario existe en la BBDD
            if ($this->userService->checkExistsByLogin($this->userLoginData->getLoginUser())) {
                // Actualizamos el usuario de LDAP en MySQL
                $this->userService->updateOnLogin($userLoginRequest);
            } else {
                // Creamos el usuario de LDAP en MySQL
                $this->userService->createOnLogin($userLoginRequest);
            }
        } catch (Exception $e) {
            throw new AuthException(
                __u('Internal error'),
                SPException::ERROR,
                __FUNCTION__,
                Service::STATUS_INTERNAL_ERROR,
                $e
            );
        }

        return true;
    }

    /**
     * Autentificación en BD
     *
     * @param DatabaseAuthData $authData
     *
     * @return bool
     * @throws SPException
     * @throws AuthException
     */
    private function authDatabase(DatabaseAuthData $authData): bool
    {
        $eventMessage = EventMessage::factory()
                                    ->addDetail(__u('Type'), __FUNCTION__)
                                    ->addDetail(__u('User'), $this->userLoginData->getLoginUser());

        // Autentificamos con la BBDD
        if ($authData->getAuthenticated() === false) {
            if ($authData->isAuthoritative() === false) {
                $eventMessage->addDescription(__u('Non authoritative auth'));

                $this->eventDispatcher->notify('login.auth.database', new Event($this, $eventMessage));

                return false;
            }

            $this->addTracking();

            $eventMessage->addDescription(__u('Wrong login'));

            $this->eventDispatcher->notify('login.auth.database', new Event($this, $eventMessage));

            throw new AuthException(
                __u('Wrong login'),
                SPException::INFO,
                __FUNCTION__,
                self::STATUS_INVALID_LOGIN
            );
        }

        $this->eventDispatcher->notify(
            'login.auth.database',
            new Event($this, $eventMessage)
        );

        return true;
    }

    /**
     * Comprobar si el cliente ha enviado las variables de autentificación
     *
     * @param BrowserAuthData $authData
     *
     * @return bool
     * @throws AuthException
     */
    private function authBrowser(BrowserAuthData $authData): bool
    {
        $authType = $this->request->getServer('AUTH_TYPE') ?: __('N/A');

        $eventMessage = EventMessage::factory()
                                    ->addDetail(__u('Type'), __FUNCTION__)
                                    ->addDetail(__u('User'), $this->userLoginData->getLoginUser())
                                    ->addDetail(
                                        __u('Authentication'),
                                        sprintf('%s (%s)', $authType, $authData->getName())
                                    );

        // Comprobar si concide el login con la autentificación del servidor web
        if ($authData->getAuthenticated() === false) {
            if ($authData->isAuthoritative() === false) {
                $eventMessage->addDescription(__u('Non authoritative auth'));

                $this->eventDispatcher->notify('login.auth.browser', new Event($this, $eventMessage));

                return false;
            }

            $this->addTracking();

            $eventMessage->addDescription(__u('Wrong login'));

            $this->eventDispatcher->notify('login.auth.browser', new Event($this, $eventMessage));

            throw new AuthException(
                __u('Wrong login'),
                SPException::INFO,
                __FUNCTION__,
                self::STATUS_INVALID_LOGIN
            );
        }

        if ($this->configData->isAuthBasicAutoLoginEnabled()) {
            try {
                $userLoginRequest = new UserLoginRequest();
                $userLoginRequest->setLogin($this->userLoginData->getLoginUser());
                $userLoginRequest->setPassword($this->userLoginData->getLoginPass());

                // Verificamos si el usuario existe en la BBDD
                if (!$this->userService->checkExistsByLogin($this->userLoginData->getLoginUser())) {
                    // Creamos el usuario de SSO en la BBDD
                    $this->userService->createOnLogin($userLoginRequest);
                }

                $this->eventDispatcher->notify('login.auth.browser', new Event($this, $eventMessage));
            } catch (Exception $e) {
                throw new AuthException(
                    __u('Internal error'),
                    SPException::ERROR,
                    __FUNCTION__,
                    Service::STATUS_INTERNAL_ERROR,
                    $e
                );
            }
        }

        return true;
    }
}
