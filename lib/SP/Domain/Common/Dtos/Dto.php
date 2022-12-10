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

namespace SP\Domain\Common\Dtos;

/**
 * Class Dto
 */
abstract class Dto
{
    protected array $reservedProperties = [];

    /**
     * Expose any property. This allows to get any property from dynamic calls.
     *
     * @param  string  $property
     *
     * @return mixed
     */
    public function get(string $property): mixed
    {
        if (property_exists($this, $property) && !in_array($property, $this->reservedProperties)) {
            return $this->{$property};
        }

        return null;
    }

    /**
     * Set any property. This allows to set any property from dynamic calls.
     *
     * @param  string  $property
     * @param  mixed  $value
     *
     * @return \SP\Domain\Common\Dtos\Dto|null Returns a new instance with the property set.
     */
    public function set(string $property, mixed $value): static|null
    {
        if (property_exists($this, $property) && !in_array($property, $this->reservedProperties)) {
            $self = clone $this;
            $self->{$property} = $value;

            return $self;
        }

        return null;
    }
}
