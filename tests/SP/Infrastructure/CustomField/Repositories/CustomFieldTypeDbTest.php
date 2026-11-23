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
use SP\Domain\CustomField\Models\CustomFieldType as CustomFieldTypeModel;
use SP\Infrastructure\CustomField\Repositories\CustomFieldType;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the CustomFieldType repository queries.
 */
#[Group('integration')]
final class CustomFieldTypeDbTest extends DatabaseRepositoryTestCase
{
    private CustomFieldType $customFieldType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldType = new CustomFieldType(
            $this->db,
            $this->context,
            $this->eventDispatcher,
            $this->queryFactory
        );
    }

    #[Test]
    public function getAllReturnsEveryTypeOrderedByName(): void
    {
        $types = $this->customFieldType->getAll()->getDataAsArray(CustomFieldTypeModel::class);

        self::assertCount(10, $types);
        self::assertSame(
            [
                'color',
                'date',
                'email',
                'number',
                'password',
                'telephone',
                'text',
                'textarea',
                'url',
                'wiki',
            ],
            array_map(static fn(CustomFieldTypeModel $type) => $type->getName(), $types)
        );
        self::assertSame('Color', $types[0]->getText());
    }
}
