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

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ClientData;
use SP\DataModel\ItemSearchData;
use SP\Mvc\Model\QueryCondition;
use SP\Repositories\Client\ClientRepository;
use SP\Repositories\DuplicatedItemException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class ClientRepositoryTest
 *
 * Tests de integración para comprobar las consultas a la BBDD relativas a los clientes
 *
 * @package SP\Tests
 */
class ClientRepositoryTest extends DatabaseTestCase
{
    /**
     * @var ClientRepository
     */
    private static $clientRepository;

    /**
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \DI\DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$clientRepository = $dic->get(ClientRepository::class);
    }

    /**
     * Comprobar los resultados de obtener los cliente por nombre
     */
    public function testGetByName()
    {
        $client = self::$clientRepository->getByName('Amazon');

        $this->assertCount(0, $client);

        $client = self::$clientRepository->getByName('Google');

        $this->assertEquals(1, $client->getId());
        $this->assertEquals('Google Inc.', $client->getDescription());

        $client = self::$clientRepository->getByName('Apple');

        $this->assertEquals(2, $client->getId());
        $this->assertEquals('Apple Inc.', $client->getDescription());

        // Se comprueba que el hash generado es el mismo en para el nombre 'Web'
        $client = self::$clientRepository->getByName(' google. ');

        $this->assertEquals(1, $client->getId());
        $this->assertEquals('Google Inc.', $client->getDescription());
    }

    /**
     * Comprobar la búsqueda mediante texto
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('google');

        $result = self::$clientRepository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(ClientData::class, $data[0]);
        $this->assertEquals(1, $data[0]->id);
        $this->assertEquals('Google Inc.', $data[0]->description);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $result = self::$clientRepository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * Comprobar los resultados de obtener los clientes por Id
     */
    public function testGetById()
    {
        $client = self::$clientRepository->getById(10);

        $this->assertCount(0, $client);

        $client = self::$clientRepository->getById(1);

        $this->assertEquals('Google', $client->getName());
        $this->assertEquals('Google Inc.', $client->getDescription());

        $client = self::$clientRepository->getById(2);

        $this->assertEquals('Apple', $client->getName());
        $this->assertEquals('Apple Inc.', $client->getDescription());
    }

    /**
     * Comprobar la obtención de todas las client
     */
    public function testGetAll()
    {
        $count = $this->conn->getRowCount('Client');

        $results = self::$clientRepository->getAll();

        $this->assertCount($count, $results);

        $this->assertInstanceOf(ClientData::class, $results[0]);
        $this->assertEquals('Apple', $results[0]->getName());

        $this->assertInstanceOf(ClientData::class, $results[1]);
        $this->assertEquals('Google', $results[1]->getName());

        $this->assertInstanceOf(ClientData::class, $results[2]);
        $this->assertEquals('Microsoft', $results[2]->getName());
    }

    /**
     * Comprobar la actualización de clientes
     *
     * @covers \SP\Repositories\Client\ClientRepository::checkDuplicatedOnUpdate()
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Repositories\DuplicatedItemException
     */
    public function testUpdate()
    {
        $clientData = new ClientData();
        $clientData->id = 1;
        $clientData->name = 'Cliente prueba';
        $clientData->description = 'Descripción cliente prueba';

        self::$clientRepository->update($clientData);

        $category = self::$clientRepository->getById(1);

        $this->assertEquals($category->getName(), $clientData->name);
        $this->assertEquals($category->getDescription(), $clientData->description);

        // Comprobar la a actualización con un nombre duplicado comprobando su hash
        $clientData = new ClientData();
        $clientData->id = 1;
        $clientData->name = ' apple.';

        $this->expectException(DuplicatedItemException::class);

        self::$clientRepository->update($clientData);
    }

    /**
     * Comprobar la eliminación de clientes
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatch()
    {
        $countBefore = $this->conn->getRowCount('Client');

        $this->assertEquals(1, self::$clientRepository->deleteByIdBatch([3]));

        $countAfter = $this->conn->getRowCount('Client');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar clientes usados
        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$clientRepository->deleteByIdBatch([1, 2, 3]));
    }

    /**
     * Comprobar la creación de clientes
     *
     * @covers \SP\Repositories\Client\ClientRepository::checkDuplicatedOnAdd()
     * @throws DuplicatedItemException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreate()
    {
        $countBefore = $this->conn->getRowCount('Client');

        $clientData = new ClientData();
        $clientData->name = 'Cliente prueba';
        $clientData->description = 'Descripción prueba';
        $clientData->isGlobal = 1;

        $id = self::$clientRepository->create($clientData);

        // Comprobar que el Id devuelto corresponde con el cliente creado
        $client = self::$clientRepository->getById($id);

        $this->assertEquals($clientData->name, $client->getName());
        $this->assertEquals($clientData->isGlobal, $client->getIsGlobal());

        $countAfter = $this->conn->getRowCount('Client');

        $this->assertEquals($countBefore + 1, $countAfter);
    }

    /**
     * Comprobar la eliminación de clientes por Id
     *
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testDelete()
    {
        $countBefore = $this->conn->getRowCount('Client');

        $this->assertEquals(1, self::$clientRepository->delete(3));

        $countAfter = $this->conn->getRowCount('Client');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar clientes usados
        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$clientRepository->delete(2));
    }

    /**
     * Comprobar la obtención de clientes por Id en lote
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $this->assertCount(3, self::$clientRepository->getByIdBatch([1, 2, 3]));
        $this->assertCount(3, self::$clientRepository->getByIdBatch([1, 2, 3, 4, 5]));
        $this->assertCount(0, self::$clientRepository->getByIdBatch([]));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllForFilter()
    {
        $filter = new QueryCondition();
        $filter->addFilter('Account.isPrivate = 0');

        $this->assertCount(3, self::$clientRepository->getAllForFilter($filter));
    }
}
