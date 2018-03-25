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

namespace SP\Mvc\Controller;

use Klein\Klein;
use SP\Core\Context\ContextInterface;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Util;


/**
 * Trait ControllerTrait
 *
 * @package SP\Mvc\Controller
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
     * Comprobar si la sesión está activa
     *
     * @param ContextInterface $context
     * @param Klein            $router
     */
    protected function checkLoggedInSession(ContextInterface $context, Klein $router)
    {
        if (!$context->isLoggedIn()) {
            if (Checks::isJson($router)) {
                $JsonResponse = new JsonResponse();
                $JsonResponse->setDescription(__u('La sesión no se ha iniciado o ha caducado'));
                $JsonResponse->setStatus(10);
                Json::returnJson($JsonResponse);
            } elseif (Checks::isAjax($router)) {
                Util::logout();
            } else {
                $route = Request::analyzeString('r');
                $hash = Request::analyzeString('h');

                if ($route && $hash) {
                    $redirect = 'index.php?r=login&from=' . urlencode($route) . '&h=' . $hash;
                } else {
                    $redirect = 'index.php?r=login';
                }

                $router->response()
                    ->redirect($redirect)
                    ->send(true);
            }
        }
    }

    /**
     * @param ContextInterface $context
     */
    protected function checkSecurityToken(ContextInterface $context)
    {
        $sk = Request::analyzeString('sk');
        $sessionKey = $context->getSecurityKey();

        if (!$sk || (null !== $sessionKey && $sessionKey !== $sk)) {
            $this->invalidAction();
        }
    }

    /**
     * Acción no disponible
     */
    protected function invalidAction()
    {
        Json::returnJson((new JsonResponse())->setDescription(__u('Acción Inválida')));
    }
}