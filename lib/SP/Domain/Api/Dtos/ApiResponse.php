<?php
declare(strict_types=1);
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

namespace SP\Domain\Api\Dtos;

/**
 * Class ApiResponse
 */
final class ApiResponse
{
    private const RESULT_SUCCESS = 0;
    private const RESULT_ERROR   = 1;

    /**
     * ApiResponse constructor.
     *
     * @param mixed $result
     * @param int $resultCode
     * @param string|null $resultMessage
     * @param int|null $itemId
     */
    public function __construct(
        private readonly mixed   $result,
        private readonly int     $resultCode,
        private readonly ?string $resultMessage = null,
        private readonly ?int    $itemId = null
    ) {
    }

    /**
     * @param mixed $result
     * @param int|null $itemId
     * @param string|null $message
     *
     * @return ApiResponse
     */
    public static function makeSuccess(
        mixed $result,
        string $message = null,
        ?int  $itemId = null,
    ): ApiResponse {
        return new self($result, self::RESULT_SUCCESS, $message, $itemId);
    }

    /**
     * @param mixed $result
     * @param string|null $message
     *
     * @return ApiResponse
     */
    public static function makeError(
        mixed $result,
        ?string $message = null
    ): ApiResponse {
        return new self($result, self::RESULT_ERROR, $message);
    }

    /**
     * @return array
     */
    public function getResponse(): array
    {
        return [
            'itemId' => $this->itemId,
            'result' => $this->result,
            'resultCode' => $this->resultCode,
            'resultMessage' => $this->resultMessage,
            'count' => is_array($this->result) ? count($this->result) : null,
        ];
    }
}
