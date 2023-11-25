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
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\HttpModuleBase;
use SP\Modules\Web\Init as InitWeb;
use SP\Util\Filter;

use function SP\__;
use function SP\logger;
use function SP\processException;

/**
 * Bootstrap web interface
 */
final class BootstrapWeb extends BootstrapBase
{
    private const ROUTE_REGEX = /** @lang RegExp */
        '#(?P<controller>[a-zA-Z]+)(?:/(?P<actions>[a-zA-Z]+))?(?P<params>(/[a-zA-Z\d.]+)+)?#';

    protected HttpModuleBase $module;

    public static function run(ContainerInterface $container): BootstrapWeb
    {
        logger('------------');
        logger('Boostrap:web');

        try {
            /** @noinspection SelfClassReferencingInspection */
            $bs = $container->get(BootstrapWeb::class);
            $bs->module = $container->get(InitWeb::class);

            $bs->handleRequest();

            return $bs;
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

                if (!preg_match_all(self::ROUTE_REGEX, $route, $matches)) {
                    throw new RuntimeException(self::OOPS_MESSAGE);
                }

                $controllerName = $matches['controller'][0];
                $actionName = empty($matches['actions'][0]) ? 'index' : $matches['actions'][0];
                $methodName = sprintf('%sAction', $actionName);
                $methodParams = empty($matches['params'][0])
                    ? []
                    : Filter::getArray(explode('/', trim($matches['params'][0], '/')));

                $controllerClass = self::getClassFor($controllerName, $actionName);

                $this->initializePluginClasses();

                if (!method_exists($controllerClass, $methodName)) {
                    logger($controllerClass . '::' . $methodName);

                    $response->code(404);

                    throw new RuntimeException(self::OOPS_MESSAGE);
                }

                $this->context->setTrasientKey(self::CONTEXT_ACTION_NAME, $actionName);

                $this->setCors($response);

                $this->initializeCommon();

                $this->module->initialize($controllerClass);

                logger(
                    sprintf(
                        'Routing call: %s::%s::%s',
                        $controllerClass,
                        $methodName,
                        print_r($methodParams, true)
                    )
                );

                $controller = $this->createObjectFor($controllerClass);

                return call_user_func_array([$controller, $methodName], $methodParams);
            } catch (SessionTimeout $sessionTimeout) {
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
