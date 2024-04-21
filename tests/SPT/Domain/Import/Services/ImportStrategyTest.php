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

use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ImportHelperInterface;
use SP\Domain\Import\Ports\XmlFileService;
use SP\Domain\Import\Services\CsvImport;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\ImportStrategy;
use SP\Domain\Import\Services\KeepassImport;
use SP\Domain\Import\Services\SyspassImport;
use SP\Domain\Import\Services\XmlFormat;
use SP\Infrastructure\File\FileException;
use SPT\UnitaryTestCase;

/**
 * Class ImportStrategyTest
 *
 */
#[Group('unitary')]
class ImportStrategyTest extends UnitaryTestCase
{

    private ImportStrategy            $importStrategy;
    private XmlFileService|MockObject $xmlFileService;

    public static function csvImportProvider(): array
    {
        return [
            ['text/plain'],
            ['text/csv'],
        ];
    }

    public static function xmlImportProvider(): array
    {
        return [
            ['text/xml'],
            ['application/xml'],
        ];
    }

    /**
     * @throws ImportException
     * @throws Exception
     * @throws FileException
     */
    #[DataProvider('csvImportProvider')]
    public function testBuildImportWithCsv(string $fileType)
    {
        $fileHandler = $this->createMock(FileHandlerInterface::class);
        $fileHandler->expects(self::once())
                    ->method('checkIsReadable');
        $fileHandler->expects(self::once())
                    ->method('getFileType')
                    ->willReturn($fileType);

        $importParamsDto = self::createMock(ImportParamsDto::class);
        $importParamsDto->expects(self::once())
                        ->method('getFile')
                        ->willReturn($fileHandler);

        $out = $this->importStrategy->buildImport($importParamsDto);

        self::assertInstanceOf(CsvImport::class, $out);
    }

    /**
     * @throws ImportException
     * @throws Exception
     * @throws FileException
     */
    #[DataProvider('xmlImportProvider')]
    public function testBuildImportWithSyspassXml(string $fileType)
    {
        $fileHandler = $this->createMock(FileHandlerInterface::class);
        $fileHandler->expects(self::once())
                    ->method('checkIsReadable');
        $fileHandler->expects(self::once())
                    ->method('getFileType')
                    ->willReturn($fileType);

        $importParamsDto = self::createMock(ImportParamsDto::class);
        $importParamsDto->expects(self::once())
                        ->method('getFile')
                        ->willReturn($fileHandler);

        $this->xmlFileService
            ->expects(self::once())
            ->method('builder')
            ->willReturn($this->xmlFileService);

        $this->xmlFileService
            ->expects(self::once())
            ->method('detectFormat')
            ->willReturn(XmlFormat::Syspass);

        $this->xmlFileService
            ->expects(self::once())
            ->method('getDocument')
            ->willReturn(new DOMDocument());

        $out = $this->importStrategy->buildImport($importParamsDto);

        self::assertInstanceOf(SyspassImport::class, $out);
    }

    /**
     * @throws ImportException
     * @throws Exception
     * @throws FileException
     */
    #[DataProvider('xmlImportProvider')]
    public function testBuildImportWithKeepassXml(string $fileType)
    {
        $fileHandler = $this->createMock(FileHandlerInterface::class);
        $fileHandler->expects(self::once())
                    ->method('checkIsReadable');
        $fileHandler->expects(self::once())
                    ->method('getFileType')
                    ->willReturn($fileType);

        $importParamsDto = self::createMock(ImportParamsDto::class);
        $importParamsDto->expects(self::once())
                        ->method('getFile')
                        ->willReturn($fileHandler);

        $this->xmlFileService
            ->expects(self::once())
            ->method('builder')
            ->willReturn($this->xmlFileService);

        $this->xmlFileService
            ->expects(self::once())
            ->method('detectFormat')
            ->willReturn(XmlFormat::Keepass);

        $this->xmlFileService
            ->expects(self::once())
            ->method('getDocument')
            ->willReturn(new DOMDocument());

        $out = $this->importStrategy->buildImport($importParamsDto);

        self::assertInstanceOf(KeepassImport::class, $out);
    }

    /**
     * @throws ImportException
     * @throws Exception
     * @throws FileException
     */
    public function testBuildImportWithFileException()
    {
        $fileHandler = $this->createMock(FileHandlerInterface::class);
        $fileHandler->expects(self::once())
                    ->method('checkIsReadable')
                    ->willThrowException(FileException::error('test'));

        $importParamsDto = self::createMock(ImportParamsDto::class);
        $importParamsDto->expects(self::once())
                        ->method('getFile')
                        ->willReturn($fileHandler);

        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Internal error while reading the file');

        $this->importStrategy->buildImport($importParamsDto);
    }

    /**
     * @throws ImportException
     * @throws Exception
     * @throws FileException
     */
    public function testBuildImportWithMimeTypeException()
    {
        $fileHandler = $this->createMock(FileHandlerInterface::class);
        $fileHandler->expects(self::once())
                    ->method('checkIsReadable');
        $fileHandler->expects(self::once())
                    ->method('getFileType')
                    ->willReturn('a_file_type');

        $importParamsDto = self::createMock(ImportParamsDto::class);
        $importParamsDto->expects(self::once())
                        ->method('getFile')
                        ->willReturn($fileHandler);

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('Mime type not supported ("a_file_type")');

        $this->importStrategy->buildImport($importParamsDto);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->xmlFileService = $this->createMock(XmlFileService::class);

        $this->importStrategy = new ImportStrategy(
            $this->application,
            $this->createStub(ImportHelperInterface::class),
            $this->createStub(CryptInterface::class),
            $this->xmlFileService
        );
    }
}
