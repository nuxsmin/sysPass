<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Services\Auth;

defined('APP_ROOT') || die();

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\CryptoException;
use SP\Bootstrap;
use SP\Config\Config;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Core\Messages\LogMessage;
use SP\Core\Session\Session;
use SP\Core\Traits\InjectableTrait;
use SP\Core\UI\Theme;
use SP\Crypt\TemporaryMasterPass;
use SP\DataModel\TrackData;
use SP\DataModel\UserLoginData;
use SP\DataModel\UserPreferencesData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Log\Log;
use SP\Mgmt\Tracks\Track;
use SP\Providers\Auth\Auth;
use SP\Providers\Auth\AuthResult;
use SP\Providers\Auth\AuthUtil;
use SP\Providers\Auth\Browser\BrowserAuthData;
use SP\Providers\Auth\Database\DatabaseAuthData;
use SP\Providers\Auth\Ldap\LdapAuthData;
use SP\Services\Service;
use SP\Services\User\UserLoginRequest;
use SP\Services\User\UserPassService;
use SP\Services\User\UserService;
use SP\Services\UserPassRecover\UserPassRecoverService;
use SP\Services\UserProfile\UserProfileService;
use SP\Util\HttpUtil;
use SP\Util\Json;
use SP\Util\Util;

/**
 * Class LoginService
 *
 * @package SP\Services
 */
class LoginService
{
    use InjectableTrait;

    /**
     * Estados
     */
    const STATUS_INVALID_LOGIN = 1;
    const STATUS_INVALID_MASTER_PASS = 2;
    const STATUS_USER_DISABLED = 3;
    const STATUS_NEED_OLD_PASS = 5;
    const STATUS_MAX_ATTEMPTS_EXCEEDED = 6;

    /**
     * Tiempo para contador de intentos
     */
    const TIME_TRACKING = 600;
    const TIME_TRACKING_MAX_ATTEMPTS = 5;

    /**
     * @var JsonResponse
     */
    protected $jsonResponse;
    /**
     * @var UserLoginData
     */
    protected $userLoginData;
    /**
     * @var LogMessage
     */
    protected $LogMessage;
    /**
     * @var $ConfigData
     */
    protected $configData;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Theme
     */
    protected $theme;
    /**
     * @var UserService
     */
    protected $userService;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * LoginController constructor.
     *
     * @param Config          $config
     * @param Session         $session
     * @param Theme           $theme
     * @param EventDispatcher $eventDispatcher
     * @throws \SP\Core\Dic\ContainerException
     * @throws \ReflectionException
     */
    public function __construct(Config $config, Session $session, Theme $theme, EventDispatcher $eventDispatcher)
    {
        $this->injectDependencies();

        $this->config = $config;
        $this->configData = $config->getConfigData();
        $this->theme = $theme;
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;

        $this->userService = new UserService();
        $this->jsonResponse = new JsonResponse();
        $this->LogMessage = new LogMessage();
        $this->userLoginData = new UserLoginData();
        $this->LogMessage->setAction(__u('Inicio sesión'));
    }

    /**
     * Ejecutar las acciones de login
     *
     * @return JsonResponse
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function doLogin()
    {
        $this->userLoginData->setLoginUser(Request::analyze('user'));
        $this->userLoginData->setLoginPass(Request::analyzeEncrypted('pass'));

        $Log = new Log($this->LogMessage);

        try {
            $this->checkTracking();

            $auth = new Auth($this->userLoginData, $this->configData);

            if (($result = $auth->doAuth()) !== false) {
                // Ejecutar la acción asociada al tipo de autentificación

                /** @var AuthResult $authResult */
                foreach ($result as $authResult) {
                    if ($authResult->isAuthGranted() === true
                        && $this->{$authResult->getAuth()}($authResult->getData()) === true) {
                        break;
                    }
                }
            } else {
                $this->addTracking();

                throw new AuthException(__u('Login incorrecto'), SPException::INFO, null, self::STATUS_INVALID_LOGIN);
            }

            $this->checkUser();
            $this->loadMasterPass();
            $this->setUserSession();
            $this->loadUserPreferences();
            $this->cleanUserData();
        } catch (SPException $e) {
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();

            $this->jsonResponse->setDescription($e->getMessage());
            $this->jsonResponse->setStatus($e->getCode());

            Json::returnJson($this->jsonResponse);
        }

        $forward = Request::getRequestHeaders('X-Forwarded-For');

        if ($forward) {
            $this->LogMessage->addDetails('X-Forwarded-For', $this->configData->isDemoEnabled() ? '***' : $forward);
        }

        $Log->writeLog();

//        $data = ['url' => 'index.php' . Request::importUrlParamsToGet()];
        $data = ['url' => 'index.php?r=index'];
        $this->jsonResponse->setStatus(JsonResponse::JSON_SUCCESS);
        $this->jsonResponse->setData($data);

        return $this->jsonResponse;
    }

    /**
     * Comprobar los intentos de login
     *
     * @throws \SP\Services\Auth\AuthException
     */
    private function checkTracking()
    {
        try {
            $TrackData = new TrackData();
            $TrackData->setSource('Login');
            $TrackData->setTrackIp(HttpUtil::getClientAddress());

            $attempts = count(Track::getItem($TrackData)->getTracksForClientFromTime(time() - self::TIME_TRACKING));
        } catch (SPException $e) {
            $this->LogMessage->addDescription($e->getMessage());
            $this->LogMessage->addDescription($e->getHint());

            throw new AuthException(__u('Error interno'), SPException::ERROR, null, Service::STATUS_INTERNAL_ERROR);
        }

        if ($attempts >= self::TIME_TRACKING_MAX_ATTEMPTS) {
            $this->addTracking();

            sleep(0.3 * $attempts);

            $this->LogMessage->addDescription(sprintf(__('Intentos excedidos (%d/%d)'), $attempts, self::TIME_TRACKING_MAX_ATTEMPTS));

            throw new AuthException(__u('Intentos excedidos'), SPException::INFO, null, self::STATUS_MAX_ATTEMPTS_EXCEEDED);
        }
    }

    /**
     * Añadir un seguimiento
     *
     * @throws \SP\Services\Auth\AuthException
     */
    private function addTracking()
    {
        try {
            $TrackData = new TrackData();
            $TrackData->setSource('Login');
            $TrackData->setTrackIp(HttpUtil::getClientAddress());

            Track::getItem($TrackData)->add();
        } catch (SPException $e) {
            throw new AuthException(__u('Error interno'), SPException::ERROR, null, Service::STATUS_INTERNAL_ERROR);
        }
    }

    /**
     * Comprobar estado del usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Services\Auth\AuthException
     */
    protected function checkUser()
    {
        $userLoginResponse = $this->userLoginData->getUserLoginResponse();

        // Comprobar si el usuario está deshabilitado
        if ($userLoginResponse->getIsDisabled()) {
            $this->LogMessage->addDescription(__u('Usuario deshabilitado'));
            $this->LogMessage->addDetails(__u('Usuario'), $userLoginResponse->getLogin());

            $this->addTracking();

            throw new AuthException(__u('Usuario deshabilitado'), SPException::INFO, null, self::STATUS_USER_DISABLED);
        }

        // Comprobar si se ha forzado un cambio de clave
        if ($userLoginResponse->getIsChangePass()) {
            $hash = Util::generateRandomBytes(16);

            (new UserPassRecoverService())->add($userLoginResponse->getId(), $hash);

            $this->jsonResponse->setData(['url' => Bootstrap::$WEBURI . '/index.php?u=userPassReset/change/' . $hash]);
            $this->jsonResponse->setStatus(0);
            Json::returnJson($this->jsonResponse);
        }
    }

    /**
     * Cargar la clave maestra o solicitarla
     *
     * @throws AuthException
     * @throws SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \SP\Core\Dic\ContainerException
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    protected function loadMasterPass()
    {
        $temporaryMasterPass = new TemporaryMasterPass();
        $userPassService = new UserPassService();

        $masterPass = Request::analyzeEncrypted('mpass');
        $oldPass = Request::analyzeEncrypted('oldpass');

        try {
            if ($masterPass) {
                if ($temporaryMasterPass->check($masterPass)) {
                    $this->LogMessage->addDescription(__u('Usando clave temporal'));

                    $masterPass = $temporaryMasterPass->getUsingKey($masterPass);
                }

                if ($userPassService->updateMasterPassOnLogin($masterPass, $this->userLoginData)->getStatus() !== UserPassService::MPASS_OK) {
                    $this->LogMessage->addDescription(__u('Clave maestra incorrecta'));

                    $this->addTracking();

                    throw new AuthException(__u('Clave maestra incorrecta'), SPException::INFO, null, self::STATUS_INVALID_MASTER_PASS);
                }

                $this->LogMessage->addDescription(__u('Clave maestra actualizada'));
            } else if ($oldPass) {
                if (!$userPassService->updateMasterPassFromOldPass($oldPass, $this->userLoginData)->getStatus() !== UserPassService::MPASS_OK) {
                    $this->LogMessage->addDescription(__u('Clave maestra incorrecta'));

                    $this->addTracking();

                    throw new AuthException(__u('Clave maestra incorrecta'), SPException::INFO, null, self::STATUS_INVALID_MASTER_PASS);
                }

                $this->LogMessage->addDescription(__u('Clave maestra actualizada'));
            } else {
                switch ($userPassService->loadUserMPass($this->userLoginData)->getStatus()) {
                    case UserPassService::MPASS_CHECKOLD:
                        throw new AuthException(__u('Es necesaria su clave anterior'), SPException::INFO, null, self::STATUS_NEED_OLD_PASS);
                        break;
                    case UserPassService::MPASS_NOTSET:
                    case UserPassService::MPASS_CHANGED:
                    case UserPassService::MPASS_WRONG:
                        $this->addTracking();

                        throw new AuthException(__u('La clave maestra no ha sido guardada o es incorrecta'), SPException::INFO, null, self::STATUS_INVALID_MASTER_PASS);
                        break;
                }
            }
        } catch (BadFormatException $e) {
            $this->LogMessage->addDescription(__u('Clave maestra incorrecta'));

            throw new AuthException(__u('Clave maestra incorrecta'), SPException::INFO, null, self::STATUS_INVALID_MASTER_PASS);
        } catch (CryptoException $e) {
            $this->LogMessage->addDescription(__u('Error interno'));

            throw new AuthException($this->LogMessage->getDescription(), SPException::INFO, $e->getMessage(), Service::STATUS_INTERNAL_ERROR);
        }
    }

    /**
     * Cargar la sesión del usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function setUserSession()
    {
        $userLoginResponse = $this->userLoginData->getUserLoginResponse();

        // Actualizar el último login del usuario
        $this->userService->updateLastLoginById($userLoginResponse->getId());

        // Cargar las variables de ussuario en la sesión
        $this->session->setUserData($userLoginResponse);
        $this->session->setUserProfile((new UserProfileService())->getById($userLoginResponse->getUserProfileId()));

        if ($this->configData->isDemoEnabled()) {
            $userLoginResponse->setPreferences(new UserPreferencesData());
        }

        $this->LogMessage->addDetails(__u('Usuario'), $userLoginResponse->getLogin());
    }

    /**
     * Cargar las preferencias del usuario y comprobar si usa 2FA
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    protected function loadUserPreferences()
    {
        Language::setLanguage(true);

        $this->theme->initTheme(true);

//        SessionFactory::setSessionType(SessionFactory::SESSION_INTERACTIVE);

        $this->session->setAuthCompleted(true);

        $this->eventDispatcher->notifyEvent('login.preferences', $this);
    }

    /**
     * Limpiar datos de usuario
     */
    private function cleanUserData()
    {
        $this->userLoginData->setUserLoginResponse();
    }

    /**
     * Autentificación LDAP
     *
     * @param LdapAuthData $AuthData
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     * @throws AuthException
     */
    protected function authLdap(LdapAuthData $AuthData)
    {
        if ($AuthData->getStatusCode() > 0) {
            $this->LogMessage->addDetails(__u('Tipo'), __FUNCTION__);
            $this->LogMessage->addDetails(__u('Usuario'), $this->userLoginData->getLoginUser());

            if ($AuthData->getStatusCode() === 49) {
                $this->LogMessage->addDescription(__u('Login incorrecto'));

                $this->addTracking();

                throw new AuthException($this->LogMessage->getDescription(), SPException::INFO, null, self::STATUS_INVALID_LOGIN);
            }

            if ($AuthData->getStatusCode() === 701) {
                $this->LogMessage->addDescription(__u('Cuenta expirada'));

                throw new AuthException($this->LogMessage->getDescription(), SPException::INFO, null, self::STATUS_USER_DISABLED);
            }

            if ($AuthData->getStatusCode() === 702) {
                $this->LogMessage->addDescription(__u('El usuario no tiene grupos asociados'));

                throw new AuthException($this->LogMessage->getDescription(), SPException::INFO, null, self::STATUS_USER_DISABLED);
            }

            if ($AuthData->isAuthGranted() === false) {
                return false;
            }

            $this->LogMessage->addDescription(__u('Error interno'));

            throw new AuthException($this->LogMessage->getDescription(), SPException::INFO, null, Service::STATUS_INTERNAL_ERROR);
        }

        $this->LogMessage->addDetails(__u('Tipo'), __FUNCTION__);
        $this->LogMessage->addDetails(__u('Servidor LDAP'), $AuthData->getServer());

        try {
            $userLoginRequest = new UserLoginRequest();
            $userLoginRequest->setLogin($this->userLoginData->getLoginUser());
            $userLoginRequest->setPassword($this->userLoginData->getLoginPass());
            $userLoginRequest->setEmail($AuthData->getEmail());
            $userLoginRequest->setName($AuthData->getName());
            $userLoginRequest->setIsLdap(1);


            // Verificamos si el usuario existe en la BBDD
            if ($this->userService->checkExistsByLogin($this->userLoginData->getLoginUser())) {
                // Actualizamos el usuario de LDAP en MySQL
                $this->userService->updateOnLogin($userLoginRequest);
            } else {
                // Creamos el usuario de LDAP en MySQL
                $this->userService->createOnLogin($userLoginRequest);
            }
        } catch (SPException $e) {
            $this->LogMessage->addDescription($e->getMessage());

            throw new AuthException(__u('Error interno'), SPException::ERROR, null, Service::STATUS_INTERNAL_ERROR);
        }

        return true;
    }

    /**
     * Autentificación en BD
     *
     * @param DatabaseAuthData $AuthData
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     * @throws AuthException
     */
    protected function authDatabase(DatabaseAuthData $AuthData)
    {
        // Autentificamos con la BBDD
        if ($AuthData->getAuthenticated() === 0) {
            if ($AuthData->isAuthGranted() === false) {
                return false;
            }

            $this->LogMessage->addDescription(__u('Login incorrecto'));
            $this->LogMessage->addDetails(__u('Usuario'), $this->userLoginData->getLoginUser());

            $this->addTracking();

            throw new AuthException($this->LogMessage->getDescription(), SPException::INFO, null, self::STATUS_INVALID_LOGIN);
        }

        if ($AuthData->getAuthenticated() === 1) {
            $this->LogMessage->addDetails(__u('Tipo'), __FUNCTION__);
        }

        return true;
    }

    /**
     * Comprobar si el cliente ha enviado las variables de autentificación
     *
     * @param BrowserAuthData $authData
     * @return mixed
     * @throws AuthException
     */
    protected function authBrowser(BrowserAuthData $authData)
    {
        // Comprobar si concide el login con la autentificación del servidor web
        if ($authData->getAuthenticated() === 0) {
            if ($authData->isAuthGranted() === false) {
                return false;
            }

            $this->LogMessage->addDescription(__u('Login incorrecto'));
            $this->LogMessage->addDetails(__u('Tipo'), __FUNCTION__);
            $this->LogMessage->addDetails(__u('Usuario'), $this->userLoginData->getLoginUser());
            $this->LogMessage->addDetails(__u('Autentificación'), sprintf('%s (%s)', AuthUtil::getServerAuthType(), $authData->getName()));

            $this->addTracking();

            throw new AuthException($this->LogMessage->getDescription(), SPException::INFO, null, self::STATUS_INVALID_LOGIN);
        }

        $this->LogMessage->addDetails(__u('Tipo'), __FUNCTION__);

        if ($authData->getAuthenticated() === 1 && $this->configData->isAuthBasicAutoLoginEnabled()) {
            try {
                $userLoginRequest = new UserLoginRequest();
                $userLoginRequest->setLogin($this->userLoginData->getLoginUser());
                $userLoginRequest->setPassword($this->userLoginData->getLoginPass());

                // Verificamos si el usuario existe en la BBDD
                if (!$this->userService->checkExistsByLogin($this->userLoginData->getLoginUser())) {
                    // Creamos el usuario de SSO en la BBDD
                    $this->userService->createOnLogin($userLoginRequest);
                }
            } catch (SPException $e) {
                throw new AuthException(__u('Error interno'), SPException::ERROR, null, Service::STATUS_INTERNAL_ERROR);
            }

            $this->LogMessage->addDetails(__u('Usuario'), $this->userLoginData->getLoginUser());
            $this->LogMessage->addDetails(__u('Autentificación'), sprintf('%s (%s)', AuthUtil::getServerAuthType(), $authData->getName()));

            return true;
        }

        return null;
    }
}