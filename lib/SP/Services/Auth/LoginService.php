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

namespace SP\Services\Auth;

defined('APP_ROOT') || die();

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Config\ConfigData;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Core\UI\ThemeInterface;
use SP\DataModel\UserLoginData;
use SP\DataModel\UserPreferencesData;
use SP\Http\Request;
use SP\Http\Uri;
use SP\Providers\Auth\AuthProvider;
use SP\Providers\Auth\Browser\BrowserAuthData;
use SP\Providers\Auth\Database\DatabaseAuthData;
use SP\Providers\Auth\Ldap\LdapAuth;
use SP\Providers\Auth\Ldap\LdapAuthData;
use SP\Providers\Auth\Ldap\LdapCode;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Track\TrackRequest;
use SP\Services\Crypt\TemporaryMasterPassService;
use SP\Services\Service;
use SP\Services\Track\TrackService;
use SP\Services\User\UserLoginRequest;
use SP\Services\User\UserPassService;
use SP\Services\User\UserService;
use SP\Services\UserPassRecover\UserPassRecoverService;
use SP\Services\UserProfile\UserProfileService;
use SP\Util\PasswordUtil;

/**
 * Class LoginService
 *
 * @package SP\Services
 */
final class LoginService extends Service
{
    /**
     * Estados
     */
    const STATUS_INVALID_LOGIN = 1;
    const STATUS_INVALID_MASTER_PASS = 2;
    const STATUS_USER_DISABLED = 3;
    const STATUS_NEED_OLD_PASS = 5;
    const STATUS_MAX_ATTEMPTS_EXCEEDED = 6;
    const STATUS_PASS_RESET = 7;
    const STATUS_PASS = 0;
    const STATUS_NONE = 100;

    /**
     * @var UserLoginData
     */
    private $userLoginData;
    /**
     * @var ConfigData
     */
    private $configData;
    /**
     * @var ThemeInterface
     */
    private $theme;
    /**
     * @var UserService
     */
    private $userService;
    /**
     * @var Language
     */
    private $language;
    /**
     * @var TrackService
     */
    private $trackService;
    /**
     * @var TrackRequest
     */
    private $trackRequest;
    /**
     * @var string
     */
    private $from;
    /**
     * @var Request
     */
    private $request;

