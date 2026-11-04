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

use ReflectionProperty;

/**
 * Serializes model properties while staying compatible with legacy payloads.
 *
 * The array produced by {@see __serialize()} is the same representation PHP builds for the
 * object's properties (mangled private/protected keys included), so newly written payloads stay
 * byte-compatible with the ones produced before __serialize() was introduced.
 *
 * {@see __unserialize()} accepts mangled keys, legacy underscore-prefixed names and dynamic
 * properties, restoring every declared property and forwarding unknown ones to Model's dynamic
 * property bag.
 */
trait SerializesModelProperties
{
    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return (array)$this;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $properties = $this->readModelProperties();
        $dynamicProperties = [];

        foreach ($data as $name => $value) {
            $name = self::normaliseSerializedPropertyName((string)$name);

            if ($name === 'properties') {
                $properties = is_array($value) ? $value : [];

                continue;
            }

            if (property_exists($this, $name)) {
                $this->{$name} = $value;

                continue;
            }

            $dynamicProperties[$name] = $value;
        }

        if ($dynamicProperties !== []) {
            $properties = array_merge($properties, $dynamicProperties);
        }

        $this->writeModelProperties($properties);
    }

    private static function normaliseSerializedPropertyName(string $name): string
    {
        if (str_starts_with($name, "\0")) {
            $name = substr($name, (int)strrpos($name, "\0") + 1);
        }

        if (str_starts_with($name, '_')) {
            $name = substr($name, 1);
        }

        return $name;
    }

    /**
     * @return array<string, mixed>
     */
    private function readModelProperties(): array
    {
        /** @var array<string, mixed> $properties */
        $properties = (new ReflectionProperty(Model::class, 'properties'))->getValue($this);

        return $properties;
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function writeModelProperties(array $properties): void
    {
        (new ReflectionProperty(Model::class, 'properties'))->setValue($this, $properties);
    }
}
