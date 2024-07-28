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
        return min(
            self::convertShortUnit(ini_get('upload_max_filesize')),
            self::convertShortUnit(ini_get('post_max_size')),
            self::convertShortUnit(ini_get('memory_limit'))
        );
    }

    public static function convertShortUnit(string $value): int
    {
        if (preg_match('/(\d+)([KMGkmg])/', $value, $match)) {
            return match (strtoupper($match[2])) {
                'K' => (int)$match[1] * 1024,
                'M' => (int)$match[1] * (1024 ** 2),
                'G' => (int)$match[1] * (1024 ** 3)
            };
        }

        return (int)$value;
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
        $in = is_string($in) ? strtolower($in) : $in;

        // if not strict, we only have to check if something is false
        if (!$in
            || in_array($in, ['false', 'no', 'n', '0', 'off', false, 0], true)
        ) {
            return false;
        }

        if ($strict
            && in_array($in, ['true', 'yes', 'y', '1', 'on', true, 1], true)
        ) {
            return true;
        }

        // not strict? let the regular php bool check figure it out (will
        // largely default to true)
        return (bool)$in;
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
        return self::convertShortUnit(ini_get('memory_limit')) / FileHandler::CHUNK_FACTOR;
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
            if (!$parameter->isOptional() && !isset($parametersValue[$parameter->getPosition()])) {
                throw new ValueError('Method parameter expects a value');
            }

            $type = self::getMethodParameterTypes($parameter)[0]->getName();

            $methodParameters[$parameter->getPosition()] = match ($type) {
                'int' => (int)$parametersValue[$parameter->getPosition()],
                'bool' => (bool)$parametersValue[$parameter->getPosition()],
                'float' => (float)$parametersValue[$parameter->getPosition()],
                'array' => (array)$parametersValue[$parameter->getPosition()],
                'object' => (object)$parametersValue[$parameter->getPosition()],
                default => (string)$parametersValue[$parameter->getPosition()]
            };
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
}
