<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Context\SessionContext;
use SP\Core\Exceptions\SPException;
use SP\Http\Json;
use SP\Http\JsonResponse;

/**
 * Trait JsonTrait
 *
 * @package SP\Modules\Web\Controllers\Traits
 * @property SessionContext $session
 */
trait JsonTrait
{
    /**
     * Returns JSON response
     *
     * @param int        $status      Status code
     * @param string     $description Untranslated description string
     * @param array|null $messages    Untranslated massages array of strings
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function returnJsonResponse(
        int $status,
        string $description,
        array $messages = null): bool
    {
        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus($status);
        $jsonResponse->setDescription($description);

        if (null !== $messages) {
            $jsonResponse->setMessages($messages);
        }

        return Json::fromDic()->returnJson($jsonResponse);
    }

    /**
     * Returns JSON response
     *
     * @param mixed      $data
     * @param int        $status      Status code
     * @param null       $description Untranslated description string
     * @param array|null $messages
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function returnJsonResponseData(
        $data,
        $status = JsonResponse::JSON_SUCCESS,
        $description = null,
        array $messages = null): bool
    {
        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus($status);
        $jsonResponse->setData($data);

        if (null !== $description) {
            $jsonResponse->setDescription($description);
        }

        if (null !== $messages) {
            $jsonResponse->setMessages($messages);
        }

        return Json::fromDic()->returnJson($jsonResponse);
    }

    /**
     * Returns JSON response
     *
     * @param Exception $exception
     * @param int       $status Status code
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function returnJsonResponseException(
        Exception $exception,
        int $status = JsonResponse::JSON_ERROR): bool
    {
        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus($status);
        $jsonResponse->setDescription($exception->getMessage());

        if ($exception instanceof SPException && $exception->getHint() !== null) {
            $jsonResponse->setMessages([$exception->getHint()]);
        }

        return Json::fromDic()->returnJson($jsonResponse);
    }
}