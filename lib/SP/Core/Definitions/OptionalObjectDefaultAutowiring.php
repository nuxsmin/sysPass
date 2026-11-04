<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Definitions;

use DI\Definition\Definition;
use DI\Definition\ObjectDefinition;
use DI\Definition\ObjectDefinition\MethodInjection;
use DI\Definition\Reference;
use DI\Definition\Source\Autowiring;
use DI\Definition\Source\DefinitionSource;
use DI\Definition\Source\ReflectionBasedAutowiring;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

final class OptionalObjectDefaultAutowiring implements Autowiring, DefinitionSource
{
    /**
     * @param array<string, mixed> $knownEntries
     */
    public function __construct(
        private readonly ReflectionBasedAutowiring $autowiring,
        private readonly array                   $knownEntries
    ) {
    }

    public function autowire(string $name, ?ObjectDefinition $definition = null): ?ObjectDefinition
    {
        return $this->replaceObjectDefaults($this->autowiring->autowire($name, $definition));
    }

    public function getDefinition(string $name): ?Definition
    {
        $definition = $this->autowiring->getDefinition($name);

        return $definition instanceof ObjectDefinition
            ? $this->replaceObjectDefaults($definition)
            : $definition;
    }

    /**
     * @return Definition[]
     */
    public function getDefinitions(): array
    {
        return [];
    }

    private function replaceObjectDefaults(?ObjectDefinition $definition): ?ObjectDefinition
    {
        if ($definition === null || !class_exists($definition->getClassName())) {
            return $definition;
        }

        $constructor = (new ReflectionClass($definition->getClassName()))->getConstructor();

        if ($constructor === null) {
            return $definition;
        }

        $injection = $definition->getConstructorInjection() ?? MethodInjection::constructor();
        $parameters = $injection->getParameters();
        $changed = false;

        foreach ($constructor->getParameters() as $index => $parameter) {
            if (array_key_exists($index, $parameters) || !$parameter->isDefaultValueAvailable()) {
                continue;
            }

            try {
                $defaultValue = $parameter->getDefaultValue();
            } catch (ReflectionException) {
                continue;
            }

            $type = $parameter->getType();

            if (!is_object($defaultValue)
                || !$type instanceof ReflectionNamedType
                || $type->isBuiltin()
                || !array_key_exists($type->getName(), $this->knownEntries)
            ) {
                continue;
            }

            $parameters[$index] = new Reference($type->getName());
            $changed = true;
        }

        if ($changed) {
            $injection->replaceParameters($parameters);
            $definition->setConstructorInjection($injection);
        }

        return $definition;
    }
}
