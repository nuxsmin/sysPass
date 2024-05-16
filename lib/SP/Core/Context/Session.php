<?php

declare(strict_types=1);
/**
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

use Exception;
use SessionHandlerInterface;
use SP\Domain\Account\Dtos\AccountCacheDto;
use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Models\ProfileData;

use function SP\getLastCaller;
use function SP\logger;
use function SP\processException;

/**
 * Class Session
 */
class Session extends ContextBase implements SessionContext
{
    public function __construct(?SessionHandlerInterface $sessionHandler = null)
    {
        parent::__construct();

        if ($sessionHandler) {
            session_set_save_handler($sessionHandler);
        }
    }

    /**
     * Closes session
     */
    public static function close(): void
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_commit();

            logger(sprintf('Session close: caller=%s', getLastCaller()));
        }
    }

    /**
     * Devuelve el tema visual utilizado en sysPass
     */
    public function getTheme(): string
    {
        return $this->getContextKey('theme');
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
     * Establece el tema visual utilizado en sysPass
     */
    public function setTheme(string $theme): void
    {
        $this->setContextKey('theme', $theme);
    }


    /**
     * Set a context variable and its value
     *
     * @template T
     * @param T $value
     * @return T
     */
    protected function setContextKey(string $key, mixed $value): mixed
    {
        try {
            if (PHP_SESSION_ACTIVE !== session_status()) {
                logger('Session locked; key=' . $key);
            }

            return parent::setContextKey($key, $value);
        } catch (ContextException $e) {
            processException($e);
        }

        return null;
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
        return (int)$this->getContextKey('configTime');
    }

    /**
     * Establece los datos del usuario en la sesión.
     */
    public function setUserData(?UserDataDto $userDataDto = null): void
    {
        $this->setContextKey('userData', $userDataDto);
    }

    /**
     * Obtiene el objeto de perfil de usuario de la sesión.
     */
    public function getUserProfile(): ?ProfileData
    {
        return $this->getContextKey('userProfile');
    }

    /**
     * Establece el objeto de perfil de usuario en la sesión.
     */
    public function setUserProfile(ProfileData $profileData): void
    {
        $this->setContextKey('userProfile', $profileData);
    }

    public function getSearchFilters(): ?AccountSearchFilterDto
    {
        return $this->getContextKey('searchFilters');
    }

    public function setSearchFilters(AccountSearchFilterDto $searchFilters): void
    {
        $this->setContextKey('searchFilters', $searchFilters);
    }

    public function resetAccountAcl(): void
    {
        $this->setContextKey('accountAcl', null);
    }

    /**
     * Returns whether the user is logged in
     */
    public function isLoggedIn(): bool
    {
        return $this->getUserData()->getLogin() && $this->getUserData()->getPreferences() !== null;
    }

    /**
     * Devuelve los datos del usuario en la sesión.
     */
    public function getUserData(): UserDataDto
    {
        return $this->getContextKey('userData', new UserDataDto());
    }

    /**
     * Establecer si el usuario está completamente autorizado
     */
    public function setAuthCompleted(bool $bool): void
    {
        $this->setContextKey('authCompleted', $bool);
    }

    /**
     * Devolver si el usuario está completamente logeado
     */
    public function getAuthCompleted(): bool
    {
        return (bool)$this->getContextKey('authCompleted', false);
    }

    /**
     * Devolver la clave maestra temporal
     */
    public function getTemporaryMasterPass(): ?string
    {
        return $this->getContextKey('tempmasterpass');
    }

    /**
     * Sets a temporary master password
     */
    public function setTemporaryMasterPass(string $password): void
    {
        $this->setContextKey('tempmasterpass', $password);
    }

    /**
     * Devolver la clave pública
     */
    public function getPublicKey(): ?string
    {
        return $this->getContextKey('pubkey');
    }

    /**
     * Establecer la clave pública
     */
    public function setPublicKey(string $key): void
    {
        $this->setContextKey('pubkey', $key);
    }

    /**
     * Devuelve el timeout de la sesión
     */
    public function getSessionTimeout(): ?int
    {
        return $this->getContextKey('sessionTimeout');
    }

    /**
     * Establecer el timeout de la sesión
     */
    public function setSessionTimeout(int $timeout): int
    {
        $this->setContextKey('sessionTimeout', $timeout);

        return $timeout;
    }

    /**
     * Devuelve la hora de la última actividad
     */
    public function getLastActivity(): int
    {
        return $this->getContextKey('lastActivity', 0);
    }

    /**
     * Establece la hora de la última actividad
     */
    public function setLastActivity(int $time): void
    {
        $this->setContextKey('lastActivity', $time);
    }

    /**
     * Devuelve la hora de inicio de actividad.
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
     */
    public function getLocale(): ?string
    {
        return $this->getContextKey('locale');
    }

    /**
     * Devolver el color asociado a una cuenta
     */
    public function getAccountColor(): string
    {
        return $this->getContextKey('accountcolor');
    }

    /**
     * Establece el color asociado a una cuenta
     */
    public function setAccountColor(array $color): void
    {
        $this->setContextKey('accountcolor', $color);
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
     * Return the CSRF key
     */
    public function getCSRF(): ?string
    {
        return $this->getContextKey('csrf');
    }

    /**
     * Set the CSRF key
     */
    public function setCSRF(string $csrf): void
    {
        $this->setContextKey('csrf', $csrf);
    }

    /**
     * Reset del estado de la aplicación
     */
    public function resetAppStatus(): ?bool
    {
        return $this->setContextKey('status', null);
    }

    /**
     * Devuelve la clave maestra encriptada
     */
    public function getVault(): ?VaultInterface
    {
        return $this->getContextKey('vault');
    }

    /**
     * Establecer la clave maestra encriptada
     */
    public function setVault(VaultInterface $vault): void
    {
        $this->setContextKey('vault', $vault);
    }

    /**
     * Establece la cache de cuentas
     */
    public function setAccountsCache(array $accountsCache): void
    {
        $this->setContextKey('accountsCache', $accountsCache);
    }

    /**
     * Devuelve la cache de cuentas
     *
     * @return AccountCacheDto[]|null
     */
    public function getAccountsCache(): ?array
    {
        return $this->getContextKey('accountsCache');
    }

    /**
     * @throws ContextException
     * @throws SPException
     */
    public function initialize(): void
    {
        try {
            SessionLifecycleHandler::start();
        } catch (Exception $e) {
            throw ContextException::from($e);
        }

        $this->setContextReference($_SESSION);

        if ($this->getSidStartTime() === 0) {
            $this->setSidStartTime(time());
            $this->setStartActivity(time());
        } elseif (SessionLifecycleHandler::needsRegenerate($this->getSidStartTime())) {
            SessionLifecycleHandler::regenerate();
        }
    }

    /**
     * Devuelve la hora en la que el SID de sesión fue creado
     */
    public function getSidStartTime(): int
    {
        return $this->getContextKey('sidStartTime', 0);
    }

    /**
     * Establece la hora de creación del SID
     */
    public function setSidStartTime(int $time): int
    {
        $this->setContextKey('sidStartTime', $time);

        return $time;
    }

    /**
     * Establece la hora de inicio de actividad
     */
    public function setStartActivity(int $time): int
    {
        $this->setContextKey('startActivity', $time);

        return $time;
    }

    public function setPluginKey(string $pluginName, string $key, mixed $value): mixed
    {
        /** @var ContextCollection $ctxKey */
        $ctxKey = $this->getContextKey($pluginName, new ContextCollection());
        $ctxKey->set($key, $value);

        $this->setContextKey($pluginName, $value);

        return $value;
    }

    public function getPluginKey(string $pluginName, string $key): mixed
    {
        /** @var ContextCollection $ctxKey */
        $ctxKey = $this->getContextKey($pluginName);

        return $ctxKey?->get($key);
    }
}
