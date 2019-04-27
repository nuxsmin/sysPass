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

namespace SP\Core\Context;

use SP\Core\Crypt\Vault;
use SP\DataModel\Dto\AccountCache;
use SP\DataModel\ProfileData;
use SP\Services\Account\AccountSearchFilter;
use SP\Services\User\UserLoginResponse;

/**
 * Class Session
 *
 * @package SP\Core\Session
 */
final class SessionContext extends ContextBase
{
    const MAX_SID_TIME = 120;

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
        if (!self::$isLocked) {
            logger('Session closed');

            session_write_close();

            self::$isLocked = true;
        }
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
        return $this->getContextKey('theme');
    }

    /**
     * Devolver una variable de sesión
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getContextKey(string $key, $default = null)
    {
        try {
            return parent::getContextKey($key, $default);
        } catch (ContextException $e) {
            processException($e);
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
        $this->setContextKey('theme', $theme);
    }

    /**
     * Establecer una variable de sesión
     *
     * @param string $key   El nombre de la variable
     * @param mixed  $value El valor de la variable
     *
     * @return mixed
     */
    protected function setContextKey(string $key, $value)
    {
        try {
            if (self::$isLocked) {
                logger('Session locked; key=' . $key);
            } else {
                parent::setContextKey($key, $value);
            }

            return $value;
        } catch (ContextException $e) {
            processException($e);
        }

        return null;
    }

    /**
     * Establecer la hora de carga de la configuración
     *
     * @param int $time
     */
    public function setConfigTime($time)
    {
        $this->setContextKey('configTime', (int)$time);
    }

    /**
     * Devolver la hora de carga de la configuración
     *
     * @return int
     */
    public function getConfigTime()
    {
        return $this->getContextKey('configTime');
    }

    /**
     * Establece los datos del usuario en la sesión.
     *
     * @param UserLoginResponse $userLoginResponse
     */
    public function setUserData(UserLoginResponse $userLoginResponse = null)
    {
        $this->setContextKey('userData', $userLoginResponse);
    }

    /**
     * Obtiene el objeto de perfil de usuario de la sesión.
     *
     * @return ProfileData
     */
    public function getUserProfile()
    {
        return $this->getContextKey('userProfile');
    }

    /**
     * Establece el objeto de perfil de usuario en la sesión.
     *
     * @param ProfileData $ProfileData
     */
    public function setUserProfile(ProfileData $ProfileData)
    {
        $this->setContextKey('userProfile', $ProfileData);
    }

    /**
     * @return AccountSearchFilter
     */
    public function getSearchFilters()
    {
        return $this->getContextKey('searchFilters', null);
    }

    /**
     * @param AccountSearchFilter $searchFilters
     */
    public function setSearchFilters(AccountSearchFilter $searchFilters)
    {
        $this->setContextKey('searchFilters', $searchFilters);
    }

    public function resetAccountAcl()
    {
        $this->setContextKey('accountAcl', null);
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
        return $this->getContextKey('userData', new UserLoginResponse());
    }

    /**
     * Establecer si el usuario está completamente autorizado
     *
     * @param $bool
     */
    public function setAuthCompleted($bool)
    {
        $this->setContextKey('authCompleted', (bool)$bool);
    }

    /**
     * Devolver si el usuario está completamente logeado
     */
    public function getAuthCompleted()
    {
        return $this->getContextKey('authCompleted', false);
    }

    /**
     * Devolver la clave maestra temporal
     *
     * @return string
     */
    public function getTemporaryMasterPass()
    {
        return $this->getContextKey('tempmasterpass');
    }

    /**
     * Sets a temporary master password
     *
     * @param string $password
     */
    public function setTemporaryMasterPass(string $password)
    {
        $this->setContextKey('tempmasterpass', $password);
    }

    /**
     * @return mixed
     */
    public function getSecurityKey()
    {
        return $this->getContextKey('sk');
    }

    /**
     * @param string $salt
     *
     * @return string
     */
    public function generateSecurityKey(string $salt)
    {
        return $this->setSecurityKey(sha1(time() . $salt));
    }

    /**
     * @param $sk
     *
     * @return mixed
     */
    public function setSecurityKey($sk)
    {
        return $this->setContextKey('sk', $sk);
    }

    /**
     * Devolver la clave pública
     *
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->getContextKey('pubkey');
    }

    /**
     * Establecer la clave pública
     *
     * @param $key
     */
    public function setPublicKey($key)
    {
        $this->setContextKey('pubkey', $key);
    }

    /**
     * Devuelve el timeout de la sesión
     *
     * @return int|null El valor en segundos
     */
    public function getSessionTimeout()
    {
        return $this->getContextKey('sessionTimeout');
    }

    /**
     * Establecer el timeout de la sesión
     *
     * @param int $timeout El valor en segundos
     *
     * @return int
     */
    public function setSessionTimeout($timeout)
    {
        $this->setContextKey('sessionTimeout', $timeout);

        return $timeout;
    }

    /**
     * Devuelve la hora de la última actividad
     *
     * @return int
     */
    public function getLastActivity()
    {
        return $this->getContextKey('lastActivity', 0);
    }

    /**
     * Establece la hora de la última actividad
     *
     * @param $time int La marca de hora
     */
    public function setLastActivity($time)
    {
        $this->setContextKey('lastActivity', $time);
    }

    /**
     * Devuelve la hora de inicio de actividad.
     *
     * @return int
     */
    public function getStartActivity()
    {
        return $this->getContextKey('startActivity', 0);
    }

    /**
     * Establecer el lenguaje de la sesión
     *
     * @param $locale
     */
    public function setLocale($locale)
    {
        $this->setContextKey('locale', $locale);
    }

    /**
     * Devuelve el lenguaje de la sesión
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->getContextKey('locale');
    }

    /**
     * Devolver el color asociado a una cuenta
     *
     * @return string
     */
    public function getAccountColor()
    {
        return $this->getContextKey('accountcolor');
    }

    /**
     * Establece el color asociado a una cuenta
     *
     * @param array $color
     */
    public function setAccountColor(array $color)
    {
        $this->setContextKey('accountcolor', $color);
    }

    /**
     * Devuelve el estado de la aplicación
     *
     * @return bool
     */
    public function getAppStatus()
    {
        return $this->getContextKey('status');
    }

    /**
     * Establecer el estado de la aplicación
     *
     * @param string $status
     */
    public function setAppStatus($status)
    {
        $this->setContextKey('status', $status);
    }

    /**
     * Reset del estado de la aplicación
     *
     * @return bool
     */
    public function resetAppStatus()
    {
        return $this->setContextKey('status', null);
    }

    /**
     * Devuelve la clave maestra encriptada
     *
     * @return Vault
     */
    public function getVault()
    {
        return $this->getContextKey('vault');
    }

    /**
     * Establecer la clave maestra encriptada
     *
     * @param Vault $vault
     */
    public function setVault(Vault $vault)
    {
        $this->setContextKey('vault', $vault);
    }

    /**
     * Establece la cache de cuentas
     *
     * @param array $accountsCache
     */
    public function setAccountsCache(array $accountsCache)
    {
        $this->setContextKey('accountsCache', $accountsCache);
    }

    /**
     * Devuelve la cache de cuentas
     *
     * @return AccountCache[]
     */
    public function getAccountsCache()
    {
        return $this->getContextKey('accountsCache');
    }

    /**
     * @throws ContextException
     */
    public function initialize()
    {
        // Si la sesión no puede ser iniciada, devolver un error 500
        if (session_start() === false) {
            throw new ContextException(__u('Session cannot be initialized'));
        }

        $this->setContextReference($_SESSION);

        if ($this->getSidStartTime() === 0) {
            $this->setSidStartTime(time());
            $this->setStartActivity(time());
        }
    }

    /**
     * Devuelve la hora en la que el SID de sesión fue creado
     *
     * @return int
     */
    public function getSidStartTime()
    {
        return $this->getContextKey('sidStartTime', 0);
    }

    /**
     * Establece la hora de creación del SID
     *
     * @param $time int La marca de hora
     *
     * @return int
     */
    public function setSidStartTime($time)
    {
        $this->setContextKey('sidStartTime', $time);

        return $time;
    }

    /**
     * Establece la hora de inicio de actividad
     *
     * @param $time int La marca de hora
     *
     * @return int
     */
    public function setStartActivity($time)
    {
        $this->setContextKey('startActivity', $time);

        return $time;
    }

    /**
     * @param string $ctxKeyName
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function setPluginKey(string $ctxKeyName, string $key, $value)
    {
        /** @var ContextCollection $ctxKey */
        $ctxKey = $this->getContextKey($ctxKeyName, new ContextCollection());

        $this->setContextKey($ctxKeyName, $ctxKey->set($key, $value));

        return $value;
    }

    /**
     * @param string $ctxKeyName
     * @param string $key
     *
     * @return mixed
     */
    public function getPluginKey(string $ctxKeyName, string $key)
    {
        /** @var ContextCollection $ctxKey */
        $ctxKey = $this->getContextKey($ctxKeyName);

        if ($ctxKey !== null) {
            return $ctxKey->get($key);
        }

        return null;
    }
}
