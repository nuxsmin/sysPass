<?php
declare(strict_types=1);
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

namespace SP\Tests\Domain\Common\Adapters;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Common\Adapters\LegacyClassMap;
use SP\Domain\Common\Adapters\PersistedClassRegistry;
use SP\Tests\UnitaryTestCase;

/**
 * Class PersistedClassRegistryTest
 */
#[Group('unitary')]
class PersistedClassRegistryTest extends UnitaryTestCase
{
    #[Test]
    public function registeredClassesResolve(): void
    {
        foreach (PersistedClassRegistry::classes() as $class) {
            $this->assertTrue(class_exists($class), sprintf('Registered class %s does not resolve', $class));
        }
    }

    #[Test]
    public function registryHasNoDuplicates(): void
    {
        $classes = PersistedClassRegistry::classes();

        $this->assertCount(count($classes), array_unique($classes));
    }

    #[Test]
    public function legacyMapValuesResolve(): void
    {
        foreach (LegacyClassMap::map() as $legacy => $current) {
            $this->assertTrue(class_exists($current), sprintf('Mapped class %s does not resolve', $current));
        }
    }

    #[Test]
    public function legacyMapKeysDoNotResolve(): void
    {
        foreach (array_keys(LegacyClassMap::map()) as $legacy) {
            $this->assertFalse(class_exists($legacy), sprintf('Legacy class %s still resolves', $legacy));
        }
    }

    #[Test]
    public function legacyMapValuesAreRegistered(): void
    {
        $registered = PersistedClassRegistry::classes();

        foreach (LegacyClassMap::map() as $current) {
            $this->assertContains($current, $registered);
        }
    }
}
