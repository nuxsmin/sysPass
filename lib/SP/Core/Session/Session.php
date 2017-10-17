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

namespace SP\Core\Session;

use SP\Account\AccountAcl;
use SP\Account\AccountSearch;
use SP\Config\ConfigData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Mgmt\Users\UserPreferences;

/**
 * Class Session
 *
 * @package SP\Core\Session
 */
class Session
{
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
    protected function getSessionKey($key, $default = '')
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
     */
    protected function setSessionKey($key, $value)
    {
        $_SESSION[$key] = $value;
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
     * @param UserData $UserData
     */
    public function setUserData(UserData $UserData = null)
    {
        $this->setSessionKey('userData', $UserData);
    }

    /**
     * Obtiene el objeto de perfil de usuario de la sesión.
     *
     * @return ProfileData
     */
    public function getUserProfile()
    {
        return $this->getSessionKey('usrprofile');
    }

    /**
     * Establece el objeto de perfil de usuario en la sesión.
     *
     * @param ProfileData $ProfileData
     */
    public function setUserProfile(ProfileData $ProfileData)
    {
        $this->setSessionKey('usrprofile', $ProfileData);
    }

    /**
     * @return AccountSearch
     */
    public function getSearchFilters()
    {
        return $this->getSessionKey('searchFilters', new AccountSearch());
    }

    /**
     * @param AccountSearch $searchFilters
     */
    public function setSearchFilters(AccountSearch $searchFilters)
    {
        $this->setSessionKey('searchFilters', $searchFilters);
    }

    /**
     * Establece el objeto de preferencias de usuario en la sesión.
     *
     * @param UserPreferencesData|UserPreferences $preferences
     */
    public function setUserPreferences(UserPreferencesData $preferences)
    {
        $this->setSessionKey('userpreferences', $preferences);
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

    /**
     * Returns if user is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->getUserData()->getUserLogin()
            && is_object($this->getUserPreferences());
    }

    /**
     * Devuelve los datos del usuario en la sesión.
     *
     * @return UserData
     */
    public function getUserData()
    {
        return $this->getSessionKey('userData', new UserData());
    }

    /**
     * Obtiene el objeto de preferencias de usuario de la sesión.
     *
     * @return UserPreferencesData
     */
    public function getUserPreferences()
    {
        return $this->getSessionKey('userpreferences');
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
}