<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Core\Context;

use SP\Core\Context\ContextException;
use SP\DataModel\ProfileData;
use SP\Domain\Account\Dtos\AccountCacheDto;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Services\UserData;

/**
 * Class ContextInterface
 *
 * @package SP\Core\Session
 */
interface ContextInterface
{
    public const MASTER_PASSWORD_KEY = '_masterpass';

    /**
     * @throws ContextException
     */
    public function initialize();

    public function isInitialized(): bool;

    /**
     * Establecer la hora de carga de la configuración
     */
    public function setConfigTime(int $time);

    /**
     * Devolver la hora de carga de la configuración
     */
    public function getConfigTime(): int;

    /**
     * Establece los datos del usuario en la sesión.
     */
    public function setUserData(?UserDataDto $userDataDto = null);

    /**
     * Obtiene el objeto de perfil de usuario de la sesión.
     */
    public function getUserProfile(): ?ProfileData;

    /**
     * Establece el objeto de perfil de usuario en la sesión.
     */
    public function setUserProfile(ProfileData $profileData);

    /**
     * Returns if user is logged in
     */
    public function isLoggedIn(): bool;

    /**
     * Devuelve los datos del usuario en la sesión.
     */
    public function getUserData(): UserDataDto;

    /**
     * Establecer el lenguaje de la sesión
     */
    public function setLocale(string $locale);

    /**
     * Devuelve el lenguaje de la sesión
     */
    public function getLocale(): ?string;

    /**
     * Devuelve el estado de la aplicación
     */
    public function getAppStatus(): ?string;

    /**
     * Establecer el estado de la aplicación
     */
    public function setAppStatus(string $status);

    /**
     * Reset del estado de la aplicación
     */
    public function resetAppStatus(): ?bool;

    /**
     * @return AccountCacheDto[]|null
     */
    public function getAccountsCache(): ?array;

    /**
     * Establece la cache de cuentas
     *
     * @param  array  $accountsCache
     */
    public function setAccountsCache(array $accountsCache): void;

    /**
     * Sets an arbitrary key in the trasient collection.
     * This key is not bound to any known method or type
     *
     * @param  string  $key
     * @param  mixed  $value
     *
     * @throws ContextException
     */
    public function setTrasientKey(string $key, mixed $value);

    /**
     * Gets an arbitrary key from the trasient collection.
     * This key is not bound to any known method or type
     *
     * @param  string  $key
     * @param  mixed|null  $default
     *
     * @return mixed
     */
    public function getTrasientKey(string $key, mixed $default = null): mixed;

    /**
     * Sets a temporary master password
     */
    public function setTemporaryMasterPass(string $password);

    /**
     * @param  string  $pluginName
     * @param  string  $key
     * @param  mixed  $value
     */
    public function setPluginKey(string $pluginName, string $key, mixed $value);

    public function getPluginKey(string $pluginName, string $key): mixed;
}
