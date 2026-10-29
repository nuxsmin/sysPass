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
use ReflectionAttribute;
use ReflectionMethod;
use ReflectionNamedType;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Domain\Api\Ports\ApiRequestService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Modules\Api\Adapters\ActionResponseAdapter;
use SP\Modules\Api\Controllers\ControllerBase;
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
            $actionResponseAdapter = $this->buildInstanceFor(ActionResponseAdapter::class);

            try {
                logger('API route');

                $response->headers()->set('Content-type', 'application/json; charset=utf-8');

                $apiRequest = $this->buildInstanceFor(ApiRequestService::class);
                [$controllerName, $actionName] = explode('/', $apiRequest->getMethod());
                $controllerClass = self::getClassFor($this->module->getName(), $controllerName, $actionName);
                $methodName = $actionName . 'Action';

                $method = $this->getActionMethod($controllerClass, $methodName);

                if ($method === null) {
                    logger($controllerClass . '::' . $methodName);

                    $response->code(Code::NOT_FOUND->value);

                    return $response->body(
                        $actionResponseAdapter->toJsonRpcError(
                            self::OOPS_MESSAGE,
                            ActionResponseAdapter::METHOD_NOT_FOUND,
                            $apiRequest->getId()
                        )
                    );
                }

                $this->context->setTrasientKey(self::CONTEXT_ACTION_NAME, $actionName);

                $this->initializeCommon();

                $this->module->initialize($controllerName);

                logger('Routing call: ' . $controllerClass . '::' . $methodName);

                $controller = $this->buildInstanceFor($controllerClass);

                try {
                    /** @var ActionResponse $actionResponse */
                    $actionResponse = $method->invoke($controller);

                    if ($controller instanceof ControllerBase
                        && $controller->isAuthenticated() === false
                    ) {
                        return $response->body(
                            $actionResponseAdapter->toJsonRpcError(
                                'Unauthorized access',
                                ActionResponseAdapter::SERVER_ERROR,
                                $apiRequest->getId()
                            )
                        );
                    }

                    return $response->body(
                        $actionResponseAdapter->toJsonRpc($actionResponse, $apiRequest->getId())
                    );
                } catch (Exception $e) {
                    processException($e);

                    return $response->body(
                        $actionResponseAdapter->toJsonRpcException($e, $apiRequest->getId())
                    );
                }
            } catch (Exception $e) {
                processException($e);

                $response->code(Code::INTERNAL_SERVER_ERROR->value);

                return $response->body($actionResponseAdapter->toJsonRpcException($e, 0));
            }
        };
    }

    /**
     * @return ReflectionMethod|null
     * @throws \ReflectionException
     */
    private function getActionMethod(string $controllerClass, string $methodName): ?ReflectionMethod
    {
        if (!method_exists($controllerClass, $methodName)) {
            return null;
        }

        $method = new ReflectionMethod($controllerClass, $methodName);

        $returnType = $method->getReturnType();

        if (!$returnType instanceof ReflectionNamedType || $returnType->getName() !== ActionResponse::class) {
            return null;
        }

        /** @var ReflectionAttribute<Action>|null $attribute */
        $attribute = $method->getAttributes(Action::class)[0] ?? null;

        if ($attribute === null || $attribute->newInstance()->responseType !== ResponseType::JSON_RPC) {
            return null;
        }

        return $method;
    }
}
