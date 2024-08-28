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
use ReflectionAttribute;
use ReflectionMethod;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Events\Event;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Code;
use SP\Domain\Http\Header;
use SP\Util\Util;

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
            logger('WEB route');

            $controllerClass = self::getClassFor(
                $this->module->getName(),
                $this->routeContextData->controller,
                $this->routeContextData->actionName
            );

            if (!method_exists($controllerClass, $this->routeContextData->methodName)) {
                logger($controllerClass . '::' . $this->routeContextData->methodName);

                $response->code(Code::NOT_FOUND->value);
                $response->append(self::OOPS_MESSAGE);

                return $response;
            }

            $method = new ReflectionMethod(
                $controllerClass,
                $this->routeContextData->methodName
            );

            try {
                $this->setCors($response);

                $this->initializeCommon();

                $this->module->initialize($controllerClass);

                logger(
                    sprintf(
                        'Routing call: %s::%s::%s',
                        $controllerClass,
                        $this->routeContextData->methodName,
                        print_r($this->routeContextData->methodParams, true)
                    )
                );

                /** @var ActionResponse $response */
                $actionResponse = $method->invoke(
                    $this->buildInstanceFor($controllerClass),
                    ...
                    Util::mapScalarParameters(
                        $controllerClass,
                        $this->routeContextData->methodName,
                        $this->routeContextData->methodParams
                    )
                );

                $this->buildResponse($method, $actionResponse, $response);
            } catch (SessionTimeout) {
                logger('Session timeout');
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notify('exception', new Event($e));

                $this->buildResponse(
                    $method,
                    ActionResponse::error($e->getMessage(), $e->getTrace()),
                    $response
                );

                $response->code(Code::INTERNAL_SERVER_ERROR->value);
            }

            return $response->send();
        };
    }

    /**
     * @param ReflectionMethod $method
     * @param ActionResponse $actionResponse
     * @param Response $response
     * @return void
     * @throws SPException
     */
    protected function buildResponse(
        ReflectionMethod $method,
        ActionResponse   $actionResponse,
        Response         $response
    ): void {
        /** @var ReflectionAttribute<Action> $attribute */
        $attribute = array_reduce(
            $method->getAttributes(Action::class),
            static fn($_, ReflectionAttribute $item) => $item
        );

        $responseType = $attribute->newInstance()->responseType;

        if ($responseType === ResponseType::JSON) {
            $this->response->header(Header::CONTENT_TYPE->value, Header::CONTENT_TYPE_JSON->value);
            $response->body(ActionResponse::toJson($actionResponse));
        } elseif ($responseType === ResponseType::PLAIN_TEXT) {
            $response->body(ActionResponse::toPlain($actionResponse));
        }
    }
}