    /**
     * Ejecutar las acciones de login
     *
     * @return LoginResponse
     * @throws AuthException
     * @throws SPException
     * @throws EnvironmentIsBrokenException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     * @uses LoginService::authBrowser()
     * @uses LoginService::authDatabase()
     * @uses LoginService::authLdap()
     *
     */
    public function doLogin()
    {
        $this->userLoginData->setLoginUser($this->request->analyzeString('user'));
        $this->userLoginData->setLoginPass($this->request->analyzeEncrypted('pass'));

        if ($this->trackService->checkTracking($this->trackRequest)) {
            $this->addTracking();

            throw new AuthException(
                __u('Attempts exceeded'),
                AuthException::INFO,
                null,
                self::STATUS_MAX_ATTEMPTS_EXCEEDED
            );
        }

        $result = $this->dic->get(AuthProvider::class)->doAuth($this->userLoginData);

        if ($result !== false) {
            // Ejecutar la acción asociada al tipo de autentificación
            foreach ($result as $authResult) {
                if ($authResult->isAuthGranted() === true
                    && $this->{$authResult->getAuth()}($authResult->getData()) === true
                ) {
                    break;
                }
            }
        } else {
            $this->addTracking();

            throw new AuthException(
                __u('Wrong login'),
                AuthException::INFO,
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

        if ($this->from) {
            $uri->addParam('r', $this->from);
            $uri->addParam('sk', $this->context->getSecurityKey());
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
    private function addTracking()
    {
        try {
            $this->trackService->add($this->trackRequest);
        } catch (Exception $e) {
            throw new AuthException(
                __u('Internal error'),
                AuthException::ERROR,
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConstraintException
     * @throws QueryException
     * @throws AuthException
     */
    private function checkUser()
    {
        $userLoginResponse = $this->userLoginData->getUserLoginResponse();

        if ($userLoginResponse !== null) {
            // Comprobar si el usuario está deshabilitado
            if ($userLoginResponse->getIsDisabled()) {
                $this->eventDispatcher->notifyEvent(
                    'login.checkUser.disabled',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('User disabled'))
                        ->addDetail(__u('User'), $userLoginResponse->getLogin()))
                );

                $this->addTracking();

                throw new AuthException(
                    __u('User disabled'),
                    AuthException::INFO,
                    null,
                    self::STATUS_USER_DISABLED
                );
            }

            // Check whether a user's password change has been requested
            if ($userLoginResponse->getIsChangePass()) {
                $this->eventDispatcher->notifyEvent(
                    'login.checkUser.changePass',
                    new Event($this, EventMessage::factory()
                        ->addDetail(__u('User'), $userLoginResponse->getLogin()))
                );

                $hash = PasswordUtil::generateRandomBytes(16);

                $this->dic->get(UserPassRecoverService::class)
                    ->add($userLoginResponse->getId(), $hash);

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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function loadMasterPass()
    {
        $temporaryMasterPass = $this->dic->get(TemporaryMasterPassService::class);
        $userPassService = $this->dic->get(UserPassService::class);

        $masterPass = $this->request->analyzeEncrypted('mpass');
        $oldPass = $this->request->analyzeEncrypted('oldpass');

        try {
            if ($masterPass) {
                if ($temporaryMasterPass->checkTempMasterPass($masterPass)) {
                    $this->eventDispatcher->notifyEvent(
                        'login.masterPass.temporary',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Using temporary password')))
                    );

                    $masterPass = $temporaryMasterPass->getUsingKey($masterPass);
                }

                if ($userPassService->updateMasterPassOnLogin(
                        $masterPass,
                        $this->userLoginData)->getStatus() !== UserPassService::MPASS_OK
                ) {
                    $this->eventDispatcher->notifyEvent(
                        'login.masterPass',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Wrong master password')))
                    );

                    $this->addTracking();

                    throw new AuthException(
                        __u('Wrong master password'),
                        AuthException::INFO,
                        null,
                        self::STATUS_INVALID_MASTER_PASS
                    );
                }

                $this->eventDispatcher->notifyEvent(
                    'login.masterPass',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Master password updated')))
                );
            } else if ($oldPass) {
                if ($userPassService->updateMasterPassFromOldPass(
                        $oldPass,
                        $this->userLoginData)->getStatus() !== UserPassService::MPASS_OK
                ) {
                    $this->eventDispatcher->notifyEvent(
                        'login.masterPass',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('Wrong master password')))
                    );

                    $this->addTracking();

                    throw new AuthException(
                        __u('Wrong master password'),
                        AuthException::INFO,
                        null,
                        self::STATUS_INVALID_MASTER_PASS
                    );
                }

                $this->eventDispatcher->notifyEvent(
                    'login.masterPass',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Master password updated')))
                );
            } else {
                switch ($userPassService->loadUserMPass($this->userLoginData)->getStatus()) {
                    case UserPassService::MPASS_CHECKOLD:
                        throw new AuthException(
                            __u('Your previous password is needed'),
                            AuthException::INFO,
                            null,
                            self::STATUS_NEED_OLD_PASS
                        );
                        break;
                    case UserPassService::MPASS_NOTSET:
                    case UserPassService::MPASS_CHANGED:
                    case UserPassService::MPASS_WRONG:
                        $this->addTracking();

                        throw new AuthException(
                            __u('The Master Password either is not saved or is wrong'),
                            AuthException::INFO,
                            null,
                            self::STATUS_INVALID_MASTER_PASS
                        );
                        break;
                }
            }
        } catch (CryptoException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new AuthException(
                __u('Internal error'),
                AuthException::ERROR,
                $e->getMessage(),
                Service::STATUS_INTERNAL_ERROR,
                $e
            );
        }
    }

    /**
     * Cargar la sesión del usuario
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    private function setUserSession()
    {
        $userLoginResponse = $this->userLoginData->getUserLoginResponse();

        // Actualizar el último login del usuario
        $this->userService->updateLastLoginById($userLoginResponse->getId());

        if ($this->context->getTrasientKey('mpass_updated')) {
            $userLoginResponse->setLastUpdateMPass(time());
        }

        // Cargar las variables de ussuario en la sesión
        $this->context->setUserData($userLoginResponse);
        $this->context->setUserProfile($this->dic->get(UserProfileService::class)->getById($userLoginResponse->getUserProfileId())->getProfile());
        $this->context->setLocale($userLoginResponse->getPreferences()->getLang());

        if ($this->configData->isDemoEnabled()) {
            $userLoginResponse->setPreferences(new UserPreferencesData());
        }

        $this->eventDispatcher->notifyEvent(
            'login.session.load',
            new Event($this, EventMessage::factory()
                ->addDetail(__u('User'), $userLoginResponse->getLogin()))
        );
    }

    /**
     * Cargar las preferencias del usuario y comprobar si usa 2FA
     */
    private function loadUserPreferences()
    {
        $this->language->setLanguage(true);

        $this->theme->initTheme(true);

        $this->context->setAuthCompleted(true);

        $this->eventDispatcher->notifyEvent(
            'login.preferences.load',
            new Event($this)
        );
    }

    /**
     * Limpiar datos de usuario
     */
    private function cleanUserData()
    {
        $this->userLoginData->setUserLoginResponse();
    }

    /**
     * @param string $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    protected function initialize()
    {
        $this->configData = $this->config->getConfigData();
        $this->theme = $this->dic->get(ThemeInterface::class);
        $this->userService = $this->dic->get(UserService::class);
        $this->language = $this->dic->get(Language::class);
        $this->trackService = $this->dic->get(TrackService::class);
        $this->request = $this->dic->get(Request::class);

        $this->userLoginData = new UserLoginData();
        $this->trackRequest = $this->trackService->getTrackRequest(__CLASS__);
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
    private function authLdap(LdapAuthData $authData)
    {
        if ($authData->getStatusCode() > LdapCode::SUCCESS) {
            $eventMessage = EventMessage::factory()
                ->addDetail(__u('Type'), __FUNCTION__)
                ->addDetail(__u('LDAP Server'), $authData->getServer())
                ->addDetail(__u('User'), $this->userLoginData->getLoginUser());

            if ($authData->getStatusCode() === LdapCode::INVALID_CREDENTIALS) {
                $eventMessage->addDescription(__u('Wrong login'));

                $this->addTracking();

                $this->eventDispatcher->notifyEvent(
                    'login.auth.ldap',
                    new Event($this, $eventMessage)
                );

                throw new AuthException(
                    __u('Wrong login'),
                    AuthException::INFO,
                    __FUNCTION__,
                    self::STATUS_INVALID_LOGIN
                );
            }

            if ($authData->getStatusCode() === LdapAuth::ACCOUNT_EXPIRED) {
                $eventMessage->addDescription(__u('Account expired'));

                $this->eventDispatcher->notifyEvent(
                    'login.auth.ldap',
                    new Event($this, $eventMessage)
                );

                throw new AuthException(
                    __u('Account expired'),
                    AuthException::INFO,
                    __FUNCTION__,
                    self::STATUS_USER_DISABLED
                );
            }

            if ($authData->getStatusCode() === LdapAuth::ACCOUNT_NO_GROUPS) {
                $eventMessage->addDescription(__u('User has no associated groups'));

                $this->eventDispatcher->notifyEvent(
                    'login.auth.ldap',
                    new Event($this, $eventMessage)
                );

                throw new AuthException(
                    __u('User has no associated groups'),
                    AuthException::INFO,
                    __FUNCTION__,
                    self::STATUS_USER_DISABLED
                );
            }

            if ($authData->getStatusCode() === LdapCode::NO_SUCH_OBJECT
                || $authData->isAuthGranted() === false
            ) {
                return false;
            }

            $eventMessage->addDescription(__u('Internal error'));

            $this->eventDispatcher->notifyEvent(
                'login.auth.ldap',
                new Event($this, $eventMessage)
            );

            throw new AuthException(
                __u('Internal error'),
                AuthException::INFO,
                __FUNCTION__,
                Service::STATUS_INTERNAL_ERROR
            );
        }

        $this->eventDispatcher->notifyEvent(
            'login.auth.ldap',
            new Event($this, EventMessage::factory()
                ->addDetail(__u('Type'), __FUNCTION__)
                ->addDetail(__u('LDAP Server'), $authData->getServer())
            )
        );

        try {
            $userLoginRequest = new UserLoginRequest();
            $userLoginRequest->setLogin($this->userLoginData->getLoginUser());
            $userLoginRequest->setPassword($this->userLoginData->getLoginPass());
            $userLoginRequest->setEmail($authData->getEmail());
            $userLoginRequest->setName($authData->getName());
            $userLoginRequest->setIsLdap(1);

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
                AuthException::ERROR,
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
    private function authDatabase(DatabaseAuthData $authData)
    {
        $eventMessage = EventMessage::factory()
            ->addDetail(__u('Type'), __FUNCTION__)
            ->addDetail(__u('User'), $this->userLoginData->getLoginUser());

        // Autentificamos con la BBDD
        if ($authData->getAuthenticated() === false) {
            if ($authData->isAuthGranted() === false) {
                return false;
            }

            $this->addTracking();

            $eventMessage->addDescription(__u('Wrong login'));

            $this->eventDispatcher->notifyEvent(
                'login.auth.database',
                new Event($this, $eventMessage)
            );

            throw new AuthException(
                __u('Wrong login'),
                AuthException::INFO,
                __FUNCTION__,
                self::STATUS_INVALID_LOGIN
            );
        }

        if ($authData->getAuthenticated() === true) {
            $this->eventDispatcher->notifyEvent(
                'login.auth.database',
                new Event($this, $eventMessage)
            );
        }

        return true;
    }

    /**
     * Comprobar si el cliente ha enviado las variables de autentificación
     *
     * @param BrowserAuthData $authData
     *
     * @return mixed
     * @throws AuthException
     */
    private function authBrowser(BrowserAuthData $authData)
    {
        $authType = $this->request->getServer('AUTH_TYPE') ?: __('N/A');

        $eventMessage = EventMessage::factory()
            ->addDetail(__u('Type'), __FUNCTION__)
            ->addDetail(__u('User'), $this->userLoginData->getLoginUser())
            ->addDetail(__u('Authentication'), sprintf('%s (%s)', $authType, $authData->getName()));

        // Comprobar si concide el login con la autentificación del servidor web
        if ($authData->getAuthenticated() === false) {
            if ($authData->isAuthGranted() === false) {
                return false;
            }

            $this->addTracking();

            $eventMessage->addDescription(__u('Wrong login'));

            $this->eventDispatcher->notifyEvent(
                'login.auth.browser',
                new Event($this, $eventMessage)
            );

            throw new AuthException(
                __u('Wrong login'),
                AuthException::INFO,
                __FUNCTION__,
                self::STATUS_INVALID_LOGIN
            );
        }

        if ($authData->getAuthenticated() === true
            && $this->configData->isAuthBasicAutoLoginEnabled()
        ) {
            try {
                $userLoginRequest = new UserLoginRequest();
                $userLoginRequest->setLogin($this->userLoginData->getLoginUser());
                $userLoginRequest->setPassword($this->userLoginData->getLoginPass());

                // Verificamos si el usuario existe en la BBDD
                if (!$this->userService->checkExistsByLogin($this->userLoginData->getLoginUser())) {
                    // Creamos el usuario de SSO en la BBDD
                    $this->userService->createOnLogin($userLoginRequest);
                }

                $this->eventDispatcher->notifyEvent(
                    'login.auth.browser',
                    new Event($this, $eventMessage)
                );

                return true;
            } catch (Exception $e) {
                throw new AuthException(
                    __u('Internal error'),
                    AuthException::ERROR,
                    __FUNCTION__,
                    Service::STATUS_INTERNAL_ERROR,
                    $e
                );
            }
        }

        return null;
    }
}