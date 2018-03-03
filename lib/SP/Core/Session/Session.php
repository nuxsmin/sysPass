<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Core\Session;

use SP\Account\AccountAcl;
use SP\Account\AccountSearchFilter;
use SP\Config\ConfigData;
use SP\DataModel\ProfileData;
use SP\Services\User\UserLoginResponse;

/**
 * Class Session
 *
 * @package SP\Core\Session
 */
class Session
{
    private static $isReset = false;
    private static $isLocked = false;

    /**
     * @return bool
     */
    public static function isLocked()
    {
        return self::$isLocked;
    }

    /**
     * Closes session
     */
    public static function close()
    {
        debugLog('Session closed');

        session_write_close();

        self::$isLocked = true;
    }

    /**
     * Destruir la sesión y reiniciar
     */
    public static function restart()
    {
        self::$isReset = true;

        session_unset();
        session_destroy();
        session_start();
    }

    /**
     * Devuelve el tema visual utilizado en sysPass
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->getSessionKey('theme');
    }

    /**
     * Devolver una variable de sesión
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function getSessionKey($key, $default = null)
    {
        if (isset($_SESSION[$key])) {
            return is_numeric($default) ? (int)$_SESSION[$key] : $_SESSION[$key];
        }

        return $default;
    }

    /**
     * Establece el tema visual utilizado en sysPass
     *
     * @param $theme string El tema visual a utilizar
     */
    public function setTheme($theme)
    {
        $this->setSessionKey('theme', $theme);
    }

    /**
     * Establecer una variable de sesión
     *
     * @param string $key   El nombre de la variable
     * @param mixed  $value El valor de la variable
     * @return mixed
     */
    protected function setSessionKey($key, $value)
    {
        if (self::$isLocked) {
            debugLog('Session locked; key=' . $key);
        } else {
            $_SESSION[$key] = $value;
        }

        return $value;
    }

    /**
     * Establecer la configuración
     *
     * @param ConfigData $config
     */
    public function setConfig(ConfigData $config)
    {
        $this->setSessionKey('config', $config);
    }

    /**
     * Establecer la hora de carga de la configuración
     *
     * @param $time
     */
    public function setConfigTime($time)
    {
        $this->setSessionKey('configTime', $time);
    }

    /**
     * Devolver la hora de carga de la configuración
     *
     * @return int
     */
    public function getConfigTime()
    {
        return $this->getSessionKey('configTime');
    }

    /**
     * Establece los datos del usuario en la sesión.
     *
     * @param UserLoginResponse $userLoginResponse
     */
    public function setUserData(UserLoginResponse $userLoginResponse = null)
    {
        $this->setSessionKey('userData', $userLoginResponse);
    }

    /**
     * Obtiene el objeto de perfil de usuario de la sesión.
     *
     * @return ProfileData
     */
    public function getUserProfile()
    {
        return $this->getSessionKey('userProfile');
    }

    /**
     * Establece el objeto de perfil de usuario en la sesión.
     *
     * @param ProfileData $ProfileData
     */
    public function setUserProfile(ProfileData $ProfileData)
    {
        $this->setSessionKey('userProfile', $ProfileData);
    }

    /**
     * @return AccountSearchFilter
     */
    public function getSearchFilters()
    {
        return $this->getSessionKey('searchFilters', null);
    }

    /**
     * @param AccountSearchFilter $searchFilters
     */
    public function setSearchFilters(AccountSearchFilter $searchFilters)
    {
        $this->setSessionKey('searchFilters', $searchFilters);
    }

    /**
     * Establecer la ACL de una cuenta
     *
     * @param AccountAcl $AccountAcl
     */
    public function setAccountAcl(AccountAcl $AccountAcl)
    {
        $_SESSION['accountAcl'][$AccountAcl->getAccountId()] = $AccountAcl;
    }

    /**
     * Devolver la ACL de una cuenta
     *
     * @param $accountId
     *
     * @return null|AccountAcl
     */
    public function getAccountAcl($accountId)
    {
        if (isset($_SESSION['accountAcl'][$accountId])) {
            return $_SESSION['accountAcl'][$accountId];
        }

        return null;
    }

    public function resetAccountAcl()
    {
        $this->setSessionKey('accountAcl', null);
    }

    /**
     * Returns if user is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return self::$isReset === false && $this->getUserData()->getLogin()
            && is_object($this->getUserData()->getPreferences());
    }

    /**
     * Devuelve los datos del usuario en la sesión.
     *
     * @return UserLoginResponse
     */
    public function getUserData()
    {
        return $this->getSessionKey('userData', new UserLoginResponse());
    }

