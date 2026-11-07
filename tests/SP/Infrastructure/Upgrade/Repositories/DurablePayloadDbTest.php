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

namespace SP\Tests\Infrastructure\Upgrade\Repositories;

use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SP\Domain\Upgrade\Dtos\DurablePayloadRow;
use SP\Domain\Upgrade\Enums\DurablePayloadType;
use SP\Infrastructure\Database\QueryFactoryAdapter;
use SP\Infrastructure\Upgrade\Repositories\DurablePayload;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the durable payload upgrade queries.
 */
#[Group('integration')]
final class DurablePayloadDbTest extends DatabaseRepositoryTestCase
{
    private DurablePayload $durablePayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->durablePayload = new DurablePayload($this->db, new QueryFactoryAdapter());
    }

    #[Test]
    public function findByTypeReturnsTheAuthTokenVaults(): void
    {
        $rows = $this->rowsFor(DurablePayloadType::AuthTokenVault);

        self::assertSame(['1', '2', '3', '4', '5'], array_map(
            static fn(DurablePayloadRow $row) => $row->id,
            $rows
        ));
        self::assertNull($rows[0]->payload);
        self::assertNotNull($rows[1]->payload);
    }

    #[Test]
    public function findByTypeReturnsThePublicLinkData(): void
    {
        $rows = $this->rowsFor(DurablePayloadType::PublicLinkData);

        self::assertSame(['2', '3'], array_map(
            static fn(DurablePayloadRow $row) => $row->id,
            $rows
        ));
        self::assertNotNull($rows[0]->payload);
    }

    #[Test]
    public function findByTypeReturnsTheUserProfiles(): void
    {
        $rows = $this->rowsFor(DurablePayloadType::UserProfile);

        self::assertSame(['1', '2', '3'], array_map(
            static fn(DurablePayloadRow $row) => $row->id,
            $rows
        ));
        self::assertNotNull($rows[0]->payload);
    }

    #[Test]
    public function findByTypeReturnsTheUserPreferences(): void
    {
        $rows = $this->rowsFor(DurablePayloadType::UserPreferences);

        self::assertSame(['1', '2', '3', '4', '5'], array_map(
            static fn(DurablePayloadRow $row) => $row->id,
            $rows
        ));
        self::assertNotNull($rows[0]->payload);
        self::assertNull($rows[1]->payload);
    }

    #[Test]
    public function findByTypeReturnsTheItemPresetsWithTheirSubtype(): void
    {
        $rows = $this->rowsFor(DurablePayloadType::ItemPreset);

        self::assertSame(['1', '2', '3', '4', '5'], array_map(
            static fn(DurablePayloadRow $row) => $row->id,
            $rows
        ));
        self::assertSame(
            ['permission', 'permission', 'permission', 'permission', 'permission'],
            array_map(static fn(DurablePayloadRow $row) => $row->subtype, $rows)
        );
        self::assertNull($rows[0]->payload);
    }

    #[Test]
    public function findByTypeReturnsThePluginDataUsingTheCompositeKey(): void
    {
        $rows = $this->rowsFor(DurablePayloadType::PluginData);

        self::assertSame(
            ['Authenticator:1', 'Authenticator:2', 'DokuWiki:3', 'XML Exporter:2'],
            array_map(static fn(DurablePayloadRow $row) => $row->id, $rows)
        );
        self::assertSame('1', $rows[0]->payload);
        self::assertNull($rows[0]->subtype);
    }

    #[Test]
    public function updateChangesTheAuthTokenVault(): void
    {
        self::assertSame(1, $this->durablePayload->update(DurablePayloadType::AuthTokenVault, '2', 'new-vault'));

        $rows = $this->rowsFor(DurablePayloadType::AuthTokenVault);

        self::assertSame('new-vault', $rows[1]->payload);
    }

    #[Test]
    public function updateChangesThePublicLinkData(): void
    {
        self::assertSame(1, $this->durablePayload->update(DurablePayloadType::PublicLinkData, '2', 'new-link'));

        self::assertSame('new-link', $this->rowsFor(DurablePayloadType::PublicLinkData)[0]->payload);
    }

    #[Test]
    public function updateChangesTheUserProfile(): void
    {
        self::assertSame(1, $this->durablePayload->update(DurablePayloadType::UserProfile, '1', 'new-profile'));

        self::assertSame('new-profile', $this->rowsFor(DurablePayloadType::UserProfile)[0]->payload);
    }

    #[Test]
    public function updateChangesTheUserPreferences(): void
    {
        self::assertSame(
            1,
            $this->durablePayload->update(DurablePayloadType::UserPreferences, '1', 'new-preferences')
        );

        self::assertSame('new-preferences', $this->rowsFor(DurablePayloadType::UserPreferences)[0]->payload);
    }

    #[Test]
    public function updateChangesTheItemPresetData(): void
    {
        self::assertSame(1, $this->durablePayload->update(DurablePayloadType::ItemPreset, '1', 'new-preset'));

        self::assertSame('new-preset', $this->rowsFor(DurablePayloadType::ItemPreset)[0]->payload);
    }

    #[Test]
    public function updateRejectsPluginData(): void
    {
        $this->expectException(LogicException::class);

        $this->durablePayload->update(DurablePayloadType::PluginData, 'Authenticator:1', 'payload');
    }

    #[Test]
    public function transactionalCommitsTheCallbackResult(): void
    {
        $result = $this->durablePayload->transactional(
            fn() => $this->durablePayload->update(DurablePayloadType::AuthTokenVault, '1', 'committed')
        );

        self::assertSame(1, $result);
        self::assertSame('committed', $this->rowsFor(DurablePayloadType::AuthTokenVault)[0]->payload);
    }

    #[Test]
    public function transactionalRollsBackOnFailure(): void
    {
        try {
            $this->durablePayload->transactional(function (): void {
                $this->durablePayload->update(DurablePayloadType::AuthTokenVault, '1', 'rolled-back');

                throw new RuntimeException('failure');
            });

            self::fail('The callback should have thrown');
        } catch (RuntimeException) {
            // Expected: the transaction must be rolled back
        }

        self::assertNull($this->rowsFor(DurablePayloadType::AuthTokenVault)[0]->payload);
    }

    /**
     * @return DurablePayloadRow[]
     */
    private function rowsFor(DurablePayloadType $type): array
    {
        return iterator_to_array($this->durablePayload->findByType($type));
    }
}
