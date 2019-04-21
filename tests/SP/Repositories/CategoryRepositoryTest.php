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
use SP\DataModel\CategoryData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Category\CategoryRepository;
use SP\Repositories\DuplicatedItemException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class CategoryRepositoryTest
 *
 * Tests de integración para comprobar las consultas a la BBDD relativas a las categorías
 *
 * @package SP\Tests
 */
class CategoryRepositoryTest extends DatabaseTestCase
{
    /**
     * @var CategoryRepository
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
        self::$repository = $dic->get(CategoryRepository::class);
    }

    /**
     * Comprobar los resultados de obtener las categorías por nombre
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $this->assertEquals(0, self::$repository->getByName('Prueba')->getNumRows());

        $result = self::$repository->getByName('Web');
        $data = $result->getData();

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Web sites', $data->getDescription());

        $result = self::$repository->getByName('Linux');
        $data = $result->getData();

        $this->assertEquals(2, $data->getId());
        $this->assertEquals('Linux server', $data->getDescription());

        // Se comprueba que el hash generado es el mismo en para el nombre 'Web'
        $result = self::$repository->getByName(' web. ');
        $data = $result->getData();

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('Web sites', $data->getDescription());
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
        $itemSearchData->setSeachString('linux');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(2, $data[0]->id);
        $this->assertEquals('Linux server', $data[0]->description);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $data);
    }

    /**
     * Comprobar los resultados de obtener las categorías por Id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $this->assertEquals(0, self::$repository->getById(10)->getNumRows());

        $result = self::$repository->getById(1);
        $data = $result->getData();

        $this->assertEquals('Web', $data->getName());
        $this->assertEquals('Web sites', $data->getDescription());

        $result = self::$repository->getById(2);
        $data = $result->getData();

        $this->assertEquals('Linux', $data->getName());
        $this->assertEquals('Linux server', $data->getDescription());
    }

    /**
     * Comprobar la obtención de todas las categorías
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $count = $this->conn->getRowCount('Category');

        $result = self::$repository->getAll();
        $this->assertEquals($count, $result->getNumRows());

        /** @var CategoryData[] $data */
        $data = $result->getDataAsArray();
        $this->assertCount($count, $data);

        $this->assertInstanceOf(CategoryData::class, $data[0]);
        $this->assertEquals('Linux', $data[0]->getName());

        $this->assertInstanceOf(CategoryData::class, $data[1]);
        $this->assertEquals('SSH', $data[1]->getName());

        $this->assertInstanceOf(CategoryData::class, $data[2]);
        $this->assertEquals('Web', $data[2]->getName());
    }

    /**
     * Comprobar la actualización de categorías
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws DuplicatedItemException
     */
    public function testUpdate()
    {
        $categoryData = new CategoryData();
        $categoryData->id = 1;
        $categoryData->name = 'Web prueba';
        $categoryData->description = 'Descripción web prueba';

        self::$repository->update($categoryData);

        $result = self::$repository->getById(1);
        $data = $result->getData();

        $this->assertEquals($categoryData->name, $data->getName());
        $this->assertEquals($categoryData->description, $data->getDescription());

        // Comprobar la a actualización con un nombre duplicado comprobando su hash
        $categoryData = new CategoryData();
        $categoryData->id = 1;
        $categoryData->name = ' linux.';

        $this->expectException(DuplicatedItemException::class);

        self::$repository->update($categoryData);
    }

    /**
     * Comprobar la eliminación de categorías
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $countBefore = $this->conn->getRowCount('Category');

        $this->assertEquals(1, self::$repository->deleteByIdBatch([3]));

        $countAfter = $this->conn->getRowCount('Category');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar categorías usadas
        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$repository->deleteByIdBatch([1, 2, 3]));
    }

    /**
     * Comprobar la creación de categorías
     *
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function testCreate()
    {
        $countBefore = $this->conn->getRowCount('Category');

        $categoryData = new CategoryData();
        $categoryData->name = 'Categoría prueba';
        $categoryData->description = 'Descripción prueba';

        $id = self::$repository->create($categoryData);

        // Comprobar que el Id devuelto corresponde con la categoría creada
        $result = self::$repository->getById($id);
        $data = $result->getData();

        $this->assertEquals($categoryData->name, $data->getName());

        $countAfter = $this->conn->getRowCount('Category');

        $this->assertEquals($countBefore + 1, $countAfter);
    }

    /**
     * Comprobar la eliminación de categorías por Id
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDelete()
    {
        $countBefore = $this->conn->getRowCount('Category');

        $this->assertEquals(1, self::$repository->delete(3));

        $countAfter = $this->conn->getRowCount('Category');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar categorías usadas
        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, self::$repository->delete(2));
    }

    /**
     * Comprobar la obtención de categorías por Id en lote
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $this->assertCount(3, self::$repository->getByIdBatch([1, 2, 3])->getDataAsArray());
        $this->assertCount(3, self::$repository->getByIdBatch([1, 2, 3, 4, 5])->getDataAsArray());
        $this->assertCount(0, self::$repository->getByIdBatch([])->getDataAsArray());
    }
}