    /**
     * Establecer si el usuario está completamente autorizado
     *
     * @param $bool
     */
    public function setAuthCompleted($bool)
    {
        $this->setSessionKey('authCompleted', (bool)$bool);
    }

    /**
     * Devolver si el usuario está completamente logeado
     */
    public function getAuthCompleted()
    {
        return $this->getSessionKey('authCompleted', false);
    }

    /**
     * Devolver la clave maestra temporal
     *
     * @return string
     */
    public function getTemporaryMasterPass()
    {
        return $this->getSessionKey('tempmasterpass');
    }

    /**
     * Establece la clave maestra temporal
     *
     * @param string $password
     */
    public function setTemporaryMasterPass($password)
    {
        $this->setSessionKey('tempmasterpass', $password);
    }

    /**
     * @return mixed
     */
    public function getSecurityKey()
    {
        return $this->getSessionKey('sk');
    }

    /**
     * @return string
     */
    public function generateSecurityKey()
    {
        return $this->setSecurityKey(sha1(time() . $this->getConfig()->getPasswordSalt()));
    }

    /**
     * @param $sk
     * @return mixed
     */
    public function setSecurityKey($sk)
    {
        return $this->setSessionKey('sk', $sk);
    }

    /**
     * Devolver la configuración
     *
     * @return ConfigData
     */
    public function getConfig()
    {
        return $this->getSessionKey('config');
    }

    /**
     * Devolver la clave pública
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->getSessionKey('pubkey');
    }

    /**
     * Establecer la clave pública
     *
     * @param $key
     */
    public function setPublicKey($key)
    {
        $this->setSessionKey('pubkey', $key);
    }

    /**
     * Devuelve el timeout de la sesión
     *
     * @return int|null El valor en segundos
     */
    public function getSessionTimeout()
    {
        return $this->getSessionKey('sessionTimeout');
    }

    /**
     * Establecer el timeout de la sesión
     *
     * @param int $timeout El valor en segundos
     * @return int
     */
    public function setSessionTimeout($timeout)
    {
        $this->setSessionKey('sessionTimeout', $timeout);

        return $timeout;
    }

    /**
     * Devuelve la hora de la última actividad
     *
     * @return int
     */
    public function getLastActivity()
    {
        return $this->getSessionKey('lastActivity', 0);
    }

    /**
     * Establece la hora de la última actividad
     *
     * @param $time int La marca de hora
     */
    public function setLastActivity($time)
    {
        $this->setSessionKey('lastActivity', $time);
    }

    /**
     * Devuelve la hora en la que el SID de sesión fue creado
     *
     * @return int
     */
    public function getSidStartTime()
    {
        return $this->getSessionKey('sidStartTime', 0);
    }

    /**
     * Establece la hora de creación del SID
     *
     * @param $time int La marca de hora
     * @return int
     */
    public function setSidStartTime($time)
    {
        $this->setSessionKey('sidStartTime', $time);

        return $time;
    }

    /**
     * Devuelve la hora de inicio de actividad.
     *
     * @return int
     */
    public function getStartActivity()
    {
        return $this->getSessionKey('startActivity', 0);
    }

    /**
     * Establece la hora de inicio de actividad
     *
     * @param $time int La marca de hora
     * @return int
     */
    public function setStartActivity($time)
    {
        $this->setSessionKey('startActivity', $time);

        return $time;
    }

    /**
     * Establecer el lenguaje de la sesión
     *
     * @param $locale
     */
    public function setLocale($locale)
    {
        $this->setSessionKey('locale', $locale);
    }

    /**
     * Devuelve el lenguaje de la sesión
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->getSessionKey('locale');
    }

    /**
     * Devolver el color asociado a una cuenta
     *
     * @return string
     */
    public function getAccountColor()
    {
        return $this->getSessionKey('accountcolor');
    }

    /**
     * Establece el color asociado a una cuenta
     *
     * @param array $color
     */
    public function setAccountColor(array $color)
    {
        $this->setSessionKey('accountcolor', $color);
    }

    /**
     * Devuelve si se ha realizado un cierre de sesión
     *
     * @return bool
     */
    public function getLoggedOut()
    {
        return $this->getSessionKey('loggedout', false);
    }

    /**
     * Establecer si se ha realizado un cierre de sesión
     *
     * @param bool $loggedout
     */
    public function setLoggedOut($loggedout = false)
    {
        $this->setSessionKey('loggedout', $loggedout);
    }
}
