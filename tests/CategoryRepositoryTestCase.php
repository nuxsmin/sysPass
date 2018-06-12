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

use SP\Core\Exceptions\QueryException;
use SP\DataModel\CategoryData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Category\CategoryRepository;
use SP\Repositories\DuplicatedItemException;
use SP\Storage\DatabaseConnectionData;

/**
 * Class CategoryRepositoryTest
 *
 * @package SP\Tests
 */
class CategoryRepositoryTest extends DatabaseBaseTest
{
    /**
     * @var CategoryRepository
     */
    private static $categoryRepository;

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
        self::$categoryRepository = $dic->get(CategoryRepository::class);
    }

    /**
     * Comprobar los resultados de obtener las categorías por nombre
     */
    public function testGetByName()
    {
        $category = self::$categoryRepository->getByName('Prueba');

        $this->assertCount(0, $category);

        $category = self::$categoryRepository->getByName('Web');

        $this->assertEquals(1, $category->getId());
        $this->assertEquals('Web sites', $category->getDescription());

        $category = self::$categoryRepository->getByName('Linux');

        $this->assertEquals(2, $category->getId());
        $this->assertEquals('Linux server', $category->getDescription());

        // Se comprueba que el hash generado es el mismo en para el nombre 'Web'
        $category = self::$categoryRepository->getByName(' web. ');

        $this->assertEquals(1, $category->getId());
        $this->assertEquals('Web sites', $category->getDescription());
    }

    /**
     * Comprobar la búsqueda mediante texto
     */
    public function testSearch()
    {
        $searchItemData = new ItemSearchData();
        $searchItemData->setLimitCount(10);
        $searchItemData->setSeachString('linux');

        $search = self::$categoryRepository->search($searchItemData);
        $this->assertCount(2, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(1, $search['count']);
        $this->assertEquals(2, $search[0]->id);
        $this->assertEquals('Linux server', $search[0]->description);

        $searchItemData = new ItemSearchData();
        $searchItemData->setLimitCount(10);
        $searchItemData->setSeachString('prueba');

        $search = self::$categoryRepository->search($searchItemData);
        $this->assertCount(1, $search);
        $this->assertArrayHasKey('count', $search);
        $this->assertEquals(0, $search['count']);
    }

    /**
     * Comprobar los resultados de obtener las categorías por Id
     */
    public function testGetById()
    {
        $category = self::$categoryRepository->getById(10);

        $this->assertCount(0, $category);

        $category = self::$categoryRepository->getById(1);

        $this->assertEquals('Web', $category->getName());
        $this->assertEquals('Web sites', $category->getDescription());

        $category = self::$categoryRepository->getById(2);

        $this->assertEquals('Linux', $category->getName());
        $this->assertEquals('Linux server', $category->getDescription());
    }

    /**
     * Comprobar la obtención de todas las categorías
     */
    public function testGetAll()
    {
        $count = $this->conn->getRowCount('Category');

        $results = self::$categoryRepository->getAll();

        $this->assertCount($count, $results);

        $this->assertInstanceOf(CategoryData::class, $results[0]);
        $this->assertEquals('Linux', $results[0]->getName());

        $this->assertInstanceOf(CategoryData::class, $results[1]);
        $this->assertEquals('SSH', $results[1]->getName());

        $this->assertInstanceOf(CategoryData::class, $results[2]);
        $this->assertEquals('Web', $results[2]->getName());
    }

    /**
     * Comprobar la actualización de categorías
     *
     * @covers \SP\Repositories\Category\CategoryRepository::checkDuplicatedOnUpdate()
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Repositories\DuplicatedItemException
     */
    public function testUpdate()
    {
        $categoryData = new CategoryData();
        $categoryData->id = 1;
        $categoryData->name = 'Web prueba';
        $categoryData->description = 'Descripción web prueba';

        self::$categoryRepository->update($categoryData);

        $category = self::$categoryRepository->getById(1);

        $this->assertEquals($category->getName(), $categoryData->name);
        $this->assertEquals($category->getDescription(), $categoryData->description);

        // Comprobar la a actualización con un nombre duplicado comprobando su hash
        $categoryData = new CategoryData();
        $categoryData->id = 1;
        $categoryData->name = ' linux.';

        $this->expectException(DuplicatedItemException::class);

        self::$categoryRepository->update($categoryData);
    }

    /**
     * Comprobar la eliminación de categorías
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatch()
    {
        $countBefore = $this->conn->getRowCount('Category');

        $this->assertEquals(1, self::$categoryRepository->deleteByIdBatch([3]));

        $countAfter = $this->conn->getRowCount('Category');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar categorías usadas
        $this->expectException(QueryException::class);

        $this->assertEquals(1, self::$categoryRepository->deleteByIdBatch([1, 2, 3]));
    }

    /**
     * Comprobar la creación de categorías
     *
     * @covers \SP\Repositories\Category\CategoryRepository::checkDuplicatedOnAdd()
     * @throws DuplicatedItemException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreate()
    {
        $countBefore = $this->conn->getRowCount('Category');

        $categoryData = new CategoryData();
        $categoryData->name = 'Categoría prueba';
        $categoryData->description = 'Descripción prueba';

        $id = self::$categoryRepository->create($categoryData);

        // Comprobar que el Id devuelto corresponde con la categoría creada
        $category = self::$categoryRepository->getById($id);

        $this->assertEquals($categoryData->name, $category->getName());

        $countAfter = $this->conn->getRowCount('Category');

        $this->assertEquals($countBefore + 1, $countAfter);
    }

    /**
     * Comprobar la eliminación de categorías por Id
     *
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testDelete()
    {
        $countBefore = $this->conn->getRowCount('Category');

        $this->assertEquals(1, self::$categoryRepository->delete(3));

        $countAfter = $this->conn->getRowCount('Category');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar que se produce una excepción al tratar de eliminar categorías usadas
        $this->expectException(QueryException::class);

        $this->assertEquals(1, self::$categoryRepository->delete(2));
    }

    /**
     * Comprobar la obtención de categorías por Id en lote
     */
    public function testGetByIdBatch()
    {
        $this->assertCount(3, self::$categoryRepository->getByIdBatch([1, 2, 3]));
        $this->assertCount(3, self::$categoryRepository->getByIdBatch([1, 2, 3, 4, 5]));
        $this->assertCount(0, self::$categoryRepository->getByIdBatch([]));
    }

    /**
     * No implementado
     */
    public function testCheckInUse()
    {
        $this->markTestSkipped();
    }
}
