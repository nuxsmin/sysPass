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

namespace SPT\Domain\Import\Services;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ImportStrategyService;
use SP\Domain\Import\Ports\ItemsImportService;
use SP\Domain\Import\Services\Import;
use SP\Infrastructure\File\FileHandlerInterface;
use SPT\UnitaryTestCase;

/**
 * Class ImportTest
 *
 * @group unitary
 */
class ImportTest extends UnitaryTestCase
{
    private Repository|MockObject            $repository;
    private ImportParamsDto                  $importParamsDto;
    private ImportStrategyService|MockObject $importStrategyService;

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testDoImport()
    {
        $this->repository->expects(self::once())
                         ->method('transactionAware')
                         ->with(
                             self::callback(function (callable $callable) {
                                 $callable();

                                 return true;
                             })
                         )
                         ->willReturn($this->createMock(ItemsImportService::class));

        $itemsImportService = $this->createMock(ItemsImportService::class);
        $itemsImportService->expects(self::once())
                           ->method('doImport')
                           ->with($this->importParamsDto)
                           ->willReturn($itemsImportService);

        $this->importStrategyService
            ->expects(self::once())
            ->method('buildImport')
            ->with($this->importParamsDto)
            ->willReturn($itemsImportService);

        $import = new Import($this->application, $this->importStrategyService, $this->repository);
        $import->doImport($this->importParamsDto);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testDoImportWithException()
    {
        $this->repository->expects(self::once())
                         ->method('transactionAware')
                         ->willThrowException(new RuntimeException('test'));

        $import = new Import($this->application, $this->importStrategyService, $this->repository);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test');

        $import->doImport($this->importParamsDto);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(Repository::class);
        $this->importStrategyService = $this->createMock(ImportStrategyService::class);

        $this->importParamsDto = new ImportParamsDto(
            $this->createMock(FileHandlerInterface::class),
            self::$faker->randomNumber(3),
            self::$faker->randomNumber(3),
            self::$faker->password,
            self::$faker->password
        );
    }
}
