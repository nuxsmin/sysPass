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

namespace SP\Domain\Api\Ports;


use Exception;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SP\Domain\Api\Services\ApiRequest;
use SP\Domain\Common\Services\ServiceException;

/**
 * Class ApiService
 *
 * @package SP\Domain\Common\Services\ApiService
 */
interface ApiServiceInterface
{
    /**
     * Sets up API
     *
     * @throws ServiceException
     * @throws SPException
     * @throws Exception
     */
    public function setup(int $actionId): void;

    /**
     * Devolver el valor de un parámetro
     *
     * @param  string  $param
     * @param  bool  $required  Si es requerido
     * @param  mixed  $default  Valor por defecto
     *
     * @return mixed
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function getParam(string $param, bool $required = false, $default = null);

    /**
     * Devuelve la ayuda para una acción
     *
     * @param  string  $action
     *
     * @return array
     */
    public function getHelp(string $action): array;

    /**
     * @throws ServiceException
     * @throws ContextException
     */
    public function requireMasterPass(): void;

    /**
     * @throws ServiceException
     */
    public function getParamInt(string $param, bool $required = false, $default = null): ?int;

    /**
     * @throws ServiceException
     */
    public function getParamString(string $param, bool $required = false, $default = null): ?string;

    /**
     * @throws ServiceException
     */
    public function getParamArray(string $param, bool $required = false, $default = null): ?array;

    /**
     * @throws ServiceException
     */
    public function getParamRaw(string $param, bool $required = false, $default = null): ?string;

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function getMasterPass(): string;

    public function setApiRequest(ApiRequest $apiRequest): ApiServiceInterface;

    public function getRequestId(): int;

    public function isInitialized(): bool;

    /**
     * @throws InvalidClassException
     */
    public function setHelpClass(string $helpClass): void;
}
