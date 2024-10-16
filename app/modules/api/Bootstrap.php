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

namespace SP\Modules\Api;

use Closure;
use Exception;
use Klein\Request;
use Klein\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Domain\Api\Ports\ApiRequestService;
use SP\Domain\Api\Services\JsonRpcResponse;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Http\Code;

use function SP\logger;
use function SP\processException;

/**
 * Class Bootstrap
 */
final class Bootstrap extends BootstrapBase
{

    protected ModuleInterface $module;

    public static function run(BootstrapInterface $bootstrap, ModuleInterface $initModule): void
    {
        logger('------------');
        logger('Boostrap:api');

        try {
            $bootstrap->module = $initModule;
            $bootstrap->handleRequest();
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            processException($e);

            die($e->getMessage());
        }
    }

    protected function configureRouter(): void
    {
        $this->router->respond('POST', '@/api\.php', $this->manageApiRequest());
    }

    private function manageApiRequest(): Closure
    {
        return function (Request $request, Response $response) {
            try {
                logger('API route');

                $response->headers()->set('Content-type', 'application/json; charset=utf-8');

                $apiRequest = $this->buildInstanceFor(ApiRequestService::class);
                [$controllerName, $actionName] = explode('/', $apiRequest->getMethod());
                $controllerClass = self::getClassFor($this->module->getName(), $controllerName, $actionName);
                $method = $actionName . 'Action';

                if (!method_exists($controllerClass, $method)) {
                    logger($controllerClass . '::' . $method);

                    $response->code(Code::NOT_FOUND->value);

                    return $response->body(
                        JsonRpcResponse::getResponseError(
                            self::OOPS_MESSAGE,
                            JsonRpcResponse::METHOD_NOT_FOUND,
                            $apiRequest->getId()
                        )
                    );
                }

                $this->context->setTrasientKey(self::CONTEXT_ACTION_NAME, $actionName);

                $this->initializeCommon();

                $this->module->initialize($controllerName);

                logger('Routing call: ' . $controllerClass . '::' . $method);

                return call_user_func([$this->buildInstanceFor($controllerClass), $method]);
            } catch (Exception $e) {
                processException($e);

                $response->code(Code::INTERNAL_SERVER_ERROR->value);

                return $response->body(JsonRpcResponse::getResponseException($e, 0));
            } finally {
                $this->router->skipRemaining();
            }
        };
    }
}
