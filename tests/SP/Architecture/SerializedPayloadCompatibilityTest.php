<?php

declare(strict_types=1);

namespace SP\Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Domain\Common\Adapters\LegacyClassMap;
use SP\Domain\Common\Adapters\PersistedClassRegistry;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Legacy\LegacyConfigData;

#[Group('unitary')]
final class SerializedPayloadCompatibilityTest extends TestCase
{
    private const LEGACY_SNAPSHOT_TARGETS = [
        ConfigData::class,
    ];

    #[Test]
    public function everyRegisteredPersistedClassResolves(): void
    {
        foreach (PersistedClassRegistry::classes() as $class) {
            self::assertTrue(
                class_exists($class),
                sprintf('Registered persisted class does not exist: %s', $class)
            );
        }
    }

    #[Test]
    public function everyLegacyClassNameIsGone(): void
    {
        foreach (array_keys(LegacyClassMap::map()) as $class) {
            self::assertFalse(
                class_exists($class),
                sprintf('Legacy persisted class still exists: %s', $class)
            );
        }
    }

    #[Test]
    public function everyRegisteredPersistedClassHasACurrentNameInTheLegacyMap(): void
    {
        $currentNames = array_values(LegacyClassMap::map());

        foreach (PersistedClassRegistry::classes() as $class) {
            if (in_array($class, self::LEGACY_SNAPSHOT_TARGETS, true)) {
                continue;
            }

            self::assertContains(
                $class,
                $currentNames,
                sprintf('Registered persisted class has no legacy map entry: %s', $class)
            );
        }
    }

    #[Test]
    public function everyLegacyMapTargetResolves(): void
    {
        foreach (LegacyClassMap::map() as $legacy => $current) {
            self::assertTrue(
                class_exists($current),
                sprintf('Legacy map target for %s does not exist: %s', $legacy, $current)
            );
        }
    }

    #[Test]
    public function legacyConfigSnapshotIsNotPersisted(): void
    {
        self::assertNotContains(LegacyConfigData::class, PersistedClassRegistry::classes());
    }

    #[Test]
    public function legacyConfigDataIsDecodedThroughTheSnapshot(): void
    {
        self::assertSame(LegacyConfigData::class, LegacyClassMap::map()['SP\Config\ConfigData']);
    }
}
