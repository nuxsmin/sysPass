<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Services\Import;

use Defuse\Crypto\Exception\CryptoException;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Domain\Account\Services\AccountService;
use SP\Domain\Category\Services\CategoryService;
use SP\Domain\Client\Services\ClientService;
use SP\Domain\Import\Services\CsvImport;
use SP\Domain\Import\Services\FileImport;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\ImportParams;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\File\FileException;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class CsvImportTest
 *
 * @package SP\Tests\Services\Import
 */
class CsvImportTest extends DatabaseTestCase
{
    /**
     * @var Container
     */
    protected static $dic;

    /**
     * @throws ContextException
     */
    public static function setUpBeforeClass(): void
    {
        self::$dic = setupContext();

        self::$loadFixtures = true;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ImportException
     * @throws FileException
     */
    public function testDoImport()
    {
        $file = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data.csv';

        $params = new ImportParams();
        $params->setDefaultUser(1);
        $params->setDefaultGroup(1);

        $import = new CsvImport(self::$dic, FileImport::fromFilesystem($file), $params);
        $import->doImport();

        $this->assertEquals(4, $import->getCounter());

        $this->checkImportedData();
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    private function checkImportedData()
    {
        // Checkout categories
        $this->assertEquals('CSV Category 1', self::$dic->get(CategoryService::class)->getByName('CSV Category 1')->getName());
        $this->assertEquals(4, self::getRowCount('Category'));

        // Checkout clients
        $this->assertEquals('CSV Client 1', self::$dic->get(ClientService::class)->getByName('CSV Client 1')->getName());
        $this->assertEquals(5, self::getRowCount('Client'));

        // Checkout accounts
        $accountService = self::$dic->get(AccountService::class);

        // 1st account
        $expectedId = 5;
        $data = $accountService->getByIdEnriched($expectedId)->getAccountVData();

        $this->assertEquals($expectedId, $data->getId());
        $this->assertEquals('Test CSV 1', $data->getName());
        $this->assertEquals('CSV Client 1', $data->getClientName());
        $this->assertEquals('CSV Category 1', $data->getCategoryName());
        $this->assertEquals('http://test.me', $data->getUrl());
        $this->assertEquals('CSV Notes', $data->getNotes());
        $this->assertEquals('csv_login1', $data->getLogin());

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('csv_pass1', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 2nd account
        $expectedId = 6;
        $data = $accountService->getByIdEnriched($expectedId)->getAccountVData();

        $this->assertEquals($expectedId, $data->getId());
        $this->assertEquals('Test CSV 2', $data->getName());
        $this->assertEquals('Google', $data->getClientName());
        $this->assertEquals('Linux', $data->getCategoryName());
        $this->assertEquals('http://linux.org', $data->getUrl());
        $this->assertEquals("CSV Notes 2\nbla\nbla\ncar\n", $data->getNotes());
        $this->assertEquals('csv_login2', $data->getLogin());

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('csv_pass2', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 3rd account
        $expectedId = 7;
        $data = $accountService->getByIdEnriched($expectedId)->getAccountVData();

        $this->assertEquals($expectedId, $data->getId());
        $this->assertEquals('Test CSV 3', $data->getName());
        $this->assertEquals('Apple', $data->getClientName());
        $this->assertEquals('SSH', $data->getCategoryName());
        $this->assertEquals('http://apple.com', $data->getUrl());
        $this->assertEquals('CSV Notes 3', $data->getNotes());
        $this->assertEquals('csv_login2', $data->getLogin());

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('csv_pass3', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        $this->assertEquals(8, self::getRowCount('Account'));
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDoImportInvalidData()
    {
        $file = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_invalid.csv';

        $params = new ImportParams();
        $params->setDefaultUser(1);
        $params->setDefaultGroup(1);

        $import = new CsvImport(self::$dic, FileImport::fromFilesystem($file), $params);

        $this->expectException(ImportException::class);

        $import->doImport();
    }
}
