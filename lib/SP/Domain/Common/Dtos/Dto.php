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

namespace SP\Domain\Common\Dtos;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SP\Domain\Common\Adapters\DumpMode;
use SP\Domain\Common\Adapters\PrintableTrait;
use SP\Domain\Common\Attributes\DtoTransformation;
use SP\Domain\Common\Attributes\ModelBounded;
use SP\Domain\Common\Models\Model;
use SP\Domain\Common\Ports\Dto as DtoInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Database\QueryResult;
use ValueError;

use function SP\processException;

/**
 * Class Dto
 */
abstract class Dto implements DtoInterface
{
    use PrintableTrait;

    /**
     * @inheritDoc
     * @throws SPException
     */
    public static function fromResult(QueryResult $queryResult, ?string $type = null): static
    {
        return self::fromArray($queryResult->getData($type)->toArray(includeOuter: true));
    }

    /**
     * Create a Dto instance with constructor values from an array.
     *
     * @param array $properties
     * @return static
     * @throws SPException
     */
    public static function fromArray(array $properties): static
    {
        $reflectionClass = new ReflectionClass(static::class);
        $parameters = self::getConstructorParametersFromClass($reflectionClass);

        foreach ($parameters as $name => $value) {
            $parameters[$name] = $properties[$name] ?? null;
        }

        try {
            return $reflectionClass->newInstanceArgs($parameters);
        } catch (ReflectionException $e) {
            processException($e);
        }

        return new static();
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return null[]
     * @throws SPException
     */
    private static function getConstructorParametersFromClass(ReflectionClass $reflectionClass): array
    {
        if (!method_exists(static::class, '__construct')) {
            throw SPException::error('Cannot mutate a class without a constructor');
        }

        return array_map(
            null,
            array_flip(array_map(static fn($p) => $p->getName(), $reflectionClass->getConstructor()->getParameters()))
        );
    }

    /**
     * @param array|null $properties Properties to process
     * @param DumpMode $mode The mode to process the properties
     * @return array
     */
    final public function toArray(?array $properties = null, DumpMode $mode = DumpMode::ONLY): array
    {
        $instanceProperties = get_object_vars($this);

        if (null !== $properties) {
            return match ($mode) {
                DumpMode::ONLY => array_intersect_key($instanceProperties, array_flip($properties)),
                DumpMode::EXCLUDE => array_diff_key($instanceProperties, array_flip($properties))
            };
        }

        return $instanceProperties;
    }

    /**
     * @inheritDoc
     * @throws SPException
     */
    public static function fromModel(Model $model): static
    {
        self::checkModelBounded($model);

        $modelProperties = $model->toArray(includeOuter: true);

        $tranformations = self::getTransformations();

        if (count($tranformations) > 0) {
            array_walk(
                $modelProperties,
                static function (mixed &$v, string $k) use ($tranformations, $model) {
                    if ($v !== null && array_key_exists($k, $tranformations)) {
                        $v = call_user_func($tranformations[$k], $model);
                    }
                }
            );
        }

        return self::fromArray($modelProperties);
    }

    /**
     * @param Model $model
     * @return void
     */
    private static function checkModelBounded(Model $model): void
    {
        $reflection = new ReflectionClass(static::class);

        foreach ($reflection->getAttributes(ModelBounded::class) as $attribute) {
            /** @var ModelBounded $instance */
            $instance = $attribute->newInstance();
            if (!is_a($model, $instance->modelClass)) {
                throw new ValueError(sprintf('Model (%s) is not an instance of %s', $model, $instance->modelClass));
            }
        }
    }

    /**
     * @return array<string, Closure>
     */
    private static function getTransformations(): array
    {
        $transformers = [];
        $reflection = new ReflectionClass(static::class);

        foreach ($reflection->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_PRIVATE) as $method) {
            foreach ($method->getAttributes(DtoTransformation::class) as $attribute) {
                /** @var DtoTransformation $instance */
                $instance = $attribute->newInstance();

                try {
                    $transformers[$instance->targetProperty] = $method->getClosure();
                } catch (ReflectionException $e) {
                    processException($e);
                }
            }
        }

        return $transformers;
    }

    /**
     * @inheritDoc
     */
    public function setBatch(array $properties, array $values): static
    {
        $filteredProperties = array_filter(
            array_combine($properties, $values),
            fn($key) => is_string($key),
            ARRAY_FILTER_USE_KEY
        );

        return new static($filteredProperties);
    }

    /**
     * @throws SPException
     */
    public function mutate(array $properties): static
    {
        $reflectionClass = new ReflectionClass($this);
        $parameters = array_merge(self::getConstructorParametersFromClass($reflectionClass), get_object_vars($this));

        foreach ($parameters as $name => $value) {
            if (array_key_exists($name, $properties)) {
                $parameters[$name] = $properties[$name];
            }
        }

        try {
            return $reflectionClass->newInstanceArgs($parameters);
        } catch (ReflectionException $e) {
            processException($e);
        }

        return new static();
    }
}
