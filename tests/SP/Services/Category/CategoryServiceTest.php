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

namespace SP\Tests\Services\Category;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\Category\CategoryService;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class CategoryServiceTest
 *
 * @package SP\Tests\Services\Category
 */
class CategoryServiceTest extends DatabaseTestCase
{
    /**
     * @var CategoryService
     */
    private static $service;

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
        self::$service = $dic->get(CategoryService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('linux');

        $result = self::$service->search($itemSearchData);

        $data = $result->getDataAsArray();

        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('Linux server', $data[0]->description);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $this->assertEquals(0, self::$service->search($itemSearchData)->getNumRows());
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $data = self::$service->getByName('Web');

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Web sites', $data->getDescription());

        $data = self::$service->getByName('Linux');

        $this->assertEquals(2, $data->getId());
        $this->assertEquals('Linux server', $data->getDescription());

        // Se comprueba que el hash generado es el mismo en para el nombre 'Web'
        $data = self::$service->getByName(' web. ');

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Web sites', $data->getDescription());

        $this->expectException(NoSuchItemException::class);

        self::$service->getByName('Prueba');
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllBasic()
    {
        $count = $this->conn->getRowCount('Category');

        $data = self::$service->getAllBasic();

        $this->assertCount($count, $data);

        $this->assertInstanceOf(CategoryData::class, $data[0]);
        $this->assertEquals('Linux', $data[0]->getName());

        $this->assertInstanceOf(CategoryData::class, $data[1]);
        $this->assertEquals('SSH', $data[1]->getName());

        $this->assertInstanceOf(CategoryData::class, $data[2]);
        $this->assertEquals('Web', $data[2]->getName());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testDeleteByIdBatch()
    {
        $countBefore = $this->conn->getRowCount('Category');

        $this->assertEquals(1, self::$service->deleteByIdBatch([3]));

        $countAfter = $this->conn->getRowCount('Category');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar categorías usadas
        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$service->deleteByIdBatch([1, 2, 3]));
    }

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreate()
    {
        $countBefore = $this->conn->getRowCount('Category');

        $data = new CategoryData();
        $data->name = 'Categoría prueba';
        $data->description = 'Descripción prueba';

        $id = self::$service->create($data);

        // Comprobar que el Id devuelto corresponde con la categoría creada
        $result = self::$service->getById($id);

        $this->assertEquals($data->name, $result->getName());
        $this->assertEquals($data->description, $result->getDescription());

        $countAfter = $this->conn->getRowCount('Category');

        $this->assertEquals($countBefore + 1, $countAfter);

        $this->expectException(DuplicatedItemException::class);

        self::$service->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdate()
    {
        $data = new CategoryData();
        $data->id = 1;
        $data->name = 'Web prueba';
        $data->description = 'Descripción web prueba';

        self::$service->update($data);

        $result = self::$service->getById(1);

        $this->assertEquals($data->name, $result->getName());
        $this->assertEquals($data->description, $result->getDescription());

        // Comprobar la a actualización con un nombre duplicado comprobando su hash
        $data = new CategoryData();
        $data->id = 1;
        $data->name = ' linux.';

        $this->expectException(DuplicatedItemException::class);

        self::$service->update($data);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetById()
    {
        $data = self::$service->getById(1);

        $this->assertEquals('Web', $data->getName());
        $this->assertEquals('Web sites', $data->getDescription());

        $data = self::$service->getById(2);

        $this->assertEquals('Linux', $data->getName());
        $this->assertEquals('Linux server', $data->getDescription());

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $countBefore = $this->conn->getRowCount('Category');

        self::$service->delete(3);

        $countAfter = $this->conn->getRowCount('Category');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar categorías usadas
        $this->expectException(ConstraintException::class);

        self::$service->delete(2);

        $this->expectException(NoSuchItemException::class);

        self::$service->delete(10);
    }
}
