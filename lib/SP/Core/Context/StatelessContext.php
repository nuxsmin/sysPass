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

use SP\DataModel\ProfileData;
use SP\Services\User\UserLoginResponse;

/**
 * Class ApiContext
 *
 * @package SP\Core\Context
 */
final class StatelessContext extends ContextBase
{
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
            parent::setContextKey($key, $value);

            return $value;
        } catch (ContextException $e) {
            processException($e);
        }

        return null;
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
     * Establece el objeto de perfil de usuario en la sesión.
     *
     * @param ProfileData $ProfileData
     */
    public function setUserProfile(ProfileData $ProfileData)
    {
        $this->setContextKey('userProfile', $ProfileData);
    }

    /**
     * Returns if user is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return !empty($this->getUserData()->getLogin());
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
     * @return void
     * @throws ContextException
     */
    public function initialize()
    {
        $this->setContext(new ContextCollection());
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
     * @return null
     */
    public function getAccountsCache()
    {
        return null;
    }

    /**
     * Sets a temporary master password
     *
     * @param string $password
     *
     * @throws ContextException
     */
    public function setTemporaryMasterPass(string $password)
    {
        $this->setTrasientKey('_tempmasterpass', $password);
    }

    /**
     * @param string $pluginName
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function setPluginKey(string $pluginName, string $key, $value)
    {
        $ctxKey = $this->getContextKey('plugins');

        $ctxKey[$pluginName][$key] = $value;

        return $value;
    }

    /**
     * @param string $pluginName
     * @param string $key
     *
     * @return mixed
     */
    public function getPluginKey(string $pluginName, string $key)
    {
        $ctxKey = $this->getContextKey('plugins');

        if (isset($ctxKey[$pluginName][$key])) {
            return $ctxKey[$pluginName][$key];
        }

        return null;
    }
}