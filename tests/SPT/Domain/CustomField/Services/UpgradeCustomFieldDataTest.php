<?php
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

namespace SPT\Domain\CustomField\Services;

use Exception;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldDataRepository;
use SP\Domain\CustomField\Services\UpgradeCustomFieldData;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\CustomFieldDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class UpgradeCustomFieldDataTest
 *
 * @group unitary
 */
class UpgradeCustomFieldDataTest extends UnitaryTestCase
{

    private CustomFieldDataRepository|MockObject $customFieldDataRepository;
    private UpgradeCustomFieldData               $upgradeCustomFieldData;

    /**
     * @throws Exception
     */
    public function testUpgradeV300B18072902WithMappedModuleId()
    {
        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(new Callback(static fn(callable $callable) => $callable() === null));

        $modulesId = [10, 61, 62, 71, 72];

        $customFieldsData = array_map(
            static fn() => CustomFieldDataGenerator::factory()
                                                   ->buildCustomFieldData()
                                                   ->mutate(['moduleId' => array_pop($modulesId)]),
            range(0, 4)
        );

        $queryResults = new QueryResult($customFieldsData);

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn($queryResults);

        $this->customFieldDataRepository
            ->expects(self::exactly(5))
            ->method('deleteBatch')
            ->with(
                ...
                self::withConsecutive(
                    ...
                    array_map(
                        static fn(CustomFieldDataModel $customFieldData) => [
                            [$customFieldData->getItemId()],
                            $customFieldData->getModuleId()
                        ],
                        $customFieldsData
                    )
                )
            );

        $this->customFieldDataRepository
            ->expects(self::exactly(5))
            ->method('create');

        $this->upgradeCustomFieldData->upgradeV300B18072902();
    }

    /**
     * @throws Exception
     */
    public function testUpgradeV300B18072902WithUnmappedModuleId()
    {
        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(new Callback(static fn(callable $callable) => $callable() === null));

        $modulesId = [1, 101, 301, 701, 801];

        $customFieldsData = array_map(
            static fn() => CustomFieldDataGenerator::factory()
                                                   ->buildCustomFieldData()
                                                   ->mutate(['moduleId' => array_pop($modulesId)]),
            range(0, 4)
        );

        $queryResults = new QueryResult($customFieldsData);

        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('getAll')
            ->willReturn($queryResults);

        $this->customFieldDataRepository
            ->expects(self::never())
            ->method('deleteBatch');

        $this->customFieldDataRepository
            ->expects(self::never())
            ->method('create');

        $this->upgradeCustomFieldData->upgradeV300B18072902();
    }

    /**
     * @throws Exception
     */
    public function testUpgradeV300B18072902WithException()
    {
        $this->customFieldDataRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->willThrowException(new RuntimeException('test'));

        $this->customFieldDataRepository
            ->expects(self::never())
            ->method('getAll');

        $this->customFieldDataRepository
            ->expects(self::never())
            ->method('deleteBatch');

        $this->customFieldDataRepository
            ->expects(self::never())
            ->method('create');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->upgradeCustomFieldData->upgradeV300B18072902();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldDataRepository = $this->createMock(CustomFieldDataRepository::class);

        $this->upgradeCustomFieldData = new UpgradeCustomFieldData(
            $this->application,
            $this->customFieldDataRepository
        );
    }

}
