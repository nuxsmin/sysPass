<?php
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

namespace SP\Modules\Api\Controllers;

use Exception;
use Klein\Klein;
use League\Fractal\Manager;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Domain\Api\Dtos\ApiResponse;
use SP\Domain\Api\Ports\JsonRpcResponseFormatter;
use SP\Domain\Api\Ports\ApiService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Ports\JsonResponseFactory;
use function SP\__u;
use function SP\processException;

/**
 * Class ControllerBase
 *
 * @package SP\Modules\Api\Controllers
 */
abstract class ControllerBase
{
    protected const SEARCH_COUNT_ITEMS = 25;

    protected string                   $controllerName;
    protected Context                  $context;
    protected EventDispatcherInterface $eventDispatcher;
    protected ConfigDataInterface      $configData;
    protected Manager                  $fractal;
    protected string                   $actionName;
    private bool                       $isAuthenticated = false;

    public function __construct(
        Application                     $application,
        protected readonly Klein        $router,
        protected readonly ApiService   $apiService,
        protected readonly AclInterface $acl,
        protected readonly JsonRpcResponseFormatter $jsonRpcResponseFormatter,
        protected readonly JsonResponseFactory $jsonResponseFactory
    ) {
        $this->context = $application->getContext();
        $this->configData = $application->getConfig()->getConfigData();
        $this->eventDispatcher = $application->getEventDispatcher();

        $this->fractal = new Manager();
        $this->controllerName = $this->getControllerName();
        $this->actionName = $this->context->getTrasientKey(BootstrapBase::CONTEXT_ACTION_NAME);
    }

    final protected function getControllerName(): string
    {
        $class = static::class;

        return substr($class, strrpos($class, '\\') + 1, -strlen('Controller')) ?: '';
    }

    /**
     * @throws SPException
     * @throws ServiceException
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

            $this->sendJsonResponse($this->jsonRpcResponseFormatter->getResponse($apiResponse, $this->apiService->getRequestId()));
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
        $this->jsonResponseFactory->create($this->router->response())->sendRaw($response);
    }

    final protected function returnResponseException(Exception $e): void
    {
        $this->sendJsonResponse($this->jsonRpcResponseFormatter->getResponseException($e, $this->apiService->getRequestId()));
    }
}
