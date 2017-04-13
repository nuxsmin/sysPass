<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Controller;

defined('APP_ROOT') || die();

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\CryptoException;
use SP\Auth\Auth;
use SP\Auth\AuthResult;
use SP\Auth\AuthUtil;
use SP\Auth\Browser\BrowserAuthData;
use SP\Auth\Database\DatabaseAuthData;
use SP\Auth\Ldap\LdapAuthData;
use SP\Core\CryptMasterPass;
use SP\Core\DiFactory;
use SP\Core\Exceptions\AuthException;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Core\Language;
use SP\Core\Messages\LogMessage;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\DataModel\TrackData;
use SP\DataModel\UserLoginData;
use SP\DataModel\UserPassRecoverData;
use SP\DataModel\UserPreferencesData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Log\Log;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Tracks\Track;
use SP\Mgmt\Users\UserLdap;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserPassRecover;
use SP\Mgmt\Users\UserPreferences;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Util;

/**
 * Class LoginController
 *
 * @package SP\Controller
 */
class LoginController
{
    /**
     * Estados
     */
    const STATUS_INVALID_LOGIN = 1;
    const STATUS_INVALID_MASTER_PASS = 2;
    const STATUS_USER_DISABLED = 3;
    const STATUS_INTERNAL_ERROR = 4;
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
    protected $UserData;
    /**
     * @var LogMessage
     */
    protected $LogMessage;

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->jsonResponse = new JsonResponse();
        $this->UserData = new UserLoginData();
        $this->LogMessage = new LogMessage();
        $this->LogMessage->setAction(__('Inicio sesión', false));
    }

    /**
     * Ejecutar las acciones de login
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \InvalidArgumentException
     */
    public function doLogin()
    {
        $userLogin = Request::analyze('user');
        $userPass = Request::analyzeEncrypted('pass');

        if (!$userLogin || !$userPass) {
            $this->jsonResponse->setDescription(__('Usuario/Clave no introducidos', false));
            Json::returnJson($this->jsonResponse);
        }

        $this->UserData->setLogin($userLogin);
        $this->UserData->setLoginPass($userPass);

        $Log = new Log($this->LogMessage);

        try {
            $this->checkTracking();

            $Auth = new Auth($this->UserData);
            $result = $Auth->doAuth();

            if ($result !== false) {
                // Ejecutar la acción asociada al tipo de autentificación

                /** @var AuthResult $AuthResult */
                foreach ($result as $AuthResult) {
                    if ($this->{$AuthResult->getAuth()}($AuthResult->getData()) === true) {
                        break;
                    }
                }
            } else {
                $this->addTracking();

                throw new AuthException(SPException::SP_INFO, __('Login incorrecto', false), '', self::STATUS_INVALID_LOGIN);
            }

            $this->getUserData();
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
            $this->LogMessage->addDetails('X-Forwarded-For', Checks::demoIsEnabled() ? '***' : $forward);
        }

        $Log->writeLog();

        $data = ['url' => 'index.php' . Request::importUrlParamsToGet()];
        $this->jsonResponse->setStatus(0);
        $this->jsonResponse->setData($data);
        Json::returnJson($this->jsonResponse);
    }

    /**
     * Comprobar los intentos de login
     *
     * @throws \SP\Core\Exceptions\AuthException
     */
    private function checkTracking()
    {
        try {
            $TrackData = new TrackData();
            $TrackData->setTrackSource('Login');
            $TrackData->setTrackIp(Util::getClientAddress());

            $attempts = count(Track::getItem($TrackData)->getTracksForClientFromTime(time() - self::TIME_TRACKING));
        } catch (SPException $e) {
            $this->LogMessage->addDescription($e->getMessage());
            $this->LogMessage->addDescription($e->getHint());

            throw new AuthException(SPException::SP_ERROR, __('Error interno', false), '', self::STATUS_INTERNAL_ERROR);
        }

        if ($attempts >= self::TIME_TRACKING_MAX_ATTEMPTS) {
            $this->addTracking();

            sleep(0.3 * $attempts);

            $this->LogMessage->addDescription(sprintf(__('Intentos excedidos (%d/%d)'), $attempts, self::TIME_TRACKING_MAX_ATTEMPTS));

            throw new AuthException(SPException::SP_INFO, __('Intentos excedidos', false), '', self::STATUS_MAX_ATTEMPTS_EXCEEDED);
        }
    }

    /**
     * Añadir un seguimiento
     *
     * @throws \SP\Core\Exceptions\AuthException
     */
    private function addTracking()
    {
        try {
            $TrackData = new TrackData();
            $TrackData->setTrackSource('Login');
            $TrackData->setTrackIp(Util::getClientAddress());

            Track::getItem($TrackData)->add();
        } catch (SPException $e) {
            throw new AuthException(SPException::SP_ERROR, __('Error interno', false), '', self::STATUS_INTERNAL_ERROR);
        }
    }

    /**
     * Obtener los datos del usuario
     *
     * @throws SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\AuthException
     * @throws \InvalidArgumentException
     */
    protected function getUserData()
    {
        try {
            $this->UserData->setUserPreferences(UserPreferences::getItem()->getById($this->UserData->getUserId()));
        } catch (SPException $e) {
            $this->LogMessage->addDescription(__('Error al obtener los datos del usuario de la BBDD', false));

            throw new AuthException(SPException::SP_ERROR, __('Error interno', false), '', self::STATUS_INTERNAL_ERROR);
        }
    }

    /**
     * Comprobar estado del usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function checkUser()
    {
        // Comprobar si el usuario está deshabilitado
        if ($this->UserData->isUserIsDisabled()) {
            $this->LogMessage->addDescription(__('Usuario deshabilitado', false));
            $this->LogMessage->addDetails(__('Usuario', false), $this->UserData->getLogin());

            $this->addTracking();

            throw new AuthException(SPException::SP_INFO, __('Usuario deshabilitado', false), '', self::STATUS_USER_DISABLED);
        }

        if ($this->UserData->isUserIsChangePass()) {
            $hash = Util::generateRandomBytes(16);

            $UserPassRecoverData = new UserPassRecoverData();
            $UserPassRecoverData->setUserpassrUserId($this->UserData->getUserId());
            $UserPassRecoverData->setUserpassrHash($hash);

            UserPassRecover::getItem($UserPassRecoverData)->add();

            $data = ['url' => Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time() . '&f=1'];
            $this->jsonResponse->setData($data);
            $this->jsonResponse->setStatus(0);
            Json::returnJson($this->jsonResponse);
        }

        return false;
    }

    /**
     * Cargar la clave maestra o solicitarla
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\AuthException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function loadMasterPass()
    {
        $masterPass = Request::analyzeEncrypted('mpass');
        $oldPass = Request::analyzeEncrypted('oldpass');

        try {
            if ($masterPass) {
                if (CryptMasterPass::checkTempMasterPass($masterPass)) {
                    $this->LogMessage->addDescription(__('Usando clave temporal', false));

                    $masterPass = CryptMasterPass::getTempMasterPass($masterPass);
                }

                if (!UserPass::updateUserMPass($masterPass, $this->UserData)) {
                    $this->LogMessage->addDescription(__('Clave maestra incorrecta', false));

                    $this->addTracking();

                    throw new AuthException(SPException::SP_INFO, __('Clave maestra incorrecta', false), '', self::STATUS_INVALID_MASTER_PASS);
                }

                $this->LogMessage->addDescription(__('Clave maestra actualizada', false));
            } else if ($oldPass) {
                if (!UserPass::updateMasterPassFromOldPass($oldPass, $this->UserData)) {
                    $this->LogMessage->addDescription(__('Clave maestra incorrecta', false));

                    $this->addTracking();

                    throw new AuthException(SPException::SP_INFO, __('Clave maestra incorrecta', false), '', self::STATUS_INVALID_MASTER_PASS);
                }

                $this->LogMessage->addDescription(__('Clave maestra actualizada', false));
            } else {
                switch (UserPass::loadUserMPass($this->UserData)) {
                    case UserPass::MPASS_CHECKOLD:
                        throw new AuthException(SPException::SP_INFO, __('Es necesaria su clave anterior', false), '', self::STATUS_NEED_OLD_PASS);
                        break;
                    case UserPass::MPASS_NOTSET:
                    case UserPass::MPASS_CHANGED:
                    case UserPass::MPASS_WRONG:
                        $this->addTracking();

                        throw new AuthException(SPException::SP_INFO, __('La clave maestra no ha sido guardada o es incorrecta', false), '', self::STATUS_INVALID_MASTER_PASS);
                        break;
                }
            }
        } catch (BadFormatException $e) {
            $this->LogMessage->addDescription(__('Clave maestra incorrecta', false));

            throw new AuthException(SPException::SP_INFO, __('Clave maestra incorrecta', false), '', self::STATUS_INVALID_MASTER_PASS);
        } catch (CryptoException $e) {
            $this->LogMessage->addDescription(__('Error interno', false));

            throw new AuthException(SPException::SP_INFO, $this->LogMessage->getDescription(), $e->getMessage(), self::STATUS_INTERNAL_ERROR);
        }
    }

    /**
     * Cargar la sesión del usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \InvalidArgumentException
     * @throws \SP\Core\Exceptions\AuthException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function setUserSession()
    {
        // Obtenemos la clave maestra del usuario
        if (UserPass::$gotMPass === true) {
            // Actualizar el último login del usuario
            UserUtil::setUserLastLogin($this->UserData->getUserId());

            // Cargar las variables de sesión del usuario
            SessionUtil::loadUserSession($this->UserData);

            $this->LogMessage->addDetails(__('Usuario', false), $this->UserData->getLogin());
            $this->LogMessage->addDetails(__('Perfil', false), Profile::getItem()->getById($this->UserData->getUserProfileId())->getUserprofileName());
            $this->LogMessage->addDetails(__('Grupo', false), Group::getItem()->getById($this->UserData->getUserGroupId())->getUsergroupName());
        } else {
            $this->LogMessage->addDescription(__('Error al obtener la clave maestra del usuario', false));

            throw new AuthException(SPException::SP_ERROR, __('Error interno', false), '', self::STATUS_INTERNAL_ERROR);
        }
    }

    /**
     * Cargar las preferencias del usuario y comprobar si usa 2FA
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function loadUserPreferences()
    {
        if (Checks::demoIsEnabled()) {
            Session::setUserPreferences(new UserPreferencesData());
        } else {
            Session::setUserPreferences($this->UserData->getUserPreferences());
        }

        Language::setLanguage(true);
        DiFactory::getTheme()->initTheme(true);

        Session::setSessionType(Session::SESSION_INTERACTIVE);
        Session::setAuthCompleted(true);

        DiFactory::getEventDispatcher()->notifyEvent('login.preferences', $this);
    }

    /**
     * Limpiar datos de usuario
     */
    private function cleanUserData()
    {
        $this->UserData->setLogin(null);
        $this->UserData->setLoginPass(null);
        $this->UserData->setUserMPass(null);
        $this->UserData->setUserMKey(null);
    }

    /**
     * Comprobar si se ha forzado un cambio de clave
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function checkPasswordChange()
    {
        // Comprobar si se ha forzado un cambio de clave
        if ($this->UserData->isUserIsChangePass()) {
            $hash = Util::generateRandomBytes();

            $UserPassRecoverData = new UserPassRecoverData();
            $UserPassRecoverData->setUserpassrUserId($this->UserData->getUserId());
            $UserPassRecoverData->setUserpassrHash($hash);

            UserPassRecover::getItem($UserPassRecoverData)->add();

            $data = ['url' => Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time() . '&f=1'];
            $this->jsonResponse->setData($data);
            $this->jsonResponse->setStatus(0);
            Json::returnJson($this->jsonResponse);
        }

        return false;
    }

    /**
     * Autentificación LDAP
     *
     * @param LdapAuthData $LdapAuthData
     * @return bool
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\SPException
     * @throws AuthException
     */
    protected function authLdap(LdapAuthData $LdapAuthData)
    {
        if ($LdapAuthData->getStatusCode() > 0) {
            $this->LogMessage->addDetails(__('Tipo', false), __FUNCTION__);
            $this->LogMessage->addDetails(__('Usuario', false), $this->UserData->getLogin());

            if ($LdapAuthData->getStatusCode() === 49) {
                $this->LogMessage->addDescription(__('Login incorrecto', false));

                $this->addTracking();

                throw new AuthException(SPException::SP_INFO, $this->LogMessage->getDescription(), '', self::STATUS_INVALID_LOGIN);
            }

            if ($LdapAuthData->getStatusCode() === 701) {
                $this->LogMessage->addDescription(__('Cuenta expirada', false));

                throw new AuthException(SPException::SP_INFO, $this->LogMessage->getDescription(), '', self::STATUS_USER_DISABLED);
            }

            if ($LdapAuthData->getStatusCode() === 702) {
                $this->LogMessage->addDescription(__('El usuario no tiene grupos asociados', false));

                throw new AuthException(SPException::SP_INFO, $this->LogMessage->getDescription(), '', self::STATUS_USER_DISABLED);
            }

            $this->LogMessage->addDescription(__('Error interno', false));

            throw new AuthException(SPException::SP_INFO, $this->LogMessage->getDescription(), '', self::STATUS_INTERNAL_ERROR);
        }

        $this->UserData->setUserName($LdapAuthData->getName());
        $this->UserData->setUserEmail($LdapAuthData->getEmail());

        $this->LogMessage->addDetails(__('Tipo', false), __FUNCTION__);
        $this->LogMessage->addDetails(__('Servidor LDAP', false), $LdapAuthData->getServer());

        try {
            $this->UserData->setUserLogin($this->UserData->getLogin());

            // Verificamos si el usuario existe en la BBDD
            if (UserLdap::checkLDAPUserInDB($this->UserData->getLogin())) {
                // Actualizamos el usuario de LDAP en MySQL
                UserLdap::getItem($this->UserData)->updateOnLogin();
            } else {
                // Creamos el usuario de LDAP en MySQL
                UserLdap::getItem($this->UserData)->add();
            }
        } catch (SPException $e) {
            $this->LogMessage->addDescription($e->getMessage());

            throw new AuthException(SPException::SP_ERROR, __('Error interno', false), '', self::STATUS_INTERNAL_ERROR);
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
            $this->LogMessage->addDescription(__('Login incorrecto', false));
            $this->LogMessage->addDetails(__('Usuario', false), $this->UserData->getLogin());

            $this->addTracking();

            throw new AuthException(SPException::SP_INFO, $this->LogMessage->getDescription(), '', self::STATUS_INVALID_LOGIN);
        }

        if ($AuthData->getAuthenticated() === 1) {
            $this->LogMessage->addDetails(__('Tipo', false), __FUNCTION__);
        }

        return true;
    }

    /**
     * Comprobar si el cliente ha enviado las variables de autentificación
     *
     * @param BrowserAuthData $AuthData
     * @throws \SP\Core\Exceptions\SPException
     * @throws AuthException
     */
    protected function authBrowser(BrowserAuthData $AuthData)
    {
        // Comprobar si concide el login con la autentificación del servidor web
        if ($AuthData->getAuthenticated() === 0) {
            $this->LogMessage->addDescription(__('Login incorrecto', false));
            $this->LogMessage->addDetails(__('Tipo', false), __FUNCTION__);
            $this->LogMessage->addDetails(__('Usuario', false), $this->UserData->getLogin());
            $this->LogMessage->addDetails(__('Autentificación', false), sprintf('%s (%s)', AuthUtil::getServerAuthType(), $AuthData->getName()));

            $this->addTracking();

            throw new AuthException(SPException::SP_INFO, $this->LogMessage->getDescription(), '', self::STATUS_INVALID_LOGIN);
        }

        if ($AuthData->getAuthenticated() === 1) {
            $this->LogMessage->addDetails(__('Tipo', false), __FUNCTION__);
        }
    }
}