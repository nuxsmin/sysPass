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
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
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
    private static $repository;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(ClientRepository::class);
    }

    /**
     * Comprobar los resultados de obtener los cliente por nombre
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $this->assertNull(self::$repository->getByName('Amazon')->getData());

        $data = self::$repository->getByName('Google')->getData();

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Google Inc.', $data->getDescription());

        $data = self::$repository->getByName('Apple')->getData();

        $this->assertEquals(2, $data->getId());
        $this->assertEquals('Apple Inc.', $data->getDescription());

        // Se comprueba que el hash generado es el mismo en para el nombre 'Web'
        $data = self::$repository->getByName(' google. ')->getData();

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Google Inc.', $data->getDescription());
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

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(ClientData::class, $data[0]);
        $this->assertEquals(1, $data[0]->id);
        $this->assertEquals('Google Inc.', $data[0]->description);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * Comprobar los resultados de obtener los clientes por Id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $this->assertNull(self::$repository->getById(10)->getData());

        $data = self::$repository->getById(1)->getData();

        $this->assertEquals('Google', $data->getName());
        $this->assertEquals('Google Inc.', $data->getDescription());

        $data = self::$repository->getById(2)->getData();

        $this->assertEquals('Apple', $data->getName());
        $this->assertEquals('Apple Inc.', $data->getDescription());
    }

    /**
     * Comprobar la obtención de todas las client
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $count = $this->conn->getRowCount('Client');

        $results = self::$repository->getAll();
        /** @var ClientData[] $data */
        $data = $results->getDataAsArray();

        $this->assertCount($count, $data);

        $this->assertInstanceOf(ClientData::class, $data[0]);
        $this->assertEquals('Apple', $data[0]->getName());

        $this->assertInstanceOf(ClientData::class, $data[1]);
        $this->assertEquals('Google', $data[1]->getName());

        $this->assertInstanceOf(ClientData::class, $data[2]);
        $this->assertEquals('Microsoft', $data[2]->getName());
    }

    /**
     * Comprobar la actualización de clientes
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function testUpdate()
    {
        $data = new ClientData();
        $data->id = 1;
        $data->name = 'Cliente prueba';
        $data->description = 'Descripción cliente prueba';

        self::$repository->update($data);

        $result = self::$repository->getById(1)->getData();

        $this->assertEquals($data->name, $result->getName());
        $this->assertEquals($data->description, $result->getDescription());

        // Comprobar la a actualización con un nombre duplicado comprobando su hash
        $data = new ClientData();
        $data->id = 1;
        $data->name = ' apple.';

        $this->expectException(DuplicatedItemException::class);

        self::$repository->update($data);
    }

    /**
     * Comprobar la eliminación de clientes
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $countBefore = $this->conn->getRowCount('Client');

        $this->assertEquals(1, self::$repository->deleteByIdBatch([3]));

        $countAfter = $this->conn->getRowCount('Client');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar clientes usados
        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$repository->deleteByIdBatch([1, 2, 3]));
    }

    /**
     * Comprobar la creación de clientes
     *
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function testCreate()
    {
        $countBefore = $this->conn->getRowCount('Client');

        $data = new ClientData();
        $data->name = 'Cliente prueba';
        $data->description = 'Descripción prueba';
        $data->isGlobal = 1;

        $id = self::$repository->create($data);

        // Comprobar que el Id devuelto corresponde con el cliente creado
        /** @var ClientData $result */
        $result = self::$repository->getById($id)->getData();

        $this->assertEquals($data->name, $result->getName());
        $this->assertEquals($data->isGlobal, $result->getIsGlobal());

        $countAfter = $this->conn->getRowCount('Client');

        $this->assertEquals($countBefore + 1, $countAfter);

        $this->expectException(DuplicatedItemException::class);

        self::$repository->create($data);
    }

    /**
     * Comprobar la eliminación de clientes por Id
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDelete()
    {
        $countBefore = $this->conn->getRowCount('Client');

        $this->assertEquals(1, self::$repository->delete(3));

        $countAfter = $this->conn->getRowCount('Client');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar clientes usados
        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$repository->delete(2));
    }

    /**
     * Comprobar la obtención de clientes por Id en lote
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $this->assertCount(3, self::$repository->getByIdBatch([1, 2, 3]));
        $this->assertCount(3, self::$repository->getByIdBatch([1, 2, 3, 4, 5]));
        $this->assertCount(0, self::$repository->getByIdBatch([]));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllForFilter()
    {
        $filter = new QueryCondition();
        $filter->addFilter('Account.isPrivate = 0');

        $this->assertEquals(3, self::$repository->getAllForFilter($filter)->getNumRows());
    }
}
