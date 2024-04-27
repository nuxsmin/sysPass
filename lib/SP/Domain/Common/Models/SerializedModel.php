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

namespace SP\Domain\Common\Models;

use ReflectionClass;
use SP\Domain\Common\Attributes\Hydratable;
use SP\Domain\Core\Exceptions\SPException;
use SP\Util\Serde;

/**
 * Trait SerializedModel
 */
trait SerializedModel
{
    /**
     * @template THydrate
     * @param class-string<THydrate> $class
     *
     * @return THydrate|null
     * @throws SPException
     */
    public function hydrate(string $class): ?object
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getAttributes(Hydratable::class) as $attribute) {
            /** @var Hydratable $instance */
            $instance = $attribute->newInstance();

            $valid = array_filter(
                $instance->getTargetClass(),
                static fn(string $targetClass) => is_a($class, $targetClass, true)
            );

            $property = $this->{$instance->getSourceProperty()};

            if (count($valid) > 0 && $property !== null) {
                return Serde::deserialize($property, $class) ?: null;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function dehydrate(object $object): static
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getAttributes(Hydratable::class) as $attribute) {
            /** @var Hydratable $instance */
            $instance = $attribute->newInstance();

            $valid = array_filter(
                $instance->getTargetClass(),
                static fn(string $targetClass) => is_a($object, $targetClass, true)
            );

            if (count($valid) > 0) {
                return $this->mutate([$instance->getSourceProperty() => Serde::serialize($object)]);
            }
        }

        return $this;
    }
}
