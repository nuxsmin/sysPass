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

namespace SP\Modules\Api\Controllers;

use DI\Container;
use Klein\Klein;
use SP\Api\ApiResponse;
use SP\Api\JsonRpcResponse;
use SP\Core\Context\StatelessContext;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\SPException;
use SP\Services\Api\ApiService;

/**
 * Class ControllerBase
 *
 * @package SP\Modules\Api\Controllers
 */
abstract class ControllerBase
{
    /**
     * @var Container
     */
    protected $dic;
    /**
     * @var string
     */
    protected $controllerName;
    /**
     * @var
     */
    protected $actionName;
    /**
     * @var StatelessContext
     */
    protected $context;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var ApiService
     */
    protected $apiService;
    /**
     * @var Klein
     */
    protected $router;

    /**
     * Constructor
     *
     * @param Container $container
     * @param string    $actionName
     * @param mixed     $requesData
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public final function __construct(Container $container, $actionName, $requesData)
    {
        $this->dic = $container;
        $this->context = $container->get(StatelessContext::class);
        $this->eventDispatcher = $container->get(EventDispatcher::class);
        $this->router = $container->get(Klein::class);

        $this->apiService = $container->get(ApiService::class);
        $this->apiService->setRequestData($requesData);

        $this->controllerName = $this->getControllerName();
        $this->actionName = $actionName;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * @return string
     */
    protected function getControllerName()
    {
        $class = static::class;

        return substr($class, strrpos($class, '\\') + 1, -strlen('Controller')) ?: '';
    }

    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * {"jsonrpc": "2.0", "result": 19, "id": 3}
     *
     * @param ApiResponse $apiResponse
     * @param int         $id
     * @return string La cadena en formato JSON
     */
    protected function returnResponse(ApiResponse $apiResponse, $id = 0)
    {
        $this->router->response()->headers()->set('Content-type', 'application/json; charset=utf-8');

        try {
            exit(JsonRpcResponse::getResponse($apiResponse, $id));
        } catch (SPException $e) {
            processException($e);

            exit(JsonRpcResponse::getResponseException($e, $id));
        }
    }

    /**
     * @param \Exception $e
     * @param int        $id
     * @return string
     */
    protected function returnResponseException(\Exception $e, $id = 0)
    {
        $this->router->response()->headers()->set('Content-type', 'application/json; charset=utf-8');

        exit(JsonRpcResponse::getResponseException($e, $id));
    }
}