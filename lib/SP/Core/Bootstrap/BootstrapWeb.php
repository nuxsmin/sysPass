<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
use Exception;
use Klein\Request;
use Klein\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Util\Filter;

use function SP\__;
use function SP\logger;
use function SP\processException;

/**
 * Bootstrap web interface
 */
final class BootstrapWeb extends BootstrapBase
{
    protected ModuleInterface $module;

    public static function run(BootstrapInterface $bootstrap, ModuleInterface $initModule): void
    {
        logger('------------');
        logger('Boostrap:web');

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
        $this->router->respond(['GET', 'POST'], '@(?!/api\.php)', $this->manageWebRequest());
    }

    /** @noinspection PhpInconsistentReturnPointsInspection */
    private function manageWebRequest(): Closure
    {
        return function (Request $request, Response $response) {
            try {
                logger('WEB route');

                $route = Filter::getString($request->param('r', 'index/index'));

                $routeContextData = RouteContext::getRouteContextData($route);

                $controllerClass = self::getClassFor(
                    $routeContextData->getController(),
                    $routeContextData->getActionName()
                );

                $this->initializePluginClasses();

                if (!method_exists($controllerClass, $routeContextData->getMethodName())) {
                    logger($controllerClass . '::' . $routeContextData->getMethodName());

                    $response->code(404);

                    throw new RuntimeException(self::OOPS_MESSAGE);
                }

                $this->context->setTrasientKey(self::CONTEXT_ACTION_NAME, $routeContextData->getActionName());

                $this->setCors($response);

                $this->initializeCommon();

                $this->module->initialize($controllerClass);

                logger(
                    sprintf(
                        'Routing call: %s::%s::%s',
                        $controllerClass,
                        $routeContextData->getMethodName(),
                        print_r($routeContextData->getMethodParams(), true)
                    )
                );

                $controller = $this->createObjectFor($controllerClass);

                return call_user_func_array(
                    [$controller, $routeContextData->getMethodName()],
                    $routeContextData->getMethodParams()
                );
            } catch (SessionTimeout) {
                logger('Session timeout');
            } catch (Exception $e) {
                processException($e);

                echo __($e->getMessage());

                if (DEBUG) {
                    echo $e->getTraceAsString();
                }
            }
        };
    }
}
