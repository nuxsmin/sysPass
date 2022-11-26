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

namespace SP\Domain\Common\Out;

/**
 * Class DataModelBase
 */
abstract class DataModelBase
{
    private array $properties;

    public function __construct(?array $properties = [])
    {
        foreach ($properties as $property => $value) {
            $this->{$property} = $value;
        }
    }

    /**
     * @param  string  $name
     *
     * @return mixed|null
     */
    final public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return $this->properties[$name] ?? null;
    }

    final public function __set(string $name, mixed $value = null): void
    {
        if (is_numeric($value)) {
            $value = (int)$value;
        }

        if (property_exists($this, $name)) {
            $this->{$name} = $value;
        } else {
            $this->properties[$name] = $value;
        }
    }

    final public function __isset(string $name): bool
    {
        return property_exists($this, $name) || isset($this->properties[$name]);
    }

    final public function toArray(): array
    {
        if (count($this->properties) !== 0) {
            return $this->properties;
        }

        return get_object_vars($this);
    }
}
