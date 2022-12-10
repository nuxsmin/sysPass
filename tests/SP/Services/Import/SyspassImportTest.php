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
use SP\Domain\Import\Services\FileImport;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\ImportParams;
use SP\Domain\Import\Services\SyspassImport;
use SP\Domain\Import\Services\XmlFileImport;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\File\FileException;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

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
        $file = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass.xml';

        $params = new ImportParams();
        $params->setDefaultUser(1);
        $params->setDefaultGroup(2);

        $import = new SyspassImport(self::$dic, new XmlFileImport(FileImport::fromFilesystem($file)), $params);
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
     */
    private function checkImportedData()
    {
        // Checkout categories
        $this->assertEquals('CSV Category 1', self::$dic->get(CategoryService::class)->getByName('CSV Category 1')->getName());

        $this->assertEquals(5, self::getRowCount('Category'));

        // Checkout clients
        $this->assertEquals('CSV Client 1', self::$dic->get(ClientService::class)->getByName('CSV Client 1')->getName());

        $this->assertEquals(6, self::getRowCount('Client'));

        // Checkout accounts
        $accountService = self::$dic->get(AccountService::class);

        // 1st account
        $expectedId = 5;
        $result = $accountService->getByIdEnriched($expectedId);
        $data = $result->getAccountVData();

        $this->assertEquals($expectedId, $data->getId());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(2, $data->getUserGroupId());
        $this->assertEquals('Google', $data->getName());
        $this->assertEquals('Google', $data->getClientName());
        $this->assertEquals('Web', $data->getCategoryName());
        $this->assertEquals('https://google.com', $data->getUrl());
        $this->assertEmpty($data->getNotes());
        $this->assertEquals('admin', $data->getLogin());

        $accountService->withTags($result);

        $expectedTags = [7, 8, 9];
        $i = 0;

        foreach ($result->getTags() as $tag) {
            $this->assertEquals($expectedTags[$i], $tag->getId());
            $i++;
        }

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('-{?^··\mjC<c', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 2nd account
        $expectedId = 6;
        $result = $accountService->getByIdEnriched($expectedId);
        $data = $result->getAccountVData();

        $this->assertEquals($expectedId, $data->getId());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(2, $data->getUserGroupId());
        $this->assertEquals('Google', $data->getName());
        $this->assertEquals('Google', $data->getClientName());
        $this->assertEquals('Web', $data->getCategoryName());
        $this->assertEquals('https://google.com', $data->getUrl());
        $this->assertEquals('blablacar', $data->getNotes());
        $this->assertEquals('admin', $data->getLogin());

        $accountService->withTags($result);

        $expectedTags = [8, 9, 1];
        $i = 0;

        foreach ($result->getTags() as $tag) {
            $this->assertEquals($expectedTags[$i], $tag->getId());
            $i++;
        }

        $pass = $accountService->getPasswordForId($data->getId());

        $this->assertEquals('\'ynHRMJy-fRa', Crypt::decrypt($pass->getPass(), $pass->getKey(), '12345678900'));

        // 3rd account
        $expectedId = 7;
        $result = $accountService->getByIdEnriched($expectedId);
        $data = $result->getAccountVData();

        $this->assertEquals($expectedId, $data->getId());
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

        // 4th account
        $expectedId = 8;
        $result = $accountService->getByIdEnriched($expectedId);
        $data = $result->getAccountVData();

        $this->assertEquals($expectedId, $data->getId());
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

        // 5th account
        $expectedId = 9;
        $result = $accountService->getByIdEnriched($expectedId);
        $data = $result->getAccountVData();

        $this->assertEquals($expectedId, $data->getId());
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

        $this->assertEquals(9, self::getRowCount('Account'));
    }
}
