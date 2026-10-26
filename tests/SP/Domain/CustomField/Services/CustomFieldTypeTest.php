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

namespace SP\Tests\Domain\CustomField\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldTypeRepository;
use SP\Domain\CustomField\Ports\CustomFieldTypeService;
use SP\Domain\CustomField\Services\CustomFieldType;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\CustomFieldTypeGenerator;
use SP\Tests\UnitaryTestCase;
use TypeError;

/**
 * Class CustomFieldTypeTest
 *
 */
#[Group('unitary')]
class CustomFieldTypeTest extends UnitaryTestCase
{

    private CustomFieldTypeService|MockObject $customFieldTypeRepository;
    private CustomFieldType                   $customFieldType;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetAll()
    {
        $customFieldType = CustomFieldTypeGenerator::factory()->buildCustomFieldType();

        $this->customFieldTypeRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn(new QueryResult([$customFieldType]));

        $out = $this->customFieldType->getAll();

        $this->assertEquals([$customFieldType], $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testGetAllWithInvalidClass()
    {
        $this->customFieldTypeRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn(new QueryResult([1]));

        $this->expectException(TypeError::class);

        $this->customFieldType->getAll();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldTypeRepository = $this->createMock(CustomFieldTypeRepository::class);

        $this->customFieldType = new CustomFieldType(
            $this->application,
            $this->customFieldTypeRepository
        );
    }


}
