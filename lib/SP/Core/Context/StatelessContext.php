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

namespace SP\Core\Context;

use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Models\ProfileData;

use function SP\processException;

/**
 * Class ApiContext
 *
 * @package SP\Core\Context
 */
class StatelessContext extends ContextBase
{
    /**
     * Establece los datos del usuario en la sesión.
     */
    public function setUserData(?UserDataDto $userDataDto = null): void
    {
        $this->setContextKey('userData', $userDataDto);
    }

    /**
     * Set a context variable and its value
     */
    protected function setContextKey(string $key, mixed $value): mixed
    {
        try {
            return parent::setContextKey($key, $value);
        } catch (ContextException $e) {
            processException($e);
        }

        return null;
    }

    /**
     * Obtiene el objeto de perfil de usuario de la sesión.
     */
    public function getUserProfile(): ?ProfileData
    {
        return $this->getContextKey('userProfile');
    }

    /**
     * Return a context variable's value
     */
    protected function getContextKey(string $key, mixed $default = null): mixed
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
     */
    public function setUserProfile(ProfileData $profileData): void
    {
        $this->setContextKey('userProfile', $profileData);
    }

    /**
     * Returns if user is logged in
     */
    public function isLoggedIn(): bool
    {
        return !empty($this->getUserData()->getLogin());
    }

    /**
     * Devuelve los datos del usuario en la sesión.
     */
    public function getUserData(): UserDataDto
    {
        return $this->getContextKey('userData', new UserDataDto());
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
     */
    public function getLocale(): ?string
    {
        return $this->getContextKey('locale');
    }

    /**
     * Devuelve el estado de la aplicación
     */
    public function getAppStatus(): ?string
    {
        return $this->getContextKey('status');
    }

    /**
     * Establecer el estado de la aplicación
     */
    public function setAppStatus(string $status): void
    {
        $this->setContextKey('status', $status);
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
     * @throws ContextException
     */
    public function initialize(): void
    {
        $this->setContext(new ContextCollection());
    }

    /**
     * Establecer la hora de carga de la configuración
     */
    public function setConfigTime(int $time): void
    {
        $this->setContextKey('configTime', $time);
    }

    /**
     * Devolver la hora de carga de la configuración
     */
    public function getConfigTime(): int
    {
        return $this->getContextKey('configTime');
    }

    public function getAccountsCache(): ?array
    {
        return $this->getContextKey('accountsCache');
    }

    /**
     * Sets a temporary master password
     *
     * @throws ContextException
     */
    public function setTemporaryMasterPass(string $password): void
    {
        $this->setTrasientKey('_tempmasterpass', $password);
    }

    public function setPluginKey(string $pluginName, string $key, mixed $value): mixed
    {
        $ctxKey = $this->getContextKey('plugins');

        $ctxKey[$pluginName][$key] = $value;

        return $value;
    }

    public function getPluginKey(string $pluginName, string $key): mixed
    {
        $ctxKey = $this->getContextKey('plugins');

        return $ctxKey[$pluginName][$key] ?? null;
    }

    public function setAccountsCache(array $accountsCache): void
    {
        $this->setContextKey('accountsCache', $accountsCache);
    }
}
