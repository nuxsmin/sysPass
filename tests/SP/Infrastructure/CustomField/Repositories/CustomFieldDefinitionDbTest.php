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
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Infrastructure\CustomField\Repositories\CustomFieldDefinition;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the CustomFieldDefinition repository queries.
 */
#[Group('integration')]
final class CustomFieldDefinitionDbTest extends DatabaseRepositoryTestCase
{
    private CustomFieldDefinition $customFieldDefinition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldDefinition = new CustomFieldDefinition(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function searchFiltersByName(): void
    {
        $result = $this->customFieldDefinition->search(new ItemSearchDto('Prueba'));

        self::assertSame(1, $result->getNumRows());
        self::assertSame('Prueba', $result->getDataAsArray(CustomFieldDefinitionModel::class)[0]->getName());
    }

    #[Test]
    public function searchFiltersByHelp(): void
    {
        $result = $this->customFieldDefinition->search(new ItemSearchDto('Ayuda'));

        self::assertSame(1, $result->getNumRows());
        self::assertSame(1, $result->getDataAsArray(CustomFieldDefinitionModel::class)[0]->getId());
    }

    #[Test]
    public function searchReturnsEveryDefinitionWithoutAFilter(): void
    {
        self::assertSame(3, $this->customFieldDefinition->search(new ItemSearchDto())->getTotalNumRows());
    }
}
