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

namespace SP\Domain\Common\Adapters;

use __PHP_Incomplete_Class;
use SplObjectStorage;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\Exceptions\SPException;

use function SP\__u;

/**
 * Class SerializedPayloadReader
 */
final class SerializedPayloadReader
{
    private const MAX_DEPTH = 512;

    /**
     * @template T of object
     *
     * @param class-string<T> $expectedClass
     * @param string[] $allowedClasses
     * @param array<string, string> $classMap
     * @return T
     *
     * @throws InvalidClassException
     */
    public static function read(
        string $payload,
        string $expectedClass,
        array $allowedClasses = PersistedClassRegistry::CLASSES,
        array $classMap = LegacyClassMap::MAP
    ): object {
        if (!class_exists($expectedClass)) {
            throw InvalidClassException::error(
                sprintf(__u('Expected class does not exist: %s'), $expectedClass)
            );
        }

        $allowedClasses = array_values(array_unique([...$allowedClasses, $expectedClass]));

        try {
            $rewritten = SerializedPayloadRewriter::rewrite($payload, $classMap);
        } catch (SPException $e) {
            throw InvalidClassException::error(
                sprintf(__u('Couldn\'t rewrite the serialized payload: %s'), $e->getMessage()),
                null,
                $e->getCode(),
                $e
            );
        }

        $value = @unserialize($rewritten, ['allowed_classes' => $allowedClasses]);

        if ($value === false) {
            throw InvalidClassException::error(__u('Couldn\'t unserialize the payload'));
        }

        if (!is_object($value)
            || $value instanceof __PHP_Incomplete_Class
            || !($value instanceof $expectedClass)
        ) {
            throw InvalidClassException::error(
                sprintf(__u('Payload does not decode to the expected class: %s'), $expectedClass)
            );
        }

        self::assertFullyDecoded($value, new SplObjectStorage(), 0);

        return $value;
    }

    /**
     * @param SplObjectStorage<object, mixed> $seen
     *
     * @throws InvalidClassException
     */
    private static function assertFullyDecoded(mixed $value, SplObjectStorage $seen, int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw InvalidClassException::error(__u('Serialized payload is nested too deeply'));
        }

        if ($value instanceof __PHP_Incomplete_Class) {
            throw InvalidClassException::error(__u('Serialized payload contains an incomplete class'));
        }

        if (is_object($value)) {
            if ($seen->contains($value)) {
                return;
            }

            $seen->attach($value);

            foreach ((array)$value as $property) {
                self::assertFullyDecoded($property, $seen, $depth + 1);
            }

            return;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                self::assertFullyDecoded($item, $seen, $depth + 1);
            }
        }
    }
}
