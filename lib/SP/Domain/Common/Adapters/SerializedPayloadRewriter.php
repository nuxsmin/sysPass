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

use SP\Domain\Core\Exceptions\SPException;

use function SP\__u;

/**
 * Class SerializedPayloadRewriter
 *
 * Rewrites legacy class names in a serialized PHP payload without instantiating anything.
 * Only class-name tokens and mangled property names are changed; every other byte is copied.
 */
final class SerializedPayloadRewriter
{
    private const DOUBLE_PATTERN   = '/^(?:[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?|[+-]?INF|NAN)$/';
    private const UNSIGNED_PATTERN = '/^(?:0|[1-9]\d*)$/';

    private int $position = 0;
    /**
     * @var array<string, string>
     */
    private readonly array $classMap;

    /**
     * @param array<string, string> $classMap
     */
    private function __construct(private readonly string $payload, array $classMap)
    {
        $this->classMap = array_change_key_case($classMap, CASE_LOWER);
    }

    /**
     * @param array<string, string> $classMap legacy FQCN => current FQCN
     *
     * @throws SPException
     */
    public static function rewrite(string $payload, array $classMap = LegacyClassMap::MAP): string
    {
        $rewriter = new self($payload, $classMap);
        $rewritten = $rewriter->value();

        if ($rewriter->position !== strlen($payload)) {
            throw $rewriter->malformed();
        }

        return $rewritten;
    }

    /**
     * @throws SPException
     */
    private function value(): string
    {
        $tag = $this->peek();

        return match ($tag) {
            'N' => $this->expect('N;'),
            'b' => $this->scalar('b:', '/^[01]$/'),
            'i' => $this->scalar('i:', '/^[+-]?\d+$/'),
            'd' => $this->scalar('d:', self::DOUBLE_PATTERN),
            'r', 'R' => $this->scalar($tag . ':', '/^[1-9]\d*$/'),
            's' => $this->string(),
            'a' => $this->array(),
            'O' => $this->object(),
            'E' => $this->enum(),
            default => throw $this->malformed(),
        };
    }

    /**
     * @throws SPException
     */
    private function scalar(string $prefix, string $pattern): string
    {
        $this->expect($prefix);
        $token = $this->readUntil(';');

        if (preg_match($pattern, $token) !== 1) {
            throw $this->malformed();
        }

        return $prefix . $token . ';';
    }

    /**
     * @throws SPException
     */
    private function string(): string
    {
        $this->expect('s:');
        $length = $this->unsigned(':');
        $this->expect('"');
        $bytes = $this->bytes($length);
        $this->expect('";');

        return 's:' . $length . ':"' . $bytes . '";';
    }

    /**
     * @throws SPException
     */
    private function array(): string
    {
        $this->expect('a:');
        $count = $this->unsigned(':');
        $this->expect('{');

        $items = '';

        for ($i = 0; $i < $count; $i++) {
            $key = $this->arrayKey();
            $value = $this->value();
            $items .= $key . $value;
        }

        $this->expect('}');

        return 'a:' . $count . ':{' . $items . '}';
    }

    /**
     * @throws SPException
     */
    private function arrayKey(): string
    {
        return match ($this->peek()) {
            'i', 's' => $this->value(),
            default => throw $this->malformed(),
        };
    }

    /**
     * @throws SPException
     */
    private function object(): string
    {
        $this->expect('O:');
        $nameLength = $this->unsigned(':');
        $this->expect('"');
        $class = $this->bytes($nameLength);
        $this->expect('":');
        $count = $this->unsigned(':');
        $this->expect('{');

        $properties = '';

        for ($i = 0; $i < $count; $i++) {
            $key = $this->propertyKey();
            $value = $this->value();
            $properties .= $key . $value;
        }

        $this->expect('}');

        $current = $this->map($class);

        return 'O:' . strlen($current) . ':"' . $current . '":' . $count . ':{' . $properties . '}';
    }

    /**
     * @throws SPException
     */
    private function propertyKey(): string
    {
        if ($this->peek() === 'i') {
            return $this->scalar('i:', '/^[+-]?\d+$/');
        }

        $this->expect('s:');
        $length = $this->unsigned(':');
        $this->expect('"');
        $key = $this->bytes($length);
        $this->expect('";');

        $rewritten = $this->mangledName($key);

        return 's:' . strlen($rewritten) . ':"' . $rewritten . '";';
    }

    /**
     * @throws SPException
     */
    private function mangledName(string $key): string
    {
        if ($key === '' || $key[0] !== "\0") {
            return $key;
        }

        $separator = strpos($key, "\0", 1);

        if ($separator === false) {
            throw $this->malformed();
        }

        $class = substr($key, 1, $separator - 1);
        $current = $this->map($class);

        return $current === $class ? $key : "\0" . $current . substr($key, $separator);
    }

    /**
     * @throws SPException
     */
    private function enum(): string
    {
        $this->expect('E:');
        $length = $this->unsigned(':');
        $this->expect('"');
        $case = $this->bytes($length);
        $this->expect('";');

        $separator = strpos($case, ':');

        if ($separator === false) {
            throw $this->malformed();
        }

        $enum = $this->map(substr($case, 0, $separator)) . substr($case, $separator);

        return 'E:' . strlen($enum) . ':"' . $enum . '";';
    }

    private function map(string $class): string
    {
        return $this->classMap[strtolower($class)] ?? $class;
    }

    /**
     * @throws SPException
     */
    private function peek(): string
    {
        if ($this->position >= strlen($this->payload)) {
            throw $this->malformed();
        }

        return $this->payload[$this->position];
    }

    /**
     * @throws SPException
     */
    private function expect(string $literal): string
    {
        if (substr($this->payload, $this->position, strlen($literal)) !== $literal) {
            throw $this->malformed();
        }

        $this->position += strlen($literal);

        return $literal;
    }

    /**
     * @throws SPException
     */
    private function unsigned(string $delimiter): int
    {
        $token = $this->readUntil($delimiter);

        if (preg_match(self::UNSIGNED_PATTERN, $token) !== 1) {
            throw $this->malformed();
        }

        return (int)$token;
    }

    /**
     * @throws SPException
     */
    private function readUntil(string $delimiter): string
    {
        $found = strpos($this->payload, $delimiter, $this->position);

        if ($found === false) {
            throw $this->malformed();
        }

        $token = substr($this->payload, $this->position, $found - $this->position);
        $this->position = $found + strlen($delimiter);

        return $token;
    }

    /**
     * @throws SPException
     */
    private function bytes(int $length): string
    {
        if ($length > strlen($this->payload) - $this->position) {
            throw $this->malformed();
        }

        $bytes = substr($this->payload, $this->position, $length);
        $this->position += $length;

        return $bytes;
    }

    private function malformed(): SPException
    {
        return SPException::error(sprintf(__u('Malformed serialized payload at offset %d'), $this->position));
    }
}
