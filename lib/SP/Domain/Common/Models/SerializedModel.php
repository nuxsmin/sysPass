<?php

declare(strict_types=1);
/**
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
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Common\Attributes\Hydratable;

/**
 * Trait SerializedModel
 */
trait SerializedModel
{
    /**
     * @inheritDoc
     */
    public function hydrate(string $class): ?object
    {
        return $this->parseAttribute(
            function (Hydratable $hydratable) use ($class) {
                $valid = array_filter(
                    $hydratable->getTargetClass(),
                    static fn(string $targetClass) => is_a($class, $targetClass, true)
                );

                $property = $this->{$hydratable->getSourceProperty()};

                if (count($valid) > 0 && $property !== null) {
                    return Serde::deserialize($property, $class) ?: null;
                }

                return null;
            }
        );
    }

    private function parseAttribute(callable $callback): mixed
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getAttributes(Hydratable::class) as $attribute) {
            return $callback($attribute->newInstance());
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function dehydrate(object $object): static|null
    {
        return $this->parseAttribute(
            function (Hydratable $hydratable) use ($object) {
                $valid = array_filter(
                    $hydratable->getTargetClass(),
                    static fn(string $targetClass) => is_a($object, $targetClass)
                );

                if (count($valid) > 0) {
                    return $this->mutate([$hydratable->getSourceProperty() => Serde::serialize($object)]);
                }

                return $this;
            }
        );
    }
}
