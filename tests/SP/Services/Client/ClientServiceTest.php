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

namespace SP\Tests\Services\Client;

use Closure;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Context\ContextInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ClientData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\Client\ClientService;
use SP\Services\ServiceException;
use SP\Services\User\UserLoginResponse;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class ClientServiceTest
 *
 * @package SP\Tests\Services\Client
 */
class ClientServiceTest extends DatabaseTestCase
{
    /**
     * @var Closure
     */
    private static $setupUser;
    /**
     * @var ClientService
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass(): void
    {
        $dic = setupContext();

        self::$loadFixtures = true;

        // Inicializar el servicio
        self::$service = $dic->get(ClientService::class);

        self::$setupUser = function (UserLoginResponse $response) use ($dic) {
            $response->setLastUpdate(time());

            $dic->get(ContextInterface::class)->setUserData($response);
        };
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('google');

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(ClientData::class, $data[0]);
        $this->assertEquals(1, $data[0]->id);
        $this->assertEquals('Google Inc.', $data[0]->description);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllBasic()
    {
        $count = self::getRowCount('Client');

        $results = self::$service->getAllBasic();

        $this->assertCount($count, $results);

        $this->assertInstanceOf(ClientData::class, $results[0]);
        $this->assertEquals('Amazon', $results[0]->getName());

        $this->assertInstanceOf(ClientData::class, $results[1]);
        $this->assertEquals('Apple', $results[1]->getName());

        $this->assertInstanceOf(ClientData::class, $results[2]);
        $this->assertEquals('Google', $results[2]->getName());

        $this->assertInstanceOf(ClientData::class, $results[3]);
        $this->assertEquals('Microsoft', $results[3]->getName());
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetById()
    {
        $client = self::$service->getById(1);

        $this->assertEquals('Google', $client->getName());
        $this->assertEquals('Google Inc.', $client->getDescription());

        $client = self::$service->getById(2);

        $this->assertEquals('Apple', $client->getName());
        $this->assertEquals('Apple Inc.', $client->getDescription());

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllForUserAdmin()
    {
        $this->assertCount(4, self::$service->getAllForUser());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllForUser()
    {
        $userData = new UserLoginResponse();
        $userData->setId(4);

        self::$setupUser->call($this, $userData);

        $this->assertCount(2, self::$service->getAllForUser());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function testCreate()
    {
        $data = new ClientData();
        $data->name = 'Cliente prueba';
        $data->description = 'Descripción prueba';
        $data->isGlobal = 1;

        $id = self::$service->create($data);

        // Comprobar que el Id devuelto corresponde con el cliente creado
        $result = self::$service->getById($id);

        $this->assertEquals($data->name, $result->getName());
        $this->assertEquals($data->isGlobal, $result->getIsGlobal());

        $countAfter = self::getRowCount('Client');

        $this->assertEquals(5, $countAfter);

        $this->expectException(DuplicatedItemException::class);

        self::$service->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatch()
    {
        $countBefore = self::getRowCount('Client');

        self::$service->deleteByIdBatch([4]);

        $countAfter = self::getRowCount('Client');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar clientes usados
        $this->expectException(ConstraintException::class);

        self::$service->deleteByIdBatch([1, 2]);

        $this->expectException(ServiceException::class);

        self::$service->deleteByIdBatch([10]);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $data = self::$service->getByName('Google');

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Google Inc.', $data->getDescription());

        $data = self::$service->getByName('Apple');

        $this->assertEquals(2, $data->getId());
        $this->assertEquals('Apple Inc.', $data->getDescription());

        // Se comprueba que el hash generado es el mismo en para el nombre 'Web'
        $data = self::$service->getByName(' google. ');

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Google Inc.', $data->getDescription());

        $this->expectException(NoSuchItemException::class);

        self::$service->getByName('Spotify');
    }

    /**
     * @throws SPException
     */
    public function testDelete()
    {
        $countBefore = self::getRowCount('Client');

        self::$service->delete(4);

        $countAfter = self::getRowCount('Client');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar clientes usados
        $this->expectException(ConstraintException::class);

        self::$service->delete(2);

        $this->expectException(NoSuchItemException::class);

        self::$service->delete(10);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdate()
    {
        $data = new ClientData();
        $data->id = 1;
        $data->name = 'Cliente prueba';
        $data->description = 'Descripción cliente prueba';

        self::$service->update($data);

        $result = self::$service->getById(1);

        $this->assertEquals($data->name, $result->getName());
        $this->assertEquals($data->description, $result->getDescription());

        // Comprobar la a actualización con un nombre duplicado comprobando su hash
        $data = new ClientData();
        $data->id = 1;
        $data->name = ' apple.';

        $this->expectException(DuplicatedItemException::class);

        self::$service->update($data);
    }
}
