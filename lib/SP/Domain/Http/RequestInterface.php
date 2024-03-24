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

namespace SP\Domain\Http;

use Klein\Request;
use SP\Domain\Core\Exceptions\SPException;

/**
 * Clase Request para la gestión de peticiones HTTP
 *
 * @package SP
 */
interface RequestInterface
{
    public function getClientAddress(bool $fullForwarded = false): string;

    /**
     * @return string[]|null
     */
    public function getForwardedFor(): ?array;

    /**
     * Comprobar si se realiza una recarga de la página
     */
    public function checkReload(): bool;

    public function analyzeEmail(string $param, ?string $default = null): ?string;

    /**
     * Analizar un valor encriptado y devolverlo desencriptado
     */
    public function analyzeEncrypted(string $param): string;

    public function analyzeString(string $param, ?string $default = null): ?string;

    public function analyzeUnsafeString(string $param, ?string $default = null): ?string;

    /**
     * @param string $param
     * @param callable|null $mapper
     * @param mixed $default
     *
     * @return array|null
     */
    public function analyzeArray(string $param, ?callable $mapper = null, mixed $default = null): ?array;

    /**
     * Comprobar si la petición es en formato JSON
     */
    public function isJson(): bool;

    /**
     * Comprobar si la petición es Ajax
     */
    public function isAjax(): bool;

    public function analyzeInt(string $param, ?int $default = null): ?int;

    public function getFile(string $file): ?array;

    public function analyzeBool(string $param, ?bool $default = null): bool;

    /**
     * @param string $key
     * @param string|null $param Checks the signature only for the given param
     *
     * @throws SPException
     */
    public function verifySignature(string $key, ?string $param = null): void;

    /**
     * Returns the URI used by the browser and checks for the protocol used
     *
     * @see https://tools.ietf.org/html/rfc7239#section-7.5
     */
    public function getHttpHost(): string;

    /**
     * Devolver datos de forward RFC 7239
     *
     * @see https://tools.ietf.org/html/rfc7239#section-7.5
     */
    public function getForwardedData(): ?array;

    public function getHeader(string $header): string;

    /**
     * Devolver datos de x-forward
     */
    public function getXForwardedData(): ?array;

    public function getMethod(): Method;

    public function isHttps(): ?bool;

    public function getServerPort(): int;

    public function getRequest(): Request;

    public function getServer(string $key): string;
}
