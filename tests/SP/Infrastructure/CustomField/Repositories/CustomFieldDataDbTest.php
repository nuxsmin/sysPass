<?php
declare(strict_types=1);
/*
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

namespace SP\Tests\Infrastructure\CustomField\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Infrastructure\CustomField\Repositories\CustomFieldData;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the CustomFieldData repository queries.
 */
#[Group('integration')]
final class CustomFieldDataDbTest extends DatabaseRepositoryTestCase
{
    private CustomFieldData $customFieldData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldData = new CustomFieldData(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function getForModuleAndItemIdReturnsTheDefinitionsWithTheirData(): void
    {
        $result = $this->customFieldData->getForModuleAndItemId(1, 1);

        self::assertSame(1, $result->getNumRows());

        $row = $result->getDataAsArray()[0];

        self::assertSame(1, $row['definitionId']);
        self::assertSame('Prueba', $row['definitionName']);
        self::assertSame('text', $row['typeName']);
        self::assertSame('Texto', $row['typeText']);
        self::assertSame(1, (int)$row['required']);
        self::assertNotEmpty($row['data']);
        self::assertNotEmpty($row['key']);
    }

    #[Test]
    public function getForModuleAndItemIdOrdersByRequiredThenTypeText(): void
    {
        $this->executeSql(
            "INSERT INTO CustomFieldDefinition VALUES
             (4, 'Email', 1, 0, NULL, 0, 5, 1),
             (5, 'Clave', 1, 0, NULL, 0, 2, 1)"
        );

        $rows = $this->customFieldData->getForModuleAndItemId(1, 1)->getDataAsArray();

        self::assertSame(
            [1, 5, 4],
            array_map(static fn($row) => (int)$row['definitionId'], $rows)
        );
    }

    #[Test]
    public function getForModuleAndItemIdReturnsNoRowsForAnUnknownModule(): void
    {
        self::assertSame(0, $this->customFieldData->getForModuleAndItemId(99, 1)->getNumRows());
    }

    #[Test]
    public function deleteByItemDeletesOnlyTargetedModuleAndItem(): void
    {
        $authTokenModule = AclActionsInterface::AUTHTOKEN;

        $this->executeSql(
            "INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES
             (10, {$authTokenModule}, 1, 'target', NULL),
             (11, {$authTokenModule}, 1, 'other-item', NULL),
             (10, 1, 1, 'other-module', NULL)"
        );

        $deleted = $this->customFieldData->deleteByItem(10, $authTokenModule)->getAffectedNumRows();

        self::assertSame(1, $deleted);

        $remaining = \SP\Tests\getDbHandler()->getConnection()->query(
            "SELECT itemId, moduleId FROM CustomFieldData WHERE (itemId = 10 OR itemId = 11) AND data IN ('target', 'other-item', 'other-module') ORDER BY moduleId ASC, itemId ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        self::assertSame(
            [
                ['itemId' => '10', 'moduleId' => '1'],
                ['itemId' => '11', 'moduleId' => (string)$authTokenModule],
            ],
            array_map(static fn(array $r): array => [
                'itemId' => (string)$r['itemId'],
                'moduleId' => (string)$r['moduleId'],
            ], $remaining)
        );
    }

    #[Test]
    public function deleteByDefinitionDeletesOnlyTargetedDefinition(): void
    {
        $this->executeSql(
            "INSERT INTO CustomFieldDefinition VALUES (99, 'Second Def', 1, 0, NULL, 0, 1, 1)"
        );
        $this->executeSql(
            "INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES
             (50, 1, 1, 'def1-target', NULL),
             (50, 1, 99, 'def99-keep', NULL),
             (51, 1, 1, 'other-item-keep', NULL)"
        );

        $deleted = $this->customFieldData->deleteByDefinition(50, 1, 1)->getAffectedNumRows();

        self::assertSame(1, $deleted);

        $remaining = \SP\Tests\getDbHandler()->getConnection()->query(
            "SELECT itemId, definitionId FROM CustomFieldData WHERE itemId IN (50, 51) ORDER BY itemId ASC, definitionId ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        self::assertSame(
            [
                ['itemId' => 50, 'definitionId' => 99],
                ['itemId' => 51, 'definitionId' => 1],
            ],
            array_map(static fn(array $r): array => [
                'itemId' => (int)$r['itemId'],
                'definitionId' => (int)$r['definitionId'],
            ], $remaining)
        );
    }

    #[Test]
    public function deleteBatchDeletesOnlyTargetedItemIdsAndModule(): void
    {
        $historyModule = AclActionsInterface::ACCOUNT_HISTORY_VIEW;

        $this->executeSql(
            "INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES
             (701, {$historyModule}, 1, 'snap-701', NULL),
             (702, {$historyModule}, 1, 'snap-702', NULL),
             (703, {$historyModule}, 1, 'snap-703-keep', NULL),
             (701, 1, 1, 'live-701-keep', NULL)"
        );

        $deleted = $this->customFieldData->deleteBatch([701, 702], $historyModule)->getAffectedNumRows();

        self::assertSame(2, $deleted);
        self::assertSame(
            0,
            (int)\SP\Tests\getDbHandler()->getConnection()->query(
                "SELECT COUNT(*) FROM CustomFieldData WHERE moduleId = {$historyModule} AND itemId IN (701, 702)"
            )->fetchColumn()
        );
        self::assertSame(
            2,
            (int)\SP\Tests\getDbHandler()->getConnection()->query(
                "SELECT COUNT(*) FROM CustomFieldData WHERE (moduleId = {$historyModule} AND itemId = 703) OR (moduleId = 1 AND itemId = 701)"
            )->fetchColumn()
        );
    }

    #[Test]
    public function copyByModuleAndItemCopiesRowsToDestinationModuleAndItem(): void
    {
        $historyModule = AclActionsInterface::ACCOUNT_HISTORY_VIEW;

        $this->executeSql(
            "INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES
             (811, 1, 1, 'live-encrypted', 'live-key'),
             (812, 1, 1, 'other-live', 'other-key')"
        );

        $copied = $this->customFieldData->copyByModuleAndItem(1, 811, $historyModule, 9001);

        self::assertSame(1, $copied);

        $rows = \SP\Tests\getDbHandler()->getConnection()->query(
            "SELECT itemId, moduleId, definitionId, data, `key` FROM CustomFieldData WHERE moduleId = {$historyModule} AND itemId = 9001"
        )->fetchAll(\PDO::FETCH_ASSOC);

        self::assertCount(1, $rows);
        self::assertSame('live-encrypted', $rows[0]['data']);
        self::assertSame('live-key', $rows[0]['key']);
        self::assertSame(1, (int)$rows[0]['definitionId']);
    }
}
