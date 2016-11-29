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
use SP\Auth\AuthUtil;
use SP\Auth\Database\DatabaseAuthData;
use SP\Auth\Ldap\LdapAuthDataBase;
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
                if ($result['type'] === 'ldap') {
                    $this->authLdap($result['data']);
                } elseif ($result['type'] === 'mysql') {
                    $this->authDatabase($result['data']);
                }
            } else {
                throw new AuthException(SPException::SP_INFO, _('Usuario/Clave incorrectos'));
            }

            $this->checkServerAuth();
            $this->getUserData($userPass);
            $this->checkUserDisabled();
            $this->checkPasswordChange();
            $this->setUserSession();
            $this->loadUserPreferences();
        } catch (SPException $e) {
            $this->jsonResponse->setDescription($e->getMessage());
            Json::returnJson($this->jsonResponse);
        }

        $data = ['url' => 'index.php' . Request::importUrlParamsToGet()];
        $this->jsonResponse->setStatus(0);
        $this->jsonResponse->setData($data);
        Json::returnJson($this->jsonResponse);
    }

    /**
     * Autentificación LDAP
     *
     * @param LdapAuthDataBase $LdapAuthData
     * @return bool
     * @throws AuthException
     */
    protected function authLdap(LdapAuthDataBase $LdapAuthData)
    {
        $this->Log->resetDescription();

        if ($LdapAuthData->getStatus() > 0) {
            $this->Log->addDetails(_('Tipo'), 'LDAP');
            $this->Log->addDetails(_('Usuario'), $this->UserData->getUserLogin());

            if ($LdapAuthData->getStatus() === 49) {
                $this->Log->addDescription(_('Login incorrecto'));
            } elseif ($LdapAuthData->getStatus() === 701) {
                $this->Log->addDescription(_('Cuenta expirada'));
            } else if ($LdapAuthData->getStatus() === 702) {
                $this->Log->addDescription(_('El usuario no tiene grupos asociados'));
            } else {
                $this->Log->addDescription(_('Error interno'));
            }

            $this->Log->writeLog();

            throw new AuthException(SPException::SP_INFO, $this->Log->getDescription());
        } else {
            $this->UserData->setUserName($LdapAuthData->getName());
            $this->UserData->setUserEmail($LdapAuthData->getEmail());

            $this->Log->addDetails(_('Tipo'), 'LDAP');
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

                throw new AuthException(SPException::SP_ERROR, _('Error interno'));
            }
        }
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
        if ($AuthData->getStatus() > 0) {
            $this->Log->addDescription(_('Login incorrecto'));
            $this->Log->addDetails(_('Usuario'), $this->UserData->getUserLogin());
            $this->Log->writeLog();

            throw new AuthException(SPException::SP_INFO, _('Usuario/Clave incorrectos'));
        } else {
            $this->Log->addDetails(_('Tipo'), 'MySQL');
        }

        return true;
    }

    /**
     * Comprobar si el cliente ha enviado las variables de autentificación
     *
     * @throws SPException
     */
    protected function checkServerAuth()
    {
        $this->Log->resetDescription();

        // Comprobar si concide el login con la autentificación del servidor web
        if (!AuthUtil::checkServerAuthUser($this->UserData->getUserLogin())) {
            $this->Log->addDescription(_('Login incorrecto'));
            $this->Log->addDetails(_('Usuario'), $this->UserData->getUserLogin());
            $this->Log->addDetails(_('Autentificación'), sprintf('%s (%s)', AuthUtil::getServerAuthType(), AuthUtil::getServerAuthUser()));
            $this->Log->writeLog();

            throw new AuthException(SPException::SP_INFO, _('Usuario/Clave incorrectos'));
        }

        return true;
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

            throw new AuthException(SPException::SP_ERROR, _('Error interno'));
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

            throw new AuthException(SPException::SP_INFO, _('Usuario deshabilitado'));
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
        if ($UserPass->getClearUserMPass()) {
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

            throw new AuthException(SPException::SP_ERROR, _('Error interno'));
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

        $UserPass = UserPass::getItem($this->UserData);

        // Comprobamos que la clave maestra del usuario es correcta y está actualizada
        if (!$masterPass
            && (!$UserPass->loadUserMPass() || !UserPass::checkUserUpdateMPass($this->UserData->getUserId()))
        ) {
            $this->jsonResponse->setStatus(2);

            throw new AuthException(SPException::SP_INFO, _('La clave maestra no ha sido guardada o es incorrecta'));
        } elseif ($masterPass) {
            if (CryptMasterPass::checkTempMasterPass($masterPass)) {
                $masterPass = CryptMasterPass::getTempMasterPass($masterPass);
            }

            if (!$UserPass->updateUserMPass($masterPass)) {
                $this->Log->addDescription(_('Clave maestra incorrecta'));
                $this->Log->writeLog();

                $this->jsonResponse->setStatus(2);

                throw new AuthException(SPException::SP_INFO, _('Clave maestra incorrecta'));
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
}