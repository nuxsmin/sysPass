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

namespace SP\Tests\Infrastructure\CustomField\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
}
