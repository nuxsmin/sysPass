<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Api;

/**
 * Class ApiResponse
 * @package SP\Api
 */
class ApiResponse
{
    const RESULT_SUCCESS = 0;
    const RESULT_ERROR = 1;

    /**
     * @var mixed
     */
    private $result;
    /**
     * @var int
     */
    private $resultCode;
    /**
     * @var null
     */
    private $itemId;

    /**
     * ApiResponse constructor.
     * @param mixed $result
     * @param int $resultCode
     * @param null $itemId
     */
    public function __construct($result, $resultCode = self::RESULT_SUCCESS, $itemId = null)
    {
        $this->result = $result;
        $this->resultCode = $resultCode;
        $this->itemId = $itemId;
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        if ($this->itemId) {
            return [
                'itemId' => $this->itemId,
                'result' => $this->result,
                'resultCode' => $this->resultCode
            ];
        }

        return [
            'result' => $this->result,
            'resultCode' => $this->resultCode
        ];
    }
}