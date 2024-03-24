<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Core\Crypt\VaultInterface;

/**
 * Class Session
 */
interface SessionContextInterface extends ContextInterface
{
    /**
     * Devuelve el tema visual utilizado en sysPass
     *
     * @return string
     */
    public function getTheme(): string;

    /**
     * Establece el tema visual utilizado en sysPass
     *
     * @param $theme string El tema visual a utilizar
     */
    public function setTheme(string $theme);

    /**
     * @return AccountSearchFilterDto|null
     */
    public function getSearchFilters(): ?AccountSearchFilterDto;

    /**
     * @param \SP\Domain\Account\Dtos\AccountSearchFilterDto $searchFilters
     */
    public function setSearchFilters(AccountSearchFilterDto $searchFilters): void;

    public function resetAccountAcl();

    /**
     * Establecer si el usuario está completamente autorizado
     */
    public function setAuthCompleted(bool $bool): void;

    /**
     * Devolver si el usuario está completamente logeado
     */
    public function getAuthCompleted();

    /**
     * Devolver la clave maestra temporal
     *
     * @return ?string
     */
    public function getTemporaryMasterPass(): ?string;

    /**
     * Devolver la clave pública
     *
     * @return string|null
     */
    public function getPublicKey(): ?string;

    /**
     * Establecer la clave pública
     */
    public function setPublicKey(string $key): void;

    /**
     * Devuelve el timeout de la sesión
     *
     * @return int|null El valor en segundos
     */
    public function getSessionTimeout(): ?int;

    /**
     * Establecer el timeout de la sesión
     *
     * @param int $timeout El valor en segundos
     *
     * @return int
     */
    public function setSessionTimeout(int $timeout): int;

    /**
     * Devuelve la hora de la última actividad
     *
     * @return int
     */
    public function getLastActivity(): int;

    /**
     * Establece la hora de la última actividad
     *
     * @param $time int La marca de hora
     */
    public function setLastActivity(int $time): void;

    /**
     * Devuelve la hora de inicio de actividad.
     *
     * @return int
     */
    public function getStartActivity(): int;

    /**
     * Devolver el color asociado a una cuenta
     *
     * @return string
     */
    public function getAccountColor(): string;

    /**
     * Establece el color asociado a una cuenta
     *
     * @param array $color
     */
    public function setAccountColor(array $color): void;

    /**
     * Return the CSRF key
     *
     * @return string|null
     */
    public function getCSRF(): ?string;

    /**
     * Set the CSRF key
     *
     * @param string $csrf
     */
    public function setCSRF(string $csrf): void;

    /**
     * Devuelve la clave maestra encriptada
     *
     * @return VaultInterface|null
     */
    public function getVault(): ?VaultInterface;

    /**
     * Establecer la clave maestra encriptada
     *
     * @param VaultInterface $vault
     */
    public function setVault(VaultInterface $vault): void;

    /**
     * Devuelve la hora en la que el SID de sesión fue creado
     *
     * @return int
     */
    public function getSidStartTime(): int;

    /**
     * Establece la hora de creación del SID
     *
     * @param $time int La marca de hora
     *
     * @return int
     */
    public function setSidStartTime(int $time): int;

    /**
     * Establece la hora de inicio de actividad
     *
     * @param $time int La marca de hora
     *
     * @return int
     */
    public function setStartActivity(int $time): int;
}
