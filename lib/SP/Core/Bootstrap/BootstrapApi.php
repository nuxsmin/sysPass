<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Bootstrap;

use Closure;
use Klein\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\HttpModuleBase;
use SP\Domain\Api\Services\ApiRequest;
use SP\Domain\Api\Services\JsonRpcResponse;
use SP\Modules\Api\Init as InitApi;

/**
 * Bootstrap API interface
 */
final class BootstrapApi extends BootstrapBase
{

    protected HttpModuleBase $module;

    /**
     * @param  \Psr\Container\ContainerInterface  $container
     *
     * @return \SP\Core\Bootstrap\BootstrapApi
     */
    public static function run(ContainerInterface $container): BootstrapApi
    {
        logger('------------');
        logger('Boostrap:api');

        // TODO: remove
        self::$container = $container;

        try {
            /** @noinspection SelfClassReferencingInspection */
            $bs = $container->get(BootstrapApi::class);
            $bs->module = $container->get(InitApi::class);
            $bs->handleRequest();

            return $bs;
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            processException($e);

            die($e->getMessage());
        }
    }

    protected function configureRouter(): void
    {
        // Manage requests for api module
        $this->router->respond(
            'POST',
            '@/api\.php',
            $this->manageApiRequest()
        );
    }

    private function manageApiRequest(): Closure
    {
        return function ($request, $response, $service) {
            try {
                logger('API route');

                $apiRequest = self::$container->get(ApiRequest::class);

                [$controllerName, $action] = explode('/', $apiRequest->getMethod());

                $controllerClass = self::getClassFor($controllerName);

                $method = $action.'Action';

                if (!method_exists($controllerClass, $method)) {
                    logger($controllerClass.'::'.$method);

                    /** @var Response $response */
                    $response->headers()
                        ->set(
                            'Content-type',
                            'application/json; charset=utf-8'
                        );

                    return $response->body(
                        JsonRpcResponse::getResponseError(
                            self::OOPS_MESSAGE,
                            JsonRpcResponse::METHOD_NOT_FOUND,
                            $apiRequest->getId()
                        )
                    );
                }

                $this->initializeCommon();

                $this->module->initialize($controllerName);

                logger('Routing call: '.$controllerClass.'::'.$method);

                return call_user_func([new $controllerClass(self::$container, $method), $method]);
            } catch (\Exception $e) {
                processException($e);

                /** @var Response $response */
                $response->headers()->set('Content-type', 'application/json; charset=utf-8');

                return $response->body(JsonRpcResponse::getResponseException($e, 0));
            } finally {
                $this->router->skipRemaining();
            }
        };
    }
}