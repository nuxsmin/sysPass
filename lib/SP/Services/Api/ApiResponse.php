<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Api;

/**
 * Class ApiResponse
 *
 * @package SP\Api
 */
final class ApiResponse
{
    private const RESULT_SUCCESS = 0;
    private const RESULT_ERROR = 1;

    private $result;
    private ?int $resultCode = null;
    private int $itemId;
    private ?string $resultMessage = null;

    /**
     * ApiResponse constructor.
     *
     * @param mixed $result
     * @param null  $itemId
     */
    public function __construct($result, $itemId = null)
    {
        $this->result = $result;
        $this->itemId = (int)$itemId;
    }

    /**
     * @param mixed       $result
     * @param int|null    $itemId
     * @param string|null $message
     *
     * @return ApiResponse
     */
    public static function makeSuccess(
        $result,
        ?int $itemId = null,
        string $message = null
    ): ApiResponse
    {
        $out = new self($result, $itemId);
        $out->resultCode = self::RESULT_SUCCESS;
        $out->resultMessage = $message;

        return $out;
    }

    /**
     * @param mixed       $result
     * @param string|null $message
     *
     * @return ApiResponse
     */
    public static function makeError(
        $result,
        ?string $message = null
    ): ApiResponse
    {
        $out = new self($result);
        $out->resultCode = self::RESULT_ERROR;
        $out->resultMessage = $message;

        return $out;
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
            'count' => is_array($this->result) ? count($this->result) : null
        ];
    }
}