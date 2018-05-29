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

namespace SP\Tests;

use DI\ContainerBuilder;
use DI\DependencyException;
use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\DbUnit\Database\Connection;
use PHPUnit\DbUnit\Database\DefaultConnection;
use PHPUnit\DbUnit\DataSet\IDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;
use SP\Account\AccountRequest;
use SP\Config\ConfigData;
use SP\Core\Context\ContextInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountVData;
use SP\DataModel\ItemSearchData;
use SP\Mvc\Model\QueryCondition;
use SP\Repositories\Account\AccountRepository;
use SP\Services\Account\AccountPasswordRequest;
use SP\Services\User\UserLoginResponse;
use SP\Storage\DatabaseConnectionData;
use SP\Storage\MySQLHandler;

/**
 * Class AccountRepositoryTest
 *
 * Tests unitarios para comprobar las consultas a la BBDD relativas a las cuentas
 *
 * @package SP\Tests
 */
class AccountRepositoryTest extends TestCase
{
    use TestCaseTrait;

    const SECURE_KEY_PASSWORD = 'syspass123';
    /**
     * @var AccountRepository
     */
    private static $accountRepository;
    /**
     * @var \PDO
     */
    private static $pdo;
    /**
     * @var DatabaseConnectionData
     */
    private static $databaseConnectionData;
    /**
     * @var DefaultConnection
     */
    private $conn;

    /**
     * @throws DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        // Instancia del contenedor de dependencias con las definiciones de los objetos necesarios
        // para la aplicación
        $builder = new ContainerBuilder();
        $builder->setDefinitionCache(new ArrayCache());
        $builder->addDefinitions(APP_ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Definitions.php');
        $dic = $builder->build();

        // Inicializar el contexto
        $context = $dic->get(ContextInterface::class);
        $context->initialize();
        $context->setConfig(new ConfigData());

        $userData = new UserLoginResponse();
        $userData->setId(1);
        $userData->setUserGroupId(1);
        $userData->setIsAdminApp(1);

        $context->setUserData($userData);

        self::$databaseConnectionData = (new DatabaseConnectionData())
            ->setDbHost('172.17.0.3')
            ->setDbName('syspass')
            ->setDbUser('root')
            ->setDbPass('syspass');

        $dic->set(ConfigData::class, $context->getConfig());
        $dic->set(DatabaseConnectionData::class, self::$databaseConnectionData);

        // Inicializar el repositorio
        self::$accountRepository = $dic->get(AccountRepository::class);
    }

    /**
     * Comprobar la eliminación de registros
     *
     * @throws SPException
     */
    public function testDelete()
    {
        // Comprobar registros iniciales
        $this->assertEquals(2, $this->conn->getRowCount('Account'));

        // Eliminar un registro y comprobar el total de registros
        $this->assertEquals(1, self::$accountRepository->delete(1));
        $this->assertEquals(1, $this->conn->getRowCount('Account'));

        // Eliminar un registro no existente
        $this->assertEquals(0, self::$accountRepository->delete(100));

        // Eliminar un registro y comprobar el total de registros
        $this->assertEquals(1, self::$accountRepository->delete(2));
        $this->assertEquals(0, $this->conn->getRowCount('Account'));
    }

    /**
     * No implementado
     */
    public function testEditRestore()
    {
        $this->markTestSkipped();
    }

    /**
     * Comprobar la modificación de una clave de cuenta
     *
     * @covers \SP\Repositories\Account\AccountRepository::getPasswordForId()
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testEditPassword()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->key = Crypt::makeSecuredKey(self::SECURE_KEY_PASSWORD);
        $accountRequest->pass = Crypt::encrypt('1234', $accountRequest->key, self::SECURE_KEY_PASSWORD);
        $accountRequest->id = 2;
        $accountRequest->userEditId = 1;
        $accountRequest->passDateChange = time() + 3600;

        // Comprobar que la modificación de la clave es correcta
        $this->assertTrue(self::$accountRepository->editPassword($accountRequest));

        $accountPassData = self::$accountRepository->getPasswordForId(2);
        $clearPassword = Crypt::decrypt($accountPassData->pass, $accountPassData->key, self::SECURE_KEY_PASSWORD);

        // Comprobar que la clave obtenida es igual a la encriptada anteriormente
        $this->assertEquals('1234', $clearPassword);

        // Comprobar que se devuelve un array vacío
        $this->assertCount(0, self::$accountRepository->getPasswordForId(10));
    }

    /**
     * No implementado
     */
    public function testCheckInUse()
    {
        $this->markTestSkipped();
    }

    /**
     * Comprobar la obtención de cuentas
     *
     * @throws SPException
     */
    public function testGetById()
    {
        $account = self::$accountRepository->getById(1);

        $this->assertInstanceOf(AccountVData::class, $account);
        $this->assertEquals(1, $account->getId());

        $this->expectException(SPException::class);

        self::$accountRepository->getById(100);
    }

    /**
     * @throws SPException
     */
    public function testUpdate()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->id = 1;
        $accountRequest->name = 'Prueba 1';
        $accountRequest->login = 'admin';
        $accountRequest->url = 'http://syspass.org';
        $accountRequest->notes = 'notas';
        $accountRequest->userEditId = 1;
        $accountRequest->passDateChange = time() + 3600;
        $accountRequest->clientId = 1;
        $accountRequest->categoryId = 1;
        $accountRequest->isPrivate = 0;
        $accountRequest->isPrivateGroup = 0;
        $accountRequest->parentId = 0;
        $accountRequest->userGroupId = 2;

        $this->assertTrue(self::$accountRepository->update($accountRequest));

        $account = self::$accountRepository->getById(1);

        $this->assertEquals($accountRequest->name, $account->getName());
        $this->assertEquals($accountRequest->login, $account->getLogin());
        $this->assertEquals($accountRequest->url, $account->getUrl());
        $this->assertEquals($accountRequest->notes, $account->getNotes());
        $this->assertEquals($accountRequest->userEditId, $account->getUserEditId());
        $this->assertEquals($accountRequest->passDateChange, $account->getPassDateChange());
        $this->assertEquals($accountRequest->clientId, $account->getClientId());
        $this->assertEquals($accountRequest->categoryId, $account->getCategoryId());
        $this->assertEquals($accountRequest->isPrivate, $account->getIsPrivate());
        $this->assertEquals($accountRequest->isPrivateGroup, $account->getIsPrivateGroup());
        $this->assertEquals($accountRequest->parentId, $account->getParentId());

        // El grupo no debe de cambiar si el usuario no tiene permisos
        $this->assertNotEquals($accountRequest->userGroupId, $account->getUserGroupId());
        $this->assertEquals(1, $account->getUserGroupId());
    }

    /**
     * No implementado
     */
    public function testCheckDuplicatedOnAdd()
    {
        $this->markTestSkipped();
    }

    /**
     * Comprobar la eliminación en lotes
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatch()
    {
        // Comprobar registros iniciales
        $this->assertEquals(2, $this->conn->getRowCount('Account'));

        $this->assertEquals(2, self::$accountRepository->deleteByIdBatch([1, 2, 100]));

        // Comprobar registros tras eliminación
        $this->assertEquals(0, $this->conn->getRowCount('Account'));
    }

    /**
     * Comprobar la búsqueda de cuentas
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('Google');
        $itemSearchData->setLimitCount(10);

        $search = self::$accountRepository->search($itemSearchData);

        $this->assertCount(3, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(2, $search['count']);
        $this->assertInstanceOf(\stdClass::class, $search[0]);
        $this->assertEquals(1, $search[0]->id);
        $this->assertEquals('Google', $search[0]->name);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('Google');
        $itemSearchData->setLimitCount(1);

        $search = self::$accountRepository->search($itemSearchData);
        $this->assertCount(2, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(2, $search['count']);
        $this->assertInstanceOf(\stdClass::class, $search[0]);
        $this->assertEquals(1, $search[0]->id);
        $this->assertEquals('Google', $search[0]->name);
    }

    /**
     * Comprobar las cuentas enlazadas
     */
    public function testGetLinked()
    {
        $filter = new QueryCondition();
        $filter->addFilter('A.parentId = 1');

        $this->assertCount(0, self::$accountRepository->getLinked($filter));
    }

    /**
     * Comprobar en incremento del contador de vistas
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws SPException
     */
    public function testIncrementViewCounter()
    {
        $accountBefore = self::$accountRepository->getById(1);

        $this->assertTrue(self::$accountRepository->incrementViewCounter(1));

        $accountAfter = self::$accountRepository->getById(1);

        $this->assertEquals($accountBefore->getCountView() + 1, $accountAfter->getCountView());
    }

    /**
     * Obtener todas las cuentas
     */
    public function testGetAll()
    {
        $this->assertCount(2, self::$accountRepository->getAll());
    }

    /**
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testUpdatePassword()
    {
        $accountRequest = new AccountPasswordRequest();
        $accountRequest->id = 2;
        $accountRequest->key = Crypt::makeSecuredKey(self::SECURE_KEY_PASSWORD);
        $accountRequest->pass = Crypt::encrypt('1234', $accountRequest->key, self::SECURE_KEY_PASSWORD);

        // Comprobar que la modificación de la clave es correcta
        $this->assertTrue(self::$accountRepository->updatePassword($accountRequest));

        $accountPassData = self::$accountRepository->getPasswordForId(2);
        $clearPassword = Crypt::decrypt($accountPassData->pass, $accountPassData->key, self::SECURE_KEY_PASSWORD);

        // Comprobar que la clave obtenida es igual a la encriptada anteriormente
        $this->assertEquals('1234', $clearPassword);
    }

    /**
     * Comprobar en incremento del contador de desencriptado
     *
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testIncrementDecryptCounter()
    {
        $accountBefore = self::$accountRepository->getById(1);

        $this->assertTrue(self::$accountRepository->incrementDecryptCounter(1));

        $accountAfter = self::$accountRepository->getById(1);

        $this->assertEquals($accountBefore->getCountDecrypt() + 1, $accountAfter->getCountDecrypt());
    }

    /**
     * Comprobar el número total de cuentas
     */
    public function testGetTotalNumAccounts()
    {
        $this->assertEquals(2, self::$accountRepository->getTotalNumAccounts()->num);
    }

    /**
     * No implementado
     */
    public function testGetDataForLink()
    {
        $this->markTestSkipped();
    }

    public function testGetForUser()
    {
//        self::$accountRepository->getForUser();
    }

    public function testGetAccountsPassData()
    {

    }

    public function testCreate()
    {

    }

    public function testGetByIdBatch()
    {

    }

    /**
     * No implementado
     */
    public function testCheckDuplicatedOnUpdate()
    {
        $this->markTestSkipped();
    }

    public function testGetPasswordHistoryForId()
    {

    }

    public function testGetByFilter()
    {

    }

    /**
     * Returns the test database connection.
     *
     * @return Connection
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo === null) {
                self::$pdo = (new MySQLHandler(self::$databaseConnectionData))->getConnection();
            }

            $this->conn = $this->createDefaultDBConnection(self::$pdo, 'syspass');
        }

        return $this->conn;
    }

    /**
     * Returns the test dataset.
     *
     * @return IDataSet
     */
    protected function getDataSet()
    {
        return $this->createMySQLXMLDataSet(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'datasets' . DIRECTORY_SEPARATOR . 'syspass.xml');
    }
}
