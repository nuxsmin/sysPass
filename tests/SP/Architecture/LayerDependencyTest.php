<?php

declare(strict_types=1);

namespace SP\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Enforces the layer dependency rules as a hard rule.
 *
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
