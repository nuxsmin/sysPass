<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Traits;

use SP\Http\JsonResponse;
use SP\Util\Json;

/**
 * Trait JsonTrait
 *
 * @package SP\Modules\Web\Controllers\Traits
 */
trait JsonTrait
{
    /**
     * Returns JSON response
     *
     * @param int        $status      Status code
     * @param string     $description Untranslated description string
     * @param array|null $messages    Untranslated massages array of strings
     */
    protected function returnJsonResponse($status, $description, array $messages = null)
    {
        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus($status);
        $jsonResponse->setDescription($description);

        if (null !== $messages) {
            $jsonResponse->setMessages($messages);
        }

        Json::returnJson($jsonResponse);
    }

    /**
     * Returns JSON response
     *
     * @param mixed $data
     * @param int   $status      Status code
     * @param null  $description Untranslated description string
     */
    protected function returnJsonResponseData($data, $status = 0, $description = null)
    {
        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus($status);
        $jsonResponse->setData($data);

        if (null !== $description) {
            $jsonResponse->setDescription($description);
        }


        Json::returnJson($jsonResponse);
    }
}