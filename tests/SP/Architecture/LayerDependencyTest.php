<?php
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

declare(strict_types=1);

namespace SP\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Enforces the layer dependency rules as a hard rule (ADR-001).
 *
 * @see docs/adr/0001-target-architecture-and-dependency-rules.md
 * @see .kilo/plans/hexagonal-migration.md section 1.2
 */
#[Group('unitary')]
final class LayerDependencyTest extends TestCase
{
    #[DataProvider('rules')]
    #[Test]
    public function testNoLayerViolations(string $rule): void
    {
        $violations = LayerRules::collect(dirname(__DIR__, 3))[$rule] ?? [];

        self::assertSame(
            [],
            $violations,
            sprintf(
                "The %s layer rule is violated:\n%s",
                $rule,
                implode("\n", $violations)
            )
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (LayerRules::RULES as $rule) {
            $rules[$rule] = [$rule];
        }

        return $rules;
    }
}
