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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Models\Category;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Models\Client;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Import\Dtos\CsvImportParamsDto;
use SP\Domain\Import\Services\CsvImport;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\ImportHelper;
use SP\Domain\Tag\Ports\TagService;
use SP\Infrastructure\File\FileException;
use SPT\UnitaryTestCase;

/**
 * Class CsvImportTest
 *
 */
#[Group('unitary')]
class CsvImportTest extends UnitaryTestCase
{

    private AccountService|MockObject       $accountService;
    private MockObject|CategoryService      $categoryService;
    private ClientService|MockObject        $clientService;
    private FileHandlerInterface|MockObject $fileHandler;
    private CsvImport                       $csvImport;


    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDoImport()
    {
        $params = new CsvImportParamsDto($this->fileHandler, 1, 1);
        $accounts = static function () {
            yield ['Account_name', 'Client_name_1', 'Category_name_1', 'a_url', 'a_login', 'a_password', 'a_note'];
            yield ['Account_name', 'Client_name_2', 'Category_name_2', 'a_url', 'a_login', 'a_password', 'a_note'];
        };

        $this->fileHandler
            ->expects(self::once())
            ->method('readFromCsv')
            ->with($params->getDelimiter())
            ->willReturnCallback($accounts);

        $clientServiceCounter = new InvokedCount(2);

        $this->clientService
            ->expects($clientServiceCounter)
            ->method('create')
            ->with(
                new Callback(static function (Client $client) use ($clientServiceCounter) {
                    return ($clientServiceCounter->numberOfInvocations() === 1 &&
                            $client->getName() === 'Client_name_1') ||
                           ($clientServiceCounter->numberOfInvocations() === 2 &&
                            $client->getName() === 'Client_name_2');
                })
            )
            ->willReturn(100);

        $categoryServiceCounter = new InvokedCount(2);

        $this->categoryService
            ->expects($categoryServiceCounter)
            ->method('create')
            ->with(
                new Callback(static function (Category $category) use ($categoryServiceCounter) {
                    return ($categoryServiceCounter->numberOfInvocations() === 1 &&
                            $category->getName() === 'Category_name_1')
                           || ($categoryServiceCounter->numberOfInvocations() === 2 &&
                               $category->getName() === 'Category_name_2');
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
        $params = new CsvImportParamsDto($this->fileHandler, 1, 1);
        $accounts = static function () {
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password'];
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
        };

        $this->fileHandler
            ->expects(self::once())
            ->method('readFromCsv')
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
        $params = new CsvImportParamsDto($this->fileHandler, 1, 1);

        $this->fileHandler
            ->expects(self::once())
            ->method('readFromCsv')
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
        $params = new CsvImportParamsDto($this->fileHandler, 1, 1);
        $accounts = static function () {
            yield ['Account_name', '', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
        };

        $this->fileHandler
            ->expects(self::once())
            ->method('readFromCsv')
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
        $params = new CsvImportParamsDto($this->fileHandler, 1, 1);
        $accounts = static function () {
            yield ['Account_name', 'Client_name', '', 'a_url', 'a_login', 'a_password', 'a_note'];
            yield ['Account_name', 'Client_name', 'Category_name', 'a_url', 'a_login', 'a_password', 'a_note'];
        };

        $this->fileHandler
            ->expects(self::once())
            ->method('readFromCsv')
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

        $importHelper = new ImportHelper(
            $this->accountService,
            $this->categoryService,
            $this->clientService,
            $this->createMock(TagService::class),
            $this->createMock(ConfigService::class)
        );

        $crypt = $this->createMock(CryptInterface::class);
        $this->fileHandler = $this->createMock(FileHandlerInterface::class);

        $this->csvImport = new CsvImport($this->application, $importHelper, $crypt, $this->fileHandler);
    }
}
