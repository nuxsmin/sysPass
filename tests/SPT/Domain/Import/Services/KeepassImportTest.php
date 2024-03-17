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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Models\Category;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Models\Client;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Services\ImportHelper;
use SP\Domain\Import\Services\KeepassImport;
use SP\Domain\Tag\Ports\TagService;
use SPT\UnitaryTestCase;

/**
 * Class KeepassImportTest
 *
 */
#[Group('unitary')]
class KeepassImportTest extends UnitaryTestCase
{

    private const KEEPASS_FILE = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                 'data_keepass.xml';
    private KeepassImport              $keepassImport;
    private AccountService|MockObject  $accountService;
    private MockObject|CategoryService $categoryService;
    private ClientService|MockObject   $clientService;

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testDoImport()
    {
        $importParamsDto = $this->createStub(ImportParamsDto::class);

        $this->clientService
            ->expects(self::once())
            ->method('getByName')
            ->with('KeePass')
            ->willReturn(null);

        $this->clientService
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static fn(Client $client) => $client->getName() === 'KeePass'))
            ->willReturn(100);

        $this->categoryService
            ->expects(self::exactly(9))
            ->method('getByName')
            ->willReturn(null);

        $this->categoryService
            ->expects(self::exactly(9))
            ->method('create')
            ->with(
                self::callback(
                    static fn(Category $category) => !empty($category->getName()) &&
                                                     $category->getDescription() === 'KeePass'
                )
            )
            ->willReturn(200);

        $this->accountService
            ->expects(self::exactly(5))
            ->method('create');

        $this->keepassImport->doImport($importParamsDto);
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testDoImportWithAccountException()
    {
        $importParamsDto = $this->createStub(ImportParamsDto::class);

        $this->clientService
            ->expects(self::once())
            ->method('getByName')
            ->with('KeePass')
            ->willReturn(null);

        $this->clientService
            ->expects(self::once())
            ->method('create')
            ->with(self::callback(static fn(Client $client) => $client->getName() === 'KeePass'))
            ->willReturn(100);

        $this->categoryService
            ->expects(self::exactly(9))
            ->method('getByName')
            ->willReturn(null);

        $this->categoryService
            ->expects(self::exactly(9))
            ->method('create')
            ->with(
                self::callback(
                    static fn(Category $category) => !empty($category->getName()) &&
                                                     $category->getDescription() === 'KeePass'
                )
            )
            ->willReturn(200);

        $this->accountService
            ->expects(self::exactly(5))
            ->method('create')
            ->willThrowException(SPException::error('test'));

        $this->keepassImport->doImport($importParamsDto);
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

        $document = new DOMDocument();
        $document->load(self::KEEPASS_FILE, LIBXML_NOBLANKS);

        $this->keepassImport = new KeepassImport($this->application, $importHelper, $crypt, $document);
    }
}
