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

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Models\Category;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Models\Client;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Import\Dtos\CsvImportParamsDto;
use SP\Domain\Import\Ports\FileImportService;
use SP\Domain\Import\Services\CsvImport;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\ImportHelper;
use SP\Domain\Tag\Ports\TagServiceInterface;
use SP\Infrastructure\File\FileException;
use SPT\UnitaryTestCase;

/**
 * Class CsvImportTest
 *
 * @group unitary
 */
class CsvImportTest extends UnitaryTestCase
{

    private AccountService|MockObject    $accountService;
    private MockObject|CategoryService   $categoryService;
    private ClientService|MockObject     $clientService;
    private CryptInterface|MockObject    $crypt;
    private FileImportService|MockObject $fileImportService;
    private CsvImport                    $csvImport;


    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDoImport()
    {
        $params = new CsvImportParamsDto(1, 1);
        $accounts = static function () {
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
        };

        $this->fileImportService
            ->expects(self::once())
            ->method('readFileToArrayFromCsv')
            ->with($params->getDelimiter())
            ->willReturnCallback($accounts);

        $this->clientService
            ->expects(self::exactly(2))
            ->method('create')
            ->with(
                new Callback(static function (Client $client) {
                    return $client->getName() === 'Client_name';
                })
            )
            ->willReturn(100);

        $this->categoryService
            ->expects(self::exactly(2))
            ->method('create')
            ->with(
                new Callback(static function (Category $category) {
                    return $category->getName() === 'Category_name';
                })
            )
            ->willReturn(200);

        $this->accountService
            ->expects(self::exactly(2))
            ->method('create')
            ->with(
                new Callback(static function (AccountCreateDto $dto) {
                    return $dto->getName() === 'Account_name'
                           && $dto->getLogin() === 'a_login'
                           && $dto->getClientId() === 100
                           && $dto->getCategoryId() === 200
                           && $dto->getPass() === 'a_password'
                           && $dto->getNotes() === 'a_note'
                           && $dto->getUrl() === 'a_url';
                })
            );

        $this->csvImport->doImport($params);
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDoImportWithWrongFields()
    {
        $params = new CsvImportParamsDto(1, 1);
        $accounts = static function () {
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password'];
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
        };

        $this->fileImportService
            ->expects(self::once())
            ->method('readFileToArrayFromCsv')
            ->with($params->getDelimiter())
            ->willReturnCallback($accounts);

        $this->clientService
            ->expects(self::never())
            ->method('create');

        $this->categoryService
            ->expects(self::never())
            ->method('create');

        $this->accountService
            ->expects(self::never())
            ->method('create');

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('Wrong number of fields (6)');

        $this->csvImport->doImport($params);
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDoImportWithoutLines()
    {
        $params = new CsvImportParamsDto(1, 1);

        $this->fileImportService
            ->expects(self::once())
            ->method('readFileToArrayFromCsv')
            ->with($params->getDelimiter())
            ->willReturn([]);

        $this->clientService
            ->expects(self::never())
            ->method('create');

        $this->categoryService
            ->expects(self::never())
            ->method('create');

        $this->accountService
            ->expects(self::never())
            ->method('create');

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('No lines read from the file');

        $this->csvImport->doImport($params);
    }


    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDoImportWithEmptyClient()
    {
        $params = new CsvImportParamsDto(1, 1);
        $accounts = static function () {
            yield ['Account_name', '', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
        };

        $this->fileImportService
            ->expects(self::once())
            ->method('readFileToArrayFromCsv')
            ->with($params->getDelimiter())
            ->willReturnCallback($accounts);

        $this->clientService
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(static function (Client $client) {
                    return $client->getName() === 'Client_name';
                })
            )
            ->willReturn(100);

        $this->categoryService
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(static function (Category $category) {
                    return $category->getName() === 'Category_name';
                })
            )
            ->willReturn(200);

        $this->accountService
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(static function (AccountCreateDto $dto) {
                    return $dto->getName() === 'Account_name'
                           && $dto->getLogin() === 'a_login'
                           && $dto->getClientId() === 100
                           && $dto->getCategoryId() === 200
                           && $dto->getPass() === 'a_password'
                           && $dto->getNotes() === 'a_note'
                           && $dto->getUrl() === 'a_url';
                })
            );

        $this->csvImport->doImport($params);
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDoImportWithEmptyCategory()
    {
        $params = new CsvImportParamsDto(1, 1);
        $accounts = static function () {
            yield ['Account_name', 'Client_name', '', 'a_url', 'a_login', 'a_password', 'a_note'];
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
        };

        $this->fileImportService
            ->expects(self::once())
            ->method('readFileToArrayFromCsv')
            ->with($params->getDelimiter())
            ->willReturnCallback($accounts);

        $this->clientService
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(static function (Client $client) {
                    return $client->getName() === 'Client_name';
                })
            )
            ->willReturn(100);

        $this->categoryService
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(static function (Category $category) {
                    return $category->getName() === 'Category_name';
                })
            )
            ->willReturn(200);

        $this->accountService
            ->expects(self::once())
            ->method('create')
            ->with(
                new Callback(static function (AccountCreateDto $dto) {
                    return $dto->getName() === 'Account_name'
                           && $dto->getLogin() === 'a_login'
                           && $dto->getClientId() === 100
                           && $dto->getCategoryId() === 200
                           && $dto->getPass() === 'a_password'
                           && $dto->getNotes() === 'a_note'
                           && $dto->getUrl() === 'a_url';
                })
            );

        $this->csvImport->doImport($params);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountService = $this->createMock(AccountService::class);
        $this->categoryService = $this->createMock(CategoryService::class);
        $this->clientService = $this->createMock(ClientService::class);
        $this->tagService = $this->createMock(TagServiceInterface::class);

        $importHelper = new ImportHelper(
            $this->accountService,
            $this->categoryService,
            $this->clientService,
            $this->createMock(TagServiceInterface::class),
            $this->createMock(ConfigService::class)
        );

        $this->crypt = $this->createMock(CryptInterface::class);
        $this->fileImportService = $this->createMock(FileImportService::class);

        $this->csvImport = new CsvImport($this->application, $importHelper, $this->crypt, $this->fileImportService);
    }
}
