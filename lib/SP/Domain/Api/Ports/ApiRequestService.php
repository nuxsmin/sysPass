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

namespace SP\Domain\Api\Ports;

use SP\Domain\Api\Services\ApiRequestException;

/**
 * Class ApiRequest
 *
 * @package SP\Domain\Api\Services
 */
interface ApiRequestService
{
    public const PHP_REQUEST_STREAM = 'php://input';

    /**
     * Build the ApiRequest from the request itself.
     *
     * It will read the 'php://input' strean and get the contents into a JSON format
     *
     * @param string $stream
     *
     * @return ApiRequestService
     * @throws ApiRequestException
     */
    public static function buildFromRequest(string $stream = self::PHP_REQUEST_STREAM): ApiRequestService;

    /**
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @return int
     */
    public function getId(): int;
}
