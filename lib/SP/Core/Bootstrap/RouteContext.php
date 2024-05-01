<?php
declare(strict_types=1);
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

use RuntimeException;
use SP\Domain\Common\Providers\Filter;
use SP\Domain\Core\Bootstrap\RouteContextData;

use function SP\__u;

/**
 * Class RouteContext
 */
final class RouteContext
{
    private const ROUTE_REGEX = /** @lang RegExp */
        '#(?P<controller>[a-zA-Z]+)(?:/(?P<actions>[a-zA-Z]+))?(?P<params>(/[a-zA-Z\d.]+)+)?#';


    public static function getRouteContextData(string $route): RouteContextData
    {
        if (!preg_match_all(self::ROUTE_REGEX, $route, $matches)) {
            throw new RuntimeException(__u('Invalid route'));
        }

        $controllerName = $matches['controller'][0];
        $actionName = empty($matches['actions'][0]) ? 'index' : $matches['actions'][0];
        $methodName = sprintf('%sAction', $actionName);
        $methodParams = empty($matches['params'][0])
            ? []
            : Filter::getArray(explode('/', trim($matches['params'][0], '/')));

        return new RouteContextData($controllerName, $actionName, $methodName, $methodParams);
    }
}
