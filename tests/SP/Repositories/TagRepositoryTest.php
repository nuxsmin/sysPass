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
use SP\DataModel\TagData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\Tag\TagRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class TagRepositoryTest
 *
 * Tests de integración para comprobar las consultas a la BBDD relativas a las etiquetas
 *
 * @package SP\Tests
 */
class TagRepositoryTest extends DatabaseTestCase
{
    /**
     * @var TagRepository
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
        self::$repository = $dic->get(TagRepository::class);
    }

    /**
     * Comprobar la búsqueda mediante texto
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('www');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(1, $data[0]->id);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * Comprobar los resultados de obtener las etiquetas por Id
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testGetById()
    {
        $this->assertNull(self::$repository->getById(10)->getData());

        /** @var CategoryData $data */
        $data = self::$repository->getById(1)->getData();

        $this->assertEquals('www', $data->getName());

        /** @var CategoryData $data */
        $data = self::$repository->getById(2)->getData();

        $this->assertEquals('windows', $data->getName());
    }

    /**
     * Comprobar la obtención de todas las etiquetas
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testGetAll()
    {
        $count = $this->conn->getRowCount('Tag');

        $results = self::$repository->getAll();

        $this->assertCount($count, $results);

        $this->assertInstanceOf(TagData::class, $results[0]);
        $this->assertEquals('Linux', $results[0]->getName());

        $this->assertInstanceOf(TagData::class, $results[1]);
        $this->assertEquals('windows', $results[1]->getName());

        $this->assertInstanceOf(TagData::class, $results[2]);
        $this->assertEquals('www', $results[2]->getName());
    }

    /**
     * Comprobar la actualización de etiquetas
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdate()
    {
        $tagData = new TagData();
        $tagData->id = 1;
        $tagData->name = 'Servidor';

        self::$repository->update($tagData);

        /** @var CategoryData $data */
        $data = self::$repository->getById(1)->getData();

        $this->assertEquals($data->getName(), $tagData->name);

        // Comprobar la a actualización con un nombre duplicado comprobando su hash
        $tagData = new TagData();
        $tagData->id = 1;
        $tagData->name = ' linux.';

        $this->expectException(DuplicatedItemException::class);

        self::$repository->update($tagData);
    }

    /**
     * Comprobar la eliminación de etiquetas
     *
     * @throws SPException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(0, self::$repository->deleteByIdBatch([4]));
        $this->assertEquals(3, self::$repository->deleteByIdBatch([1, 2, 3]));

        $this->assertEquals(0, $this->conn->getRowCount('Tag'));
    }

    /**
     * Comprobar la creación de etiquetas
     *
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function testCreate()
    {
        $countBefore = $this->conn->getRowCount('Tag');

        $tagData = new TagData();
        $tagData->name = 'Core';

        $id = self::$repository->create($tagData);

        // Comprobar que el Id devuelto corresponde con la etiqueta creada
        $data = self::$repository->getById($id)->getData();

        $this->assertEquals($tagData->name, $data->getName());

        $countAfter = $this->conn->getRowCount('Tag');

        $this->assertEquals($countBefore + 1, $countAfter);
    }

    /**
     * Comprobar la eliminación de etiquetas por Id
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testDelete()
    {
        $countBefore = $this->conn->getRowCount('Tag');

        $this->assertEquals(1, self::$repository->delete(3));

        $countAfter = $this->conn->getRowCount('Tag');

        $this->assertEquals($countBefore - 1, $countAfter);

        // Comprobar la eliminación de etiquetas usadas
        $this->assertEquals(1, self::$repository->delete(1));
    }

    /**
     * Comprobar la obtención de etiquetas por Id en lote
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testGetByIdBatch()
    {
        $this->assertCount(3, self::$repository->getByIdBatch([1, 2, 3]));
        $this->assertCount(3, self::$repository->getByIdBatch([1, 2, 3, 4, 5]));
        $this->assertCount(0, self::$repository->getByIdBatch([]));
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     */
    public function testCheckInUse()
    {
        $this->assertTrue(self::$repository->checkInUse(1));
    }
}
