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

namespace SP\Domain\Common\Attributes;

use Attribute;

/**
 * Class Hydratable
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Hydratable
{
    private array $targetClass;

    public function __construct(private string $sourceProperty, array $targetClass)
    {
        $this->targetClass = array_filter(
            $targetClass,
            static fn(string $class) => class_exists($class) || interface_exists($class)
        );
    }

    public function getTargetClass(): array
    {
        return $this->targetClass;
    }

    public function getSourceProperty(): string
    {
        return $this->sourceProperty;
    }
}
