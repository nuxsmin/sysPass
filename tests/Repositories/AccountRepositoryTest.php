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

namespace SP\Tests\Repositories;

use DI\DependencyException;
use SP\Account\AccountRequest;
use SP\Account\AccountSearchFilter;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountVData;
use SP\DataModel\Dto\AccountSearchResponse;
use SP\DataModel\ItemSearchData;
use SP\Mvc\Model\QueryCondition;
use SP\Repositories\Account\AccountRepository;
use SP\Services\Account\AccountPasswordRequest;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class AccountRepositoryTest
 *
 * Tests de integración para comprobar las consultas a la BBDD relativas a las cuentas
 *
 * @package SP\Tests
 */
class AccountRepositoryTest extends DatabaseTestCase
{
    const SECURE_KEY_PASSWORD = 'syspass123';
    /**
     * @var AccountRepository
     */
    private static $accountRepository;

    /**
     * @throws DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

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
        $this->markTestSkipped('Not implemented');
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
        $this->assertEquals(1, self::$accountRepository->editPassword($accountRequest));

        $accountPassData = self::$accountRepository->getPasswordForId(2);
        $clearPassword = Crypt::decrypt($accountPassData->pass, $accountPassData->key, self::SECURE_KEY_PASSWORD);

        // Comprobar que la clave obtenida es igual a la encriptada anteriormente
        $this->assertEquals('1234', $clearPassword);

        // Comprobar que se devuelve un array vacío
        $this->assertCount(0, self::$accountRepository->getPasswordForId(10));
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

        $this->assertEquals(1, self::$accountRepository->update($accountRequest));

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
        $this->markTestSkipped('Not implemented');
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
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testSearch()
    {
        // Comprobar búsqueda con el texto Google Inc
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('Google');
        $itemSearchData->setLimitCount(10);

        $result = self::$accountRepository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(\stdClass::class, $data[0]);
        $this->assertEquals(1, $data[0]->id);
        $this->assertEquals('Google', $data[0]->name);

        // Comprobar búsqueda con el texto Apple
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('Apple');
        $itemSearchData->setLimitCount(1);

        $result = self::$accountRepository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(\stdClass::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('Apple', $data[0]->name);
    }

    /**
     * Comprobar las cuentas enlazadas
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetLinked()
    {
        $filter = new QueryCondition();
        $filter->addFilter('Account.parentId = 1');

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
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
        $this->markTestSkipped('Not implemented');
    }

    /**
     * Comprobar las cuentas devueltas para un filtro de usuario
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetForUser()
    {
        $queryCondition = new QueryCondition();
        $queryCondition->addFilter('Account.isPrivate = 1');

        $this->assertCount(0, self::$accountRepository->getForUser($queryCondition));
    }

    /**
     * Comprobar las cuentas devueltas para obtener los datos de las claves
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetAccountsPassData()
    {
        $this->assertCount(2, self::$accountRepository->getAccountsPassData());
    }

    /**
     * Comprobar la creación de una cuenta
     *
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCreate()
    {
        $accountRequest = new AccountRequest();
        $accountRequest->name = 'Prueba 2';
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
        $accountRequest->userId = 1;
        $accountRequest->userGroupId = 2;
        $accountRequest->key = Crypt::makeSecuredKey(self::SECURE_KEY_PASSWORD);
        $accountRequest->pass = Crypt::encrypt('1234', $accountRequest->key, self::SECURE_KEY_PASSWORD);

        // Comprobar registros iniciales
        $this->assertEquals(2, $this->conn->getRowCount('Account'));

        self::$accountRepository->create($accountRequest);

        // Comprobar registros finales
        $this->assertEquals(3, $this->conn->getRowCount('Account'));
    }

    /**
     * No implementado
     */
    public function testGetByIdBatch()
    {
        $this->markTestSkipped('Not implemented');
    }

    /**
     * No implementado
     */
    public function testCheckDuplicatedOnUpdate()
    {
        $this->markTestSkipped('Not implemented');
    }

    /**
     * No implementado
     */
    public function testGetPasswordHistoryForId()
    {
        $this->markTestSkipped('Not implemented');
    }

    /**
     * Comprobar la búsqueda de cuentas mediante filtros
     *
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetByFilter()
    {
        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setCategoryId(1);

        // Comprobar un Id de categoría
        $response = self::$accountRepository->getByFilter($searchFilter);

        $this->assertInstanceOf(AccountSearchResponse::class, $response);
        $this->assertEquals(1, $response->getCount());
        $this->assertCount(1, $response->getData());

        // Comprobar un Id de categoría no existente
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setCategoryId(10);

        $response = self::$accountRepository->getByFilter($searchFilter);

        $this->assertInstanceOf(AccountSearchResponse::class, $response);
        $this->assertEquals(0, $response->getCount());
        $this->assertCount(0, $response->getData());

        // Comprobar un Id de cliente
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setClientId(1);

        $response = self::$accountRepository->getByFilter($searchFilter);

        $this->assertInstanceOf(AccountSearchResponse::class, $response);
        $this->assertEquals(1, $response->getCount());
        $this->assertCount(1, $response->getData());

        // Comprobar un Id de cliente no existente
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setClientId(10);

        $response = self::$accountRepository->getByFilter($searchFilter);

        $this->assertInstanceOf(AccountSearchResponse::class, $response);
        $this->assertEquals(0, $response->getCount());
        $this->assertCount(0, $response->getData());

        // Comprobar una cadena de texto
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setCleanTxtSearch('apple.com');

        $response = self::$accountRepository->getByFilter($searchFilter);

        $this->assertInstanceOf(AccountSearchResponse::class, $response);
        $this->assertEquals(1, $response->getCount());
        $this->assertCount(1, $response->getData());
        $this->assertEquals(2, $response->getData()[0]->getId());

        // Comprobar los favoritos
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setSearchFavorites(true);

        $response = self::$accountRepository->getByFilter($searchFilter);

        $this->assertInstanceOf(AccountSearchResponse::class, $response);
        $this->assertEquals(0, $response->getCount());
        $this->assertCount(0, $response->getData());

        // Comprobar las etiquetas
        $searchFilter->reset();
        $searchFilter->setLimitCount(10);
        $searchFilter->setTagsId([1]);

        $response = self::$accountRepository->getByFilter($searchFilter);

        $this->assertInstanceOf(AccountSearchResponse::class, $response);
        $this->assertEquals(1, $response->getCount());
        $this->assertCount(1, $response->getData());
        $this->assertEquals(1, $response->getData()[0]->getId());
    }
}
