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

namespace SP\Modules\Web;

use Closure;
use Exception;
use Klein\Request;
use Klein\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Http\Code;

use function SP\__;
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

    private function manageWebRequest(): Closure
    {
        return function (Request $request, Response $response) {
            try {
                logger('WEB route');

                $controllerClass = self::getClassFor(
                    $this->routeContextData->getController(),
                    $this->routeContextData->getActionName()
                );

                if (!method_exists($controllerClass, $this->routeContextData->getMethodName())) {
                    logger($controllerClass . '::' . $this->routeContextData->getMethodName());

                    $response->code(Code::NOT_FOUND->value);
                    $response->append(self::OOPS_MESSAGE);

                    return $response;
                }

                $this->context->setTrasientKey(self::CONTEXT_ACTION_NAME, $this->routeContextData->getActionName());

                $this->setCors($response);

                $this->initializeCommon();

                $this->module->initialize($controllerClass);

                logger(
                    sprintf(
                        'Routing call: %s::%s::%s',
                        $controllerClass,
                        $this->routeContextData->getMethodName(),
                        print_r($this->routeContextData->getMethodParams(), true)
                    )
                );

                return call_user_func_array(
                    [$this->buildInstanceFor($controllerClass), $this->routeContextData->getMethodName()],
                    $this->routeContextData->getMethodParams()
                );
            } catch (SessionTimeout) {
                logger('Session timeout');
            } catch (Exception $e) {
                processException($e);

                echo __($e->getMessage());

                if (DEBUG) {
                    echo $e->getTraceAsString();
                }

                $response->code(Code::INTERNAL_SERVER_ERROR->value);
                $response->append($e->getMessage());
            }

            return $response;
        };
    }
}
