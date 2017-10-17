<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionMethod;

/**
 * Class Dic
 *
 * @package SP\Core\Dic
 */
final class Container implements DicInterface
{
    /**
     * @var array Shared objects
     */
    private $shared = [];
    /**
     * @var array Factory objects
     */
    private $factory = [];

    /**
     * Store shared object
     *
     * @param string   $name
     * @param callable $callable
     * @internal param callable|string $class
     */
    public function share($name, $callable = null)
    {
        $this->shared[$name] = $callable;
    }

    /**
     * Store factory object
     *
     * @param string   $name
     * @param callable $callable
     * @internal param callable|string $class
     */
    public function add($name, $callable = null)
    {
        $this->factory[$name] = $callable;
    }

    /**
     * Inject object
     *
     * @param $context
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function inject($context)
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

                    if ($this->has($className)) {
                        $params[$key] = $this->get($className);
                    } else {
                        $params[$key] = new $className;
                    }
                } else {
                    $params[$key] = null;
                }
            }

            return $reflectionMethod->invokeArgs($context, $params);
        } catch (\ReflectionException $e) {
            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->shared) || array_key_exists($id, $this->factory);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->factory)) {
            return $this->getFactoryObject($id);
        }

        if (array_key_exists($id, $this->shared)) {
            return $this->getSharedObject($id);
        }

        throw new NotFoundException(sprintf('Object not found (%s)', $id));
    }

    /**
     * @param $id
     * @return mixed
     * @throws ContainerExceptionInterface
     */
    private function getFactoryObject($id)
    {
        if (is_callable($this->factory[$id])) {
            return $this->factory[$id]($this);
        }

        if (class_exists($id)) {
            return new $id();
        }

        throw new ContainerException(sprintf('Invalid class (%s)', $id));
    }

    /**
     * @param $id
     * @return mixed
     */
    private function getSharedObject($id)
    {
        if (get_class($this->shared[$id]) === $id) {
            return $this->shared[$id];
        }

        if (is_callable($this->shared[$id])) {
            $this->shared[$id] = $this->shared[$id]($this);
        } elseif (class_exists($id)) {
            $this->shared[$id] = new $id();
        }

        return $this->shared[$id];
    }
}