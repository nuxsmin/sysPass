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
use Exception;
use Klein\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\HttpModuleBase;
use SP\Modules\Web\Init as InitWeb;
use SP\Util\Filter;

/**
 * Bootstrap web interface
 */
final class BootstrapWeb extends BootstrapBase
{
    protected HttpModuleBase $module;

    /**
     * @param  \Psr\Container\ContainerInterface  $container
     *
     * @return \SP\Core\Bootstrap\BootstrapWeb
     *
     * TODO: Inject needed classes
     */
    public static function run(ContainerInterface $container): BootstrapWeb
    {
        logger('------------');
        logger('Boostrap:web');

        // TODO: remove
        self::$container = $container;

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
        // Manage requests for web module
        $this->router->respond(
            ['GET', 'POST'],
            '@(?!/api\.php)',
            $this->manageWebRequest()
        );
    }

    private function manageWebRequest(): Closure
    {
        return function ($request, $response, $service) {
            /** @var \Klein\Request $request */
            /** @var \Klein\Response $response */

            try {
                logger('WEB route');

                /** @var \Klein\Request $request */
                $route = Filter::getString($request->param('r', 'index/index'));

                if (!preg_match_all(
                    '#(?P<controller>[a-zA-Z]+)(?:/(?P<action>[a-zA-Z]+))?(?P<params>(/[a-zA-Z\d.]+)+)?#',
                    $route,
                    $matches
                )) {
                    throw new RuntimeException(self::OOPS_MESSAGE);
                }

                $controllerName = $matches['controller'][0];
                $actionName = empty($matches['action'][0]) ? 'index' : $matches['action'][0];
                $methodName = sprintf('%sAction', $actionName);
                $methodParams = empty($matches['params'][0])
                    ? []
                    : Filter::getArray(
                        explode(
                            '/',
                            trim(
                                $matches['params'][0],
                                '/'
                            )
                        )
                    );

                $controllerClass = self::getClassFor($controllerName, $actionName);

                $this->initializePluginClasses();

                if (!method_exists($controllerClass, $methodName)) {
                    logger($controllerClass.'::'.$methodName);

                    $response->code(404);

                    throw new RuntimeException(self::OOPS_MESSAGE);
                }

                $this->setCors($response);

                $this->initializeCommon();

                // TODO: remove??
                if (APP_MODULE === 'web') {
                    $this->module->initialize($controllerName);
                }

                logger(
                    sprintf(
                        'Routing call: %s::%s::%s',
                        $controllerClass,
                        $methodName,
                        print_r($methodParams, true)
                    )
                );

                $controller = self::$container->get($controllerClass);

                return call_user_func_array([$controller, $methodName], $methodParams);
            } catch (SessionTimeout $sessionTimeout) {
                logger('Session timeout');
            } catch (Exception $e) {
                processException($e);

                /** @var Response $response */
                if ($response->status()->getCode() !== 404) {
                    $response->code(503);
                }

                echo __($e->getMessage());

                if (DEBUG) {
                    echo $e->getTraceAsString();
                }
            }
        };
    }
}