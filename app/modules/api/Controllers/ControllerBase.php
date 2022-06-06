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

namespace SP\Modules\Api\Controllers;

use Exception;
use Klein\Klein;
use League\Fractal\Manager;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\SPException;
use SP\Domain\Api\ApiServiceInterface;
use SP\Domain\Api\Services\ApiResponse;
use SP\Domain\Api\Services\JsonRpcResponse;
use SP\Domain\Config\In\ConfigDataInterface;
use SP\Http\Json;

/**
 * Class ControllerBase
 *
 * @package SP\Modules\Api\Controllers
 */
abstract class ControllerBase
{
    protected const SEARCH_COUNT_ITEMS = 25;

    protected ContainerInterface  $dic;
    protected string              $controllerName;
    protected ContextInterface    $context;
    protected EventDispatcher     $eventDispatcher;
    protected ApiServiceInterface $apiService;
    protected Klein               $router;
    protected ConfigDataInterface $configData;
    protected Manager             $fractal;
    protected Acl                 $acl;
    private bool                  $isAuthenticated = false;

    public function __construct(
        Application $application,
        Klein $router,
        ApiServiceInterface $apiService,
        Acl $acl
    ) {
        $this->context = $application->getContext();
        $this->configData = $application->getConfig()->getConfigData();
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->router = $router;
        $this->apiService = $apiService;
        $this->acl = $acl;
        $this->fractal = new Manager();

        $this->controllerName = $this->getControllerName();
        // FIXME: provide action name
//        $this->actionName = $actionName;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    final protected function getControllerName(): string
    {
        $class = static::class;

        return substr(
            $class,
            strrpos($class, '\\') + 1,
            -strlen('Controller')
        ) ?: '';
    }

    /**
     * @throws SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    final protected function setupApi(int $actionId): void
    {
        $this->apiService->setup($actionId);

        $this->isAuthenticated = true;
    }

    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * {"jsonrpc": "2.0", "result": 19, "id": 3}
     */
    final protected function returnResponse(ApiResponse $apiResponse): void
    {
        try {
            if ($this->isAuthenticated === false) {
                throw new SPException(__u('Unauthorized access'));
            }

            $this->sendJsonResponse(
                JsonRpcResponse::getResponse(
                    $apiResponse,
                    $this->apiService->getRequestId()
                )
            );
        } catch (SPException $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * Returns a JSON response back to the browser
     */
    private function sendJsonResponse(string $response): void
    {
        Json::factory($this->router->response())
            ->returnRawJson($response);
    }

    final protected function returnResponseException(Exception $e): void
    {
        $this->sendJsonResponse(
            JsonRpcResponse::getResponseException(
                $e,
                $this->apiService->getRequestId()
            )
        );
    }
}