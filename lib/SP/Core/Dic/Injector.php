<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Core\Dic;

use Interop\Container\ContainerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionMethod;

/**
 * Class Injector
 *
 * @package SP\Core\Dic
 */
class Injector
{
    /**
     * Inject object
     *
     * @param ContainerInterface $container
     * @param                    $context
     * @return mixed
     * @throws ContainerException
     */
    public static function inject(ContainerInterface $container, $context)
    {
        try {
            $reflectionMethod = new ReflectionMethod($context, 'inject');
            $methodParams = $reflectionMethod->getParameters();

            $params = [];

            if (!count($methodParams)) {
                return false;
            }

            foreach ($methodParams as $key => $methodParam) {
                if ($methodParam->getClass()) {
                    $className = $methodParam->getClass()->getName();

                    $params[$key] = $container->get($className);
                } else {
                    $params[$key] = null;
                }
            }

            return $reflectionMethod->invokeArgs($context, $params);
        } catch (NotFoundExceptionInterface $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        } catch (ContainerExceptionInterface $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        } catch (\ReflectionException $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }
}