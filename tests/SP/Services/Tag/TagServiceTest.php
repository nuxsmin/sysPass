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

namespace SP\Tests\Services\Tag;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\TagData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\ServiceException;
use SP\Services\Tag\TagService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class TagServiceTest
 *
 * @package SP\Tests\Services\Tag
 */
class TagServiceTest extends DatabaseTestCase
{
    /**
     * @var TagService
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

        self::$dataset = 'syspass_tag.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(TagService::class);
    }

    /**
     * @throws SPException
     */
    public function testDeleteByIdBatch()
    {
        self::$service->deleteByIdBatch([1, 2, 3]);

        $this->assertEquals(0, $this->conn->getRowCount('Tag'));

        $this->expectException(ServiceException::class);

        self::$service->deleteByIdBatch([4]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testDelete()
    {
        self::$service->delete(1);

        self::$service->delete(2);

        $this->assertEquals(1, $this->conn->getRowCount('Tag'));

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
        $tagData = new TagData();
        $tagData->id = 1;
        $tagData->name = 'Servidor';

        self::$service->update($tagData);

        $data = self::$service->getById(1);

        $this->assertEquals($data->getName(), $tagData->name);

        // Comprobar la a actualización con un nombre duplicado comprobando su hash
        $tagData = new TagData();
        $tagData->id = 1;
        $tagData->name = ' linux.';

        $this->expectException(DuplicatedItemException::class);

        self::$service->update($tagData);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetById()
    {
        $data = self::$service->getById(1);

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('www', $data->getName());

        $data = self::$service->getById(2);

        $this->assertEquals(2, $data->getId());
        $this->assertEquals('windows', $data->getName());

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllBasic()
    {
        $results = self::$service->getAllBasic();

        $this->assertCount(3, $results);

        $this->assertInstanceOf(TagData::class, $results[0]);
        $this->assertEquals('Linux', $results[0]->getName());

        $this->assertInstanceOf(TagData::class, $results[1]);
        $this->assertEquals('windows', $results[1]->getName());

        $this->assertInstanceOf(TagData::class, $results[2]);
        $this->assertEquals('www', $results[2]->getName());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('www');

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(1, $data[0]->id);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('prueba');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testCreate()
    {
        $tagData = new TagData();
        $tagData->name = 'Core';

        $id = self::$service->create($tagData);

        // Comprobar que el Id devuelto corresponde con la etiqueta creada
        $data = self::$service->getById($id);

        $this->assertEquals($tagData->name, $data->getName());

        $this->assertEquals(4, $this->conn->getRowCount('Tag'));
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $data = self::$service->getByName('www');

        $this->assertEquals(1, $data->getId());
        $this->assertEquals('www', $data->getName());

        $data = self::$service->getByName('windows');

        $this->assertEquals(2, $data->getId());
        $this->assertEquals('windows', $data->getName());

        $this->expectException(NoSuchItemException::class);

        self::$service->getByName('test');
    }
}
