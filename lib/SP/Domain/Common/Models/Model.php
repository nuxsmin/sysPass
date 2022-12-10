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

namespace SP\Domain\Common\Models;

use JsonSerializable;

/**
 * Class DataModel
 */
abstract class Model implements JsonSerializable
{
    private ?array $fields = null;
    /**
     * Dynamically declared properties must not be class' properties
     */
    private array $properties = [];

    public function __construct(?array $properties = [])
    {
        $this->assignProperties($properties);
    }

    private function assignProperties(array $properties): void
    {
        $selfProperties = array_intersect_key($properties, $this->getClassProperties());

        foreach ($selfProperties as $property => $value) {
            $this->{$property} = $value;
        }

        $this->properties = array_diff_key($properties, $selfProperties);
    }

    /**
     * @return array
     */
    private function getClassProperties(): array
    {
        return array_filter(
            get_object_vars($this),
            fn($key) => $key !== 'properties' && $key !== 'fields',
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Build a new concrete object from a simple model
     *
     * @param  \SP\Domain\Common\Models\Simple  $model
     *
     * @return static
     */
    final public static function buildFromSimpleModel(Simple $model): static
    {
        return new static($model->toArray(null, null, true));
    }

    /**
     * @param  array|null  $only  Include only these properties
     * @param  array|null  $filter  Filter out these properties
     * @param  bool  $includeOuter
     *
     * @return array
     */
    final public function toArray(?array $only = null, ?array $filter = null, bool $includeOuter = false): array
    {
        $fields = $this->getClassProperties();

        if ($includeOuter) {
            $fields = array_merge($fields, $this->properties);
        }

        if (null !== $only) {
            $fields = array_intersect_key($fields, array_flip($only));
        }

        if (null !== $filter) {
            $fields = array_diff_key($fields, array_flip($filter));
        }

        $this->fields = array_keys($fields);

        return $fields;
    }

    /**
     * Get columns name for this model
     *
     * @param  array|null  $filter
     *
     * @return array
     */
    final public static function getCols(?array $filter = null): array
    {
        $self = new static();

        return array_keys($self->toArray(null, $filter, false));
    }

    /**
     * Create a new object with properties changed
     *
     * @param  array  $properties
     *
     * @return $this
     */
    final public function mutate(array $properties): static
    {
        return new static(array_merge($this->toArray(), $properties));
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     * @throws \JsonException
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     *
     * @return string
     * @throws \JsonException
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options & JSON_THROW_ON_ERROR);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getFields(): ?array
    {
        return $this->fields;
    }

    /**
     * @param  string  $name
     *
     * @return void
     */
    public function __get(string $name)
    {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }
    }

    /**
     * @param  string  $name
     * @param $value
     *
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->properties[$name] = $value;
    }
}
