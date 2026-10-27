<?php

declare(strict_types=1);

namespace SP\Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Enforces the layer dependency rules with a shrinking allowlist.
 *
 * @see .kilo/plans/hexagonal-migration.md section 1.2
 */
#[Group('unitary')]
final class LayerDependencyTest extends TestCase
{
    private const ALLOWLIST_FILE = __DIR__ . '/allowed-violations.php';

    /**
     * Maximum number of allowlisted violations per rule.
     *
     * This freezes the P0-3 baseline. Never increase an entry; lower it when
     * violations are fixed.
     *
     * @var array<string, int>
     */
    private const MAX_ALLOWED = [
        LayerRules::DOMAIN_ONLY => 47,
        LayerRules::INFRASTRUCTURE_ONLY => 2,
        LayerRules::MODULES_INFRASTRUCTURE => 22,
        LayerRules::MODULES_CONCRETE_SERVICES => 41,
        LayerRules::LIB_MODULES => 2,
        LayerRules::DOMAIN_APPLICATION => 0,
        LayerRules::DOMAIN_PRESENTATION_CALLS => 126,
    ];

    #[DataProvider('rules')]
    public function testNoNewLayerViolations(string $rule): void
    {
        $allowlist = self::allowlist();
        $allowed = $allowlist[$rule] ?? [];
        $actual = LayerRules::collect(dirname(__DIR__, 3))[$rule] ?? [];

        self::assertLessThanOrEqual(
            self::MAX_ALLOWED[$rule],
            count($allowed),
            sprintf(
                'The %s allowlist grew to %d entries (maximum %d). Fix the violation instead of adding an allowlist entry.',
                $rule,
                count($allowed),
                self::MAX_ALLOWED[$rule]
            )
        );

        $newViolations = array_values(array_diff($actual, $allowed));

        self::assertSame(
            [],
            $newViolations,
            sprintf(
                "New %s violations were introduced:\n%s",
                $rule,
                implode("\n", $newViolations)
            )
        );

        $staleAllowlistEntries = array_values(array_diff($allowed, $actual));

        self::assertSame(
            [],
            $staleAllowlistEntries,
            sprintf(
                "The %s allowlist contains entries that are no longer violations. Remove them from %s:\n%s",
                $rule,
                self::ALLOWLIST_FILE,
                implode("\n", $staleAllowlistEntries)
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

    /**
     * @return array<string, list<string>>
     */
    private static function allowlist(): array
    {
        if (!is_file(self::ALLOWLIST_FILE)) {
            return [];
        }

        /** @var array<string, list<string>> $allowlist */
        $allowlist = require self::ALLOWLIST_FILE;

        return $allowlist;
    }
}
