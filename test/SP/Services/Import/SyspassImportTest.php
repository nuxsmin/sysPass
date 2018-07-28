<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Test\Services\Import;

use DI\Container;
use SP\Core\Crypt\Crypt;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Import\FileImport;
use SP\Services\Import\ImportException;
use SP\Services\Import\ImportParams;
use SP\Services\Import\SyspassImport;
use SP\Services\Import\XmlFileImport;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Test\DatabaseTestCase;
use function SP\Test\setupContext;

/**
 * Class SyspassImportTest
 *
 * @package SP\Tests\Services\Import
 */
class SyspassImportTest extends DatabaseTestCase
{
    /**
     * @var Container
     */
    protected static $dic;

    /**
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \DI\DependencyException
     */
    public static function setUpBeforeClass()
    {
        self::$dic = setupContext();

        self::$dataset = 'syspass_import.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = self::$dic->get(DatabaseConnectionData::class);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     * @throws \SP\Services\Import\ImportException
     * @throws \SP\Storage\File\FileException
     */
    public function testDoImport()
    {
        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass.xml';

        $params = new ImportParams();
        $params->setDefaultUser(1);
        $params->setDefaultGroup(2);

        $import = new SyspassImport(self::$dic, new XmlFileImport(FileImport::fromFilesystem($file)), $params);
        $import->doImport();

        $this->checkImportedData();
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     */
    private function checkImportedData()
    {
        // Checkout categories
        $this->assertEquals('CSV Category 1', self::$dic->get(CategoryService::class)->getByName('CSV Category 1')->getName());

        $this->assertEquals(5, $this->conn->getRowCount('Category'));

        // Checkout clients
        $this->assertEquals('CSV Client 1', self::$dic->get(ClientService::class)->getByName('CSV Client 1')->getName());

        $this->assertEquals(5, $this->conn->getRowCount('Client'));

        // Checkout accounts
        $accountService = self::$dic->get(AccountService::class);

        // 1st account
        $result = $accountService->getById(3);
        $data = $result->getAccountVData();

        $this->assertEquals(3, $data->getId());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(2, $data->getUserGroupId());
        $this->assertEquals('Google', $data->getName());
        $this->assertEquals('Google', $data->getClientName());
        $this->assertEquals('Web', $data->getCategoryName());
        $this->assertEquals('https://google.com', $data->getUrl());
        $this->assertEmpty($data->getNotes());
        $this->assertEquals('admin', $data->getLogin());

        $accountService->withTagsById($result);

        $expectedTags = [7, 8, 9];
        $i = 0;

        foreach ($result->getTags() as $tag) {
            $this->assertEquals($expectedTags[$i], $tag->getId());
            $i++;
        }

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('-{?^··\mjC<c', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 1st account
        $result = $accountService->getById(4);
        $data = $result->getAccountVData();

        $this->assertEquals(4, $data->getId());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(2, $data->getUserGroupId());
        $this->assertEquals('Google', $data->getName());
        $this->assertEquals('Google', $data->getClientName());
        $this->assertEquals('Web', $data->getCategoryName());
        $this->assertEquals('https://google.com', $data->getUrl());
        $this->assertEquals('blablacar', $data->getNotes());
        $this->assertEquals('admin', $data->getLogin());

        $accountService->withTagsById($result);

        $expectedTags = [8, 9, 1];
        $i = 0;

        foreach ($result->getTags() as $tag) {
            $this->assertEquals($expectedTags[$i], $tag->getId());
            $i++;
        }

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('\'ynHRMJy-fRa', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 1st account
        $result = $accountService->getById(5);
        $data = $result->getAccountVData();

        $this->assertEquals(5, $data->getId());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(2, $data->getUserGroupId());
        $this->assertEquals('Test CSV 1', $data->getName());
        $this->assertEquals('CSV Client 1', $data->getClientName());
        $this->assertEquals('CSV Category 1', $data->getCategoryName());
        $this->assertEquals('http://test.me', $data->getUrl());
        $this->assertEquals('CSV Notes', $data->getNotes());
        $this->assertEquals('csv_login1', $data->getLogin());

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('csv_pass1', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 2nd account
        $result = $accountService->getById(6);
        $data = $result->getAccountVData();

        $this->assertEquals(6, $data->getId());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(2, $data->getUserGroupId());
        $this->assertEquals('Test CSV 2', $data->getName());
        $this->assertEquals('Google', $data->getClientName());
        $this->assertEquals('Linux', $data->getCategoryName());
        $this->assertEquals('http://linux.org', $data->getUrl());
        $this->assertEquals("CSV Notes 2\nbla\nbla\ncar\n", $data->getNotes());
        $this->assertEquals('csv_login2', $data->getLogin());

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('csv_pass2', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 3rd account
        $result = $accountService->getById(7);
        $data = $result->getAccountVData();

        $this->assertEquals(7, $data->getId());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(2, $data->getUserGroupId());
        $this->assertEquals('Test CSV 3', $data->getName());
        $this->assertEquals('Apple', $data->getClientName());
        $this->assertEquals('SSH', $data->getCategoryName());
        $this->assertEquals('http://apple.com', $data->getUrl());
        $this->assertEquals('CSV Notes 3', $data->getNotes());
        $this->assertEquals('csv_login2', $data->getLogin());

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('csv_pass3', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        $this->assertEquals(7, $this->conn->getRowCount('Account'));
    }

    /**
     * @throws ImportException
     * @throws \SP\Storage\File\FileException
     */
    public function testDoImportInvalidData()
    {
        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass_invalid.xml';

        $params = new ImportParams();
        $params->setDefaultUser(1);
        $params->setDefaultGroup(1);

        $import = new SyspassImport(self::$dic, new XmlFileImport(FileImport::fromFilesystem($file)), $params);

        $this->expectException(ImportException::class);

        $import->doImport();
    }
}
