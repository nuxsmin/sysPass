<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\DataModel\UserData;
use SP\DataModel\UserPassRecoverData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Log\Log;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserLdap;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserPassRecover;
use SP\Mgmt\Users\UserPreferences;
use SP\Mgmt\Users\UserUtil;
use SP\Util\Json;
use SP\Util\Util;

/**
 * Class LoginController
 *
 * @package SP\Controller
 */
class LoginController
{
    const STATUS_INVALID_LOGIN = 1;
    const STATUS_INVALID_MASTER_PASS = 2;
    const STATUS_USER_DISABLED = 3;
    const STATUS_INTERNAL_ERROR = 4;
    const STATUS_NEED_OLD_PASS = 5;

    /**
     * @var JsonResponse
     */
    protected $jsonResponse;
    /**
     * @var UserData
     */
    protected $UserData;
    /**
     * @var Log
     */
    protected $Log;

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->jsonResponse = new JsonResponse();
        $this->UserData = new UserData();
        $this->Log = new Log();
    }

    /**
     * Ejecutar las acciones de login
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doLogin()
    {
        $userLogin = Request::analyze('user');
        $userPass = Request::analyzeEncrypted('pass');

        if (!$userLogin || !$userPass) {
            $this->jsonResponse->setDescription(_('Usuario/Clave no introducidos'));
            Json::returnJson($this->jsonResponse);
        }

        $this->UserData->setUserLogin($userLogin);
        $this->UserData->setUserPass($userPass);

        $this->Log = new Log(_('Inicio sesión'));

        try {
            $Auth = new Auth($this->UserData);
            $result = $Auth->doAuth();

            if ($result !== false) {
                // Ejecutar la acción asociada al tipo de autentificación

                /** @var AuthResult $AuthResult */
                foreach ($result as $AuthResult) {
                    $this->{$AuthResult->getAuth()}($AuthResult->getData());
                }
            } else {
                throw new AuthException(SPException::SP_INFO, _('Login incorrecto'), '', self::STATUS_INVALID_LOGIN);
            }

            $this->getUserData($userPass);
            $this->checkUserDisabled();
            $this->checkPasswordChange();
            $this->setUserSession();
            $this->loadUserPreferences();
        } catch (SPException $e) {
            $this->jsonResponse->setDescription($e->getMessage());
            $this->jsonResponse->setStatus($e->getCode());

            Json::returnJson($this->jsonResponse);
        }

        $data = ['url' => 'index.php' . Request::importUrlParamsToGet()];
        $this->jsonResponse->setStatus(0);
        $this->jsonResponse->setData($data);
        Json::returnJson($this->jsonResponse);
    }

    /**
     * Obtener los datos del usuario
     *
     * @param $userPass
     * @throws SPException
     */
    protected function getUserData($userPass)
    {
        $this->Log->resetDescription();

        try {
            $this->UserData = User::getItem($this->UserData)->getByLogin($this->UserData->getUserLogin());
            $this->UserData->setUserPass($userPass);
        } catch (SPException $e) {
            $this->Log->setLogLevel(Log::ERROR);
            $this->Log->addDescription(_('Error al obtener los datos del usuario de la BBDD'));
            $this->Log->writeLog();

            throw new AuthException(SPException::SP_ERROR, _('Error interno'), '', self::STATUS_INTERNAL_ERROR);
        }
    }

    /**
     * omprobar si el usuario está deshabilitado
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function checkUserDisabled()
    {
        $this->Log->resetDescription();

        // Comprobar si el usuario está deshabilitado
        if ($this->UserData->isUserIsDisabled()) {
            $this->Log->addDescription(_('Usuario deshabilitado'));
            $this->Log->addDetails(_('Usuario'), $this->UserData->getUserLogin());
            $this->Log->writeLog();

            throw new AuthException(SPException::SP_INFO, _('Usuario deshabilitado'), '', self::STATUS_USER_DISABLED);
        }

        return false;
    }

    /**
     * Comprobar si se ha forzado un cambio de clave
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function checkPasswordChange()
    {
        // Comprobar si se ha forzado un cambio de clave
        if ($this->UserData->isUserIsChangePass()) {
            $hash = Util::generateRandomBytes();

            $UserPassRecoverData = new UserPassRecoverData();
            $UserPassRecoverData->setUserpassrUserId($this->UserData->getUserId());
            $UserPassRecoverData->setUserpassrHash($hash);

            if (UserPassRecover::getItem($UserPassRecoverData)->add()) {
                $data = ['url' => Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time() . '&f=1'];
                $this->jsonResponse->setData($data);
                Json::returnJson($this->jsonResponse);
            }
        }

        return false;
    }

    /**
     * Cargar la sesión del usuario
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function setUserSession()
    {
        $UserPass = $this->loadMasterPass();

        // Obtenemos la clave maestra del usuario
        if ($UserPass->getClearUserMPass() !== '') {
            // Actualizar el último login del usuario
            UserUtil::setUserLastLogin($this->UserData->getUserId());

            // Cargar las variables de sesión del usuario
            SessionUtil::loadUserSession($this->UserData);

            $this->Log->addDetails(_('Usuario'), $this->UserData->getUserLogin());
            $this->Log->addDetails(_('Perfil'), Profile::getItem()->getById($this->UserData->getUserProfileId())->getUserprofileName());
            $this->Log->addDetails(_('Grupo'), Group::getItem()->getById($this->UserData->getUserGroupId())->getUsergroupName());
            $this->Log->writeLog();
        } else {
            $this->Log->setLogLevel(Log::ERROR);
            $this->Log->addDescription(_('Error al obtener la clave maestra del usuario'));
            $this->Log->writeLog();

            throw new AuthException(SPException::SP_ERROR, _('Error interno'), '', self::STATUS_INTERNAL_ERROR);
        }
    }

    /**
     * Cargar la clave maestra o solicitarla
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function loadMasterPass()
    {
        $this->Log->resetDescription();

        $masterPass = Request::analyzeEncrypted('mpass');
        $oldPass = Request::analyzeEncrypted('oldpass');

        $UserPass = UserPass::getItem($this->UserData);

        if ($masterPass) {
            if (CryptMasterPass::checkTempMasterPass($masterPass)) {
                $masterPass = CryptMasterPass::getTempMasterPass($masterPass);
            }

            if (!$UserPass->updateUserMPass($masterPass)) {
                $this->Log->addDescription(_('Clave maestra incorrecta'));
                $this->Log->writeLog();

                throw new AuthException(SPException::SP_INFO, _('Clave maestra incorrecta'), '', self::STATUS_INVALID_MASTER_PASS);
            } else {
                SessionUtil::saveSessionMPass($UserPass->getClearUserMPass());

                Log::writeNewLog(_('Login'), _('Clave maestra actualizada'));
            }
        } else if ($oldPass) {
            if (!$UserPass->updateMasterPass($oldPass)) {
                $this->Log->addDescription(_('Clave maestra incorrecta'));
                $this->Log->writeLog();

                throw new AuthException(SPException::SP_INFO, _('Clave maestra incorrecta'), '', self::STATUS_INVALID_MASTER_PASS);
            } else {
                SessionUtil::saveSessionMPass($UserPass->getClearUserMPass());

                Log::writeNewLog(_('Login'), _('Clave maestra actualizada'));
            }
        } else {
            $loadMPass = $UserPass->loadUserMPass();

            // Comprobar si es necesario actualizar la clave maestra
            if ($loadMPass === false) {
                throw new AuthException(SPException::SP_INFO, _('Es necesaria su clave anterior'), '', self::STATUS_NEED_OLD_PASS);
                // La clave no está establecida o se ha sido cambiada por el administrador
            } else if ($loadMPass === null || !$UserPass->checkUserUpdateMPass()) {
                throw new AuthException(SPException::SP_INFO, _('La clave maestra no ha sido guardada o es incorrecta'), '', self::STATUS_INVALID_MASTER_PASS);
            }
        }

        return $UserPass;
    }

    /**
     * Cargar las preferencias del usuario y comprobar si usa 2FA
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function loadUserPreferences()
    {
        $UserPreferencesData = UserPreferences::getItem()->getById($this->UserData->getUserId());
        Language::setLanguage(true);
        DiFactory::getTheme()->initTheme(true);
        Session::setUserPreferences($UserPreferencesData);
        Session::setSessionType(Session::SESSION_INTERACTIVE);

        if ($UserPreferencesData->isUse2Fa()) {
            Session::set2FApassed(false);

            $data = ['url' => Init::$WEBURI . '/index.php?a=2fa&i=' . $this->UserData->getUserId() . '&t=' . time() . '&f=1'];
            $this->jsonResponse->setData($data);
            $this->jsonResponse->setStatus(0);
            Json::returnJson($this->jsonResponse);
        } else {
            Session::set2FApassed(true);
        }
    }

    /**
     * Autentificación LDAP
     *
     * @param LdapAuthData $LdapAuthData
     * @return bool
     * @throws AuthException
     */
    protected function authLdap(LdapAuthData $LdapAuthData)
    {
        $this->Log->resetDescription();

        if ($LdapAuthData->getStatusCode() > 0) {
            $this->Log->addDetails(_('Tipo'), __FUNCTION__);
            $this->Log->addDetails(_('Usuario'), $this->UserData->getUserLogin());

            if ($LdapAuthData->getStatusCode() === 49) {
                $this->Log->addDescription(_('Login incorrecto'));
                $this->Log->writeLog();

                throw new AuthException(SPException::SP_INFO, $this->Log->getDescription(), '', self::STATUS_INVALID_LOGIN);
            } elseif ($LdapAuthData->getStatusCode() === 701) {
                $this->Log->addDescription(_('Cuenta expirada'));
                $this->Log->writeLog();

                throw new AuthException(SPException::SP_INFO, $this->Log->getDescription(), '', self::STATUS_USER_DISABLED);
            } else if ($LdapAuthData->getStatusCode() === 702) {
                $this->Log->addDescription(_('El usuario no tiene grupos asociados'));
                $this->Log->writeLog();

                throw new AuthException(SPException::SP_INFO, $this->Log->getDescription(), '', self::STATUS_USER_DISABLED);
            } else {
                $this->Log->addDescription(_('Error interno'));
                $this->Log->writeLog();

                throw new AuthException(SPException::SP_INFO, $this->Log->getDescription(), '', self::STATUS_INTERNAL_ERROR);
            }
        }

        $this->UserData->setUserName($LdapAuthData->getName());
        $this->UserData->setUserEmail($LdapAuthData->getEmail());

        $this->Log->addDetails(_('Tipo'), __FUNCTION__);
        $this->Log->addDetails(_('Servidor LDAP'), $LdapAuthData->getServer());

        try {
            // Verificamos si el usuario existe en la BBDD
            if (!UserLdap::checkLDAPUserInDB($this->UserData->getUserLogin())) {
                // Creamos el usuario de LDAP en MySQL
                UserLdap::getItem($this->UserData)->add();
            } else {
                // Actualizamos el usuario de LDAP en MySQL
                UserLdap::getItem($this->UserData)->update();
            }
        } catch (SPException $e) {
            $this->Log->setLogLevel(Log::ERROR);
            $this->Log->addDescription($e->getMessage());
            $this->Log->writeLog();

            throw new AuthException(SPException::SP_ERROR, _('Error interno'), '', self::STATUS_INTERNAL_ERROR);
        }

        return true;
    }

    /**
     * Autentificación en BD
     *
     * @param DatabaseAuthData $AuthData
     * @return bool
     * @throws AuthException
     */
    protected function authDatabase(DatabaseAuthData $AuthData)
    {
        $this->Log->resetDescription();

        // Autentificamos con la BBDD
        if ($AuthData->getAuthenticated() === 0) {
            $this->Log->addDescription(_('Login incorrecto'));
            $this->Log->addDetails(_('Usuario'), $this->UserData->getUserLogin());
            $this->Log->writeLog();

            throw new AuthException(SPException::SP_INFO, $this->Log->getDescription(), '', self::STATUS_INVALID_LOGIN);
        }

        $this->Log->addDetails(_('Tipo'), __FUNCTION__);

        return true;
    }

    /**
     * Comprobar si el cliente ha enviado las variables de autentificación
     *
     * @param BrowserAuthData $AuthData
     * @return bool
     * @throws AuthException
     */
    protected function authBrowser(BrowserAuthData $AuthData)
    {
        // Comprobar si concide el login con la autentificación del servidor web
        if ($AuthData->getAuthenticated() === 0) {
            $this->Log->resetDescription();
            $this->Log->addDescription(_('Login incorrecto'));
            $this->Log->addDetails(_('Tipo'), __FUNCTION__);
            $this->Log->addDetails(_('Usuario'), $this->UserData->getUserLogin());
            $this->Log->addDetails(_('Autentificación'), sprintf('%s (%s)', AuthUtil::getServerAuthType(), $AuthData->getName()));
            $this->Log->writeLog();

            throw new AuthException(SPException::SP_INFO, $this->Log->getDescription(), '', self::STATUS_INVALID_LOGIN);
        }

        return true;
    }
}