<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mvc\Controller;

use Closure;
use SP\Bootstrap;
use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\Http\Json;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Http\Uri;
use SP\Util\Util;


/**
 * Trait ControllerTrait
 *
 * @package SP\Mvc\Controller
 * @property ConfigData $configData
 */
trait ControllerTrait
{
    /**
     * @return string
     */
    protected function getControllerName()
    {
        $class = static::class;

        return substr($class, strrpos($class, '\\') + 1, -strlen('Controller')) ?: '';
    }

    /**
     * Logout from current session
     *
     * @param Request    $request
     * @param ConfigData $configData
     * @param Closure    $onRedirect
     */
    protected function sessionLogout(Request $request, ConfigData $configData, Closure $onRedirect)
    {
        if ($request->isJson()) {
            $jsonResponse = new JsonResponse(__u('Session not started or timed out'));
            $jsonResponse->setStatus(10);

            Json::fromDic()->returnJson($jsonResponse);
        } elseif ($request->isAjax()) {
            Util::logout();
        } else {
            try {
                // Analyzes if there is any direct route within the URL
                // then it computes the route HMAC to build a signed URI
                // which would be used during logging in
                $route = $request->analyzeString('r');
                $hash = $request->analyzeString('h');

                $uri = new Uri(Bootstrap::$WEBROOT . Bootstrap::$SUBURI);
                $uri->addParam('_r', 'login');

                if ($route && $hash) {
                    $key = $configData->getPasswordSalt();
                    $request->verifySignature($key);

                    $uri->addParam('from', $route);

                    $onRedirect->call($this, $uri->getUriSigned($key));
                } else {
                    $onRedirect->call($this, $uri->getUri());
                }
            } catch (SPException $e) {
                processException($e);
            }
        }
    }

    /**
     * @param string  $previousToken
     * @param Request $request
     *
     * @throws SPException
     */
    protected function checkSecurityToken($previousToken, Request $request)
    {
        if ($request->analyzeString('h') !== null
            && $request->analyzeString('from') === null
            && isset($this->configData)
        ) {
            $request->verifySignature($this->configData->getPasswordSalt());
        } else {
            $sk = $request->analyzeString('sk');

            if (!$sk || $previousToken !== $sk) {
                throw new SPException(
                    __u('Invalid Action'),
                    SPException::ERROR,
                    null,
                    1
                );
            }
        }
    }

    /**
     * Acción no disponible
     */
    protected function invalidAction()
    {
        Json::fromDic()->returnJson(new JsonResponse(__u('Invalid Action')));
    }
}