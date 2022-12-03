<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Context;

use SP\Core\Crypt\Vault;
use SP\DataModel\ProfileData;
use SP\Domain\Account\Search\AccountSearchFilter;
use SP\Domain\User\Services\UserLoginResponse;
use function SP\logger;
use function SP\processException;

/**
 * Class Session
 *
 * @package SP\Core\Session
 */
class SessionContext extends ContextBase
{
    public const MAX_SID_TIME = 120;

    private static bool $isReset  = false;
    private static bool $isLocked = false;

    /**
     * Closes session
     */
    public static function close(): void
    {
        if (!self::$isLocked) {
            self::$isLocked = session_write_close();

            logger(sprintf('Session close value=%s caller=%s', self::$isLocked, getLastCaller()));
        }
    }

    /**
     * Destruir la sesión y reiniciar
     */
    public static function restart(): void
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
    public function getTheme(): string
    {
        return $this->getContextKey('theme');
    }

    /**
     * Devolver una variable de sesión
     *
     * @param  string  $key
     * @param  mixed  $default
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
    public function setTheme(string $theme)
    {
        $this->setContextKey('theme', $theme);
    }

    /**
     * Establecer una variable de sesión
     *
     * @param  string  $key  El nombre de la variable
     * @param  mixed  $value  El valor de la variable
     *
     * @return mixed
     */
    protected function setContextKey(string $key, mixed $value): mixed
    {
        try {
            if (self::$isLocked) {
                logger('Session locked; key='.$key);
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
     * @param  int  $time
     */
    public function setConfigTime(int $time): void
    {
        $this->setContextKey('configTime', $time);
    }

    /**
     * Devolver la hora de carga de la configuración
     *
     * @return int
     */
    public function getConfigTime(): int
    {
        return (int)$this->getContextKey('configTime');
    }

    /**
     * Establece los datos del usuario en la sesión.
     *
     * @param  UserLoginResponse|null  $userLoginResponse
     */
    public function setUserData(UserLoginResponse $userLoginResponse = null): void
    {
        $this->setContextKey('userData', $userLoginResponse);
    }

    /**
     * Obtiene el objeto de perfil de usuario de la sesión.
     *
     * @return ProfileData|null
     */
    public function getUserProfile(): ?ProfileData
    {
        return $this->getContextKey('userProfile');
    }

    /**
     * Establece el objeto de perfil de usuario en la sesión.
     *
     * @param  ProfileData  $ProfileData
     */
    public function setUserProfile(ProfileData $ProfileData): void
    {
        $this->setContextKey('userProfile', $ProfileData);
    }

    /**
     * @return AccountSearchFilter|null
     */
    public function getSearchFilters(): ?AccountSearchFilter
    {
        return $this->getContextKey('searchFilters');
    }

    /**
     * @param  \SP\Domain\Account\Search\AccountSearchFilter  $searchFilters
     */
    public function setSearchFilters(AccountSearchFilter $searchFilters): void
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
    public function isLoggedIn(): bool
    {
        return self::$isReset === false && $this->getUserData()->getLogin()
               && is_object($this->getUserData()->getPreferences());
    }

    /**
     * Devuelve los datos del usuario en la sesión.
     *
     * @return UserLoginResponse
     */
    public function getUserData(): UserLoginResponse
    {
        return $this->getContextKey('userData', new UserLoginResponse());
    }

    /**
     * Establecer si el usuario está completamente autorizado
     *
     * @param $bool
     */
    public function setAuthCompleted($bool): void
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
     * @return ?string
     */
    public function getTemporaryMasterPass(): ?string
    {
        return $this->getContextKey('tempmasterpass');
    }

    /**
     * Sets a temporary master password
     *
     * @param  string  $password
     */
    public function setTemporaryMasterPass(string $password): void
    {
        $this->setContextKey('tempmasterpass', $password);
    }

    /**
     * Devolver la clave pública
     *
     * @return string|null
     */
    public function getPublicKey(): ?string
    {
        return $this->getContextKey('pubkey');
    }

    /**
     * Establecer la clave pública
     *
     * @param $key
     */
    public function setPublicKey($key): void
    {
        $this->setContextKey('pubkey', $key);
    }

    /**
     * Devuelve el timeout de la sesión
     *
     * @return int|null El valor en segundos
     */
    public function getSessionTimeout(): ?int
    {
        return $this->getContextKey('sessionTimeout');
    }

    /**
     * Establecer el timeout de la sesión
     *
     * @param  int  $timeout  El valor en segundos
     *
     * @return int
     */
    public function setSessionTimeout(int $timeout): int
    {
        $this->setContextKey('sessionTimeout', $timeout);

        return $timeout;
    }

    /**
     * Devuelve la hora de la última actividad
     *
     * @return int
     */
    public function getLastActivity(): int
    {
        return $this->getContextKey('lastActivity', 0);
    }

    /**
     * Establece la hora de la última actividad
     *
     * @param $time int La marca de hora
     */
    public function setLastActivity(int $time): void
    {
        $this->setContextKey('lastActivity', $time);
    }

    /**
     * Devuelve la hora de inicio de actividad.
     *
     * @return int
     */
    public function getStartActivity(): int
    {
        return $this->getContextKey('startActivity', 0);
    }

    /**
     * Establecer el lenguaje de la sesión
     */
    public function setLocale(string $locale): void
    {
        $this->setContextKey('locale', $locale);
    }

    /**
     * Devuelve el lenguaje de la sesión
     *
     * @return string|null
     */
    public function getLocale(): ?string
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
     * @param  array  $color
     */
    public function setAccountColor(array $color): void
    {
        $this->setContextKey('accountcolor', $color);
    }

    /**
     * Devuelve el estado de la aplicación
     *
     * @return bool|null
     */
    public function getAppStatus(): ?bool
    {
        return $this->getContextKey('status');
    }

    /**
     * Establecer el estado de la aplicación
     *
     * @param  string  $status
     */
    public function setAppStatus(string $status): void
    {
        $this->setContextKey('status', $status);
    }

    /**
     * Return the CSRF key
     *
     * @return string|null
     */
    public function getCSRF(): ?string
    {
        return $this->getContextKey('csrf');
    }

    /**
     * Set the CSRF key
     *
     * @param  string  $csrf
     */
    public function setCSRF(string $csrf): void
    {
        $this->setContextKey('csrf', $csrf);
    }

    /**
     * Reset del estado de la aplicación
     *
     * @return bool|null
     */
    public function resetAppStatus(): ?bool
    {
        return $this->setContextKey('status', null);
    }

    /**
     * Devuelve la clave maestra encriptada
     *
     * @return Vault|null
     */
    public function getVault(): ?Vault
    {
        return $this->getContextKey('vault');
    }

    /**
     * Establecer la clave maestra encriptada
     *
     * @param  Vault  $vault
     */
    public function setVault(Vault $vault): void
    {
        $this->setContextKey('vault', $vault);
    }

    /**
     * Establece la cache de cuentas
     *
     * @param  array  $accountsCache
     */
    public function setAccountsCache(array $accountsCache): void
    {
        $this->setContextKey('accountsCache', $accountsCache);
    }

    /**
     * Devuelve la cache de cuentas
     *
     * @return \SP\Domain\Account\Dtos\AccountCacheDto[]|null
     */
    public function getAccountsCache(): ?array
    {
        return $this->getContextKey('accountsCache');
    }

    /**
     * @throws ContextException
     */
    public function initialize(): void
    {
        // Si la sesión no puede ser iniciada, devolver un error 500
        if (headers_sent($filename, $line)
            || @session_start() === false) {

            logger(sprintf('Headers sent in %s:%d file', $filename, $line));

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
    public function getSidStartTime(): int
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
    public function setSidStartTime(int $time): int
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
    public function setStartActivity(int $time): int
    {
        $this->setContextKey('startActivity', $time);

        return $time;
    }

    /**
     * @param  string  $pluginName
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return mixed
     */
    public function setPluginKey(string $pluginName, string $key, $value)
    {
        /** @var ContextCollection $ctxKey */
        $ctxKey = $this->getContextKey($pluginName, new ContextCollection());

        $this->setContextKey($pluginName, $ctxKey->set($key, $value));

        return $value;
    }

    /**
     * @param  string  $pluginName
     * @param  string  $key
     *
     * @return mixed
     */
    public function getPluginKey(string $pluginName, string $key): mixed
    {
        /** @var ContextCollection $ctxKey */
        $ctxKey = $this->getContextKey($pluginName);

        if ($ctxKey !== null) {
            return $ctxKey->get($key);
        }

        return null;
    }
}
