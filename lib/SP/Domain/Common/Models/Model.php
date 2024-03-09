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

use ArrayAccess;
use JsonException;
use JsonSerializable;

/**
 * Class Model
 */
abstract class Model implements JsonSerializable, ArrayAccess
{
    /**
     * Dynamically declared properties. Must not be class' properties
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
            static fn(string $key) => $key !== 'properties' && $key !== 'fields',
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Build a new concrete object from a simple model
     *
     * @param Simple $model
     *
     * @return static
     */
    final public static function buildFromSimpleModel(Simple $model): static
    {
        return new static($model->toArray(null, null, true));
    }

    /**
     * @param array|null $only Include only these properties
     * @param array|null $exclude Filter out these properties
     * @param bool $includeOuter Whether to include non-class properties
     *
     * @return array
     */
    final public function toArray(?array $only = null, ?array $exclude = null, bool $includeOuter = false): array
    {
        $fields = $this->getClassProperties();

        if ($includeOuter) {
            $fields = array_merge($fields, $this->properties);
        }

        if (null !== $only) {
            $fields = array_intersect_key($fields, array_flip($only));
        }

        if (null !== $exclude) {
            $fields = array_diff_key($fields, array_flip($exclude));
        }

        return $fields;
    }

    final public static function getColsWithPreffix(string $preffix, ?array $exclude = null): array
    {
        return array_map(static fn(string $name) => sprintf('%s.%s', $preffix, $name), self::getCols($exclude));
    }

    /**
     * Get columns name for this model
     *
     * @param array|null $exclude The columns to filter out from this model
     *
     * @return array
     */
    final public static function getCols(?array $exclude = null): array
    {
        return array_keys((new static())->toArray(null, $exclude));
    }

    /**
     * Create a new object with properties changed
     *
     * @param array $properties
     *
     * @return static
     */
    final public function mutate(array $properties): static
    {
        return new static(array_merge($this->toArray(), $properties));
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     * @throws JsonException
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param int $options
     *
     * @return string
     * @throws JsonException
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

    /**
     * Get non-class properties
     *
     * @param string $name
     *
     * @return void
     */
    public function __get(string $name)
    {
        $this->offsetGet($name);
    }

    /**
     * Set non-class properties
     *
     * @param string $name
     * @param $value
     *
     * @return void
     */
    public function __set(string $name, $value): void
    {
        $this->offsetSet($name, $value);
    }

    /**
     * Get non-class properties
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->properties[$offset];
    }

    /**
     * Set non-class properties
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->properties[$offset] = $value;
    }

    /**
     * Whether an offset exists in non-class properties
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     *
     * @return bool true on success or false on failure.
     * </p>
     * <p>
     * The return value will be cast to boolean if non-boolean was returned.
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->properties);
    }

    /**
     * Unset a non-class property
     *
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->properties[$offset]);
    }
}
