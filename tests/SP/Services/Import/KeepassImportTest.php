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

namespace SP\Tests\Services\Import;

use Defuse\Crypto\Exception\CryptoException;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountSearchVData;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountSearchFilter;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Import\FileImport;
use SP\Services\Import\ImportException;
use SP\Services\Import\ImportParams;
use SP\Services\Import\KeepassImport;
use SP\Services\Import\XmlFileImport;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Storage\File\FileException;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class KeepassImportTest
 *
 * @package SP\Tests\Services\Import
 */
class KeepassImportTest extends DatabaseTestCase
{
    /**
     * @var Container
     */
    protected static $dic;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        self::$dic = setupContext();

        self::$dataset = 'syspass_import.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = self::$dic->get(DatabaseConnectionData::class);
    }

    /**
     * @throws ImportException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws NoSuchItemException
     * @throws FileException
     */
    public function testDoImport()
    {
        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_keepass.xml';

        $params = new ImportParams();
        $params->setDefaultUser(1);
        $params->setDefaultGroup(2);

        $import = new KeepassImport(self::$dic, new XmlFileImport(FileImport::fromFilesystem($file)), $params);
        $import->doImport();

        $this->checkImportedData();
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws SPException
     */
    private function checkImportedData()
    {
        // Checkout categories
        $categoryService = self::$dic->get(CategoryService::class);

        $this->assertEquals('Linux', $categoryService->getByName('Linux')->getName());
        $this->assertEquals('Windows', $categoryService->getByName('Windows')->getName());
        $this->assertEquals('Servers', $categoryService->getByName('Servers')->getName());
        $this->assertEquals('General', $categoryService->getByName('General')->getName());

        $this->assertEquals(11, $this->conn->getRowCount('Category'));

        // Checkout clients
        $client = self::$dic->get(ClientService::class)->getByName('KeePass');

        $this->assertEquals('KeePass', $client->getName());

        $this->assertEquals(4, $this->conn->getRowCount('Client'));

        // Checkout accounts
        $accountService = self::$dic->get(AccountService::class);

        // 1st account
        $filter = new AccountSearchFilter();
        $filter->setClientId($client->getId());

        /** @var AccountSearchVData[] $data */
        $data = $accountService->getByFilter($filter)->getDataAsArray();

        $this->assertCount(5, $data);

        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(1, $data[0]->getUserId());
        $this->assertEquals(2, $data[0]->getUserGroupId());
        $this->assertEquals('DC1', $data[0]->getName());
        $this->assertEquals('KeePass', $data[0]->getClientName());
        $this->assertEquals('Windows', $data[0]->getCategoryName());
        $this->assertEquals('192.168.100.1', $data[0]->getUrl());
        $this->assertEquals('ADS server', $data[0]->getNotes());
        $this->assertEquals('administrator', $data[0]->getLogin());

        $pass = $accountService->getPasswordForId($data[0]->getId());

        $this->assertEquals('k6V4iIAeR9SBOprLMUGV', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 2nd account

        $this->assertEquals(4, $data[1]->getId());
        $this->assertEquals(1, $data[1]->getUserId());
        $this->assertEquals(2, $data[1]->getUserGroupId());
        $this->assertEquals('debian', $data[1]->getName());
        $this->assertEquals('KeePass', $data[1]->getClientName());
        $this->assertEquals('Linux', $data[1]->getCategoryName());
        $this->assertEquals('http://debian.org', $data[1]->getUrl());
        $this->assertEquals("Some notes about the server", $data[1]->getNotes());
        $this->assertEquals('root', $data[1]->getLogin());

        $pass = $accountService->getPasswordForId($data[1]->getId());

        $this->assertEquals('TKr321zqCZhgbzmmAX13', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 3rd account
        $this->assertEquals(5, $data[2]->getId());
        $this->assertEquals(1, $data[2]->getUserId());
        $this->assertEquals(2, $data[2]->getUserGroupId());
        $this->assertEquals('proxy', $data[2]->getName());
        $this->assertEquals('KeePass', $data[2]->getClientName());
        $this->assertEquals('Linux', $data[2]->getCategoryName());
        $this->assertEquals('192.168.0.1', $data[2]->getUrl());
        $this->assertEquals('Some notes about proxy server', $data[2]->getNotes());
        $this->assertEquals('admin', $data[2]->getLogin());

        $pass = $accountService->getPasswordForId($data[2]->getId());

        $this->assertEquals('TKr321zqCZhgbzmmAX13', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        $this->assertEquals(6, $data[3]->getId());
        $this->assertEquals(1, $data[3]->getUserId());
        $this->assertEquals(2, $data[3]->getUserGroupId());
        $this->assertEquals('Sample Entry', $data[3]->getName());
        $this->assertEquals('NewDatabase', $data[3]->getCategoryName());

        $this->assertEquals(7, $data[4]->getId());
        $this->assertEquals(1, $data[4]->getUserId());
        $this->assertEquals(2, $data[4]->getUserGroupId());
        $this->assertEquals('Sample Entry #2', $data[4]->getName());
        $this->assertEquals('NewDatabase', $data[4]->getCategoryName());

        $this->assertEquals(7, $this->conn->getRowCount('Account'));
    }
}
