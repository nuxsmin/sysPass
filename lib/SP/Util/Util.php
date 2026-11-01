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

namespace SP\Util;

use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use SP\Domain\Common\Providers\Environment;
use SP\Domain\Common\Providers\Filter;
use SP\Infrastructure\File\FileHandler;
use ValueError;

/**
 * Class Util
 */
final class Util
{
    /**
     * Obtener el tamaño máximo de subida de PHP.
     */
    public static function getMaxUpload(): int
    {
        return Environment::getMaxUpload();
    }

    public static function convertShortUnit(string $value): int
    {
        return Environment::convertShortUnit($value);
    }

    /**
     * Adaptador para convertir una cadena de IDs a un array
     */
    public static function itemsIdAdapter(string $itemsId, string $delimiter = ','): array
    {
        return array_map(
            static fn(string|int $value) => (int)$value,
            explode($delimiter, $itemsId)
        );
    }

    public static function getMaxDownloadChunk(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit < 0) {
            return 1024;
        }

        return (int)(self::convertShortUnit(ini_get('memory_limit')) / FileHandler::CHUNK_FACTOR);
    }

    /**
     * @param string $class
     * @param string $method
     * @param array $parametersValue
     * @return array
     * @throws ReflectionException
     */
    public static function mapScalarParameters(string $class, string $method, array $parametersValue): array
    {
        $reflectionMethod = new ReflectionMethod($class, $method);

        $methodParameters = [];

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $position = $parameter->getPosition();

            if (isset($parametersValue[$position])) {
                $type = self::getMethodParameterTypes($parameter)[0]->getName();

                $methodParameters[$position] = match ($type) {
                    'int' => (int)$parametersValue[$position],
                    'bool' => Util::boolval($parametersValue[$position]),
                    'float' => (float)$parametersValue[$position],
                    'array' => (array)$parametersValue[$position],
                    'object' => (object)$parametersValue[$position],
                    default => (string)$parametersValue[$position]
                };
            } elseif ($parameter->allowsNull()) {
                $methodParameters[$position] = null;
            } elseif (!$parameter->isOptional()) {
                throw new ValueError('Method parameter expects a value');
            }
        }

        return $methodParameters;
    }

    /**
     * @param ReflectionParameter $reflectionParameter
     * @return array<ReflectionNamedType>
     */
    public static function getMethodParameterTypes(ReflectionParameter $reflectionParameter): array
    {
        $reflectionType = $reflectionParameter->getType();

        if (!$reflectionType) {
            return [];
        }

        return $reflectionType instanceof ReflectionUnionType
            ? $reflectionType->getTypes()
            : [$reflectionType];
    }

    /**
     * Checks a variable to see if it should be considered a boolean true or false.
     * Also takes into account some text-based representations of true of false,
     * such as 'false','N','yes','on','off', etc.
     *
     * @param mixed $in The variable to check
     * @param bool $strict If set to false, consider everything that is not false to
     *                      be true.
     *
     * @return bool The boolean equivalent or null (if strict, and no exact equivalent)
     * @author Samuel Levy <sam+nospam@samuellevy.com>
     *
     */
    public static function boolval(mixed $in, bool $strict = false): bool
    {
        return Filter::getBool($in, $strict);
    }
}
