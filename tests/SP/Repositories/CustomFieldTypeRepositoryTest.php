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
use SP\DataModel\CustomFieldTypeData;
use SP\Repositories\CustomField\CustomFieldTypeRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class CustomFieldTypeRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class CustomFieldTypeRepositoryTest extends DatabaseTestCase
{
    /**
     * @var CustomFieldTypeRepository
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
        self::$repository = $dic->get(CustomFieldTypeRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $countBefore = $this->conn->getRowCount('CustomFieldType');

        $this->assertEquals(2, self::$repository->deleteByIdBatch([3, 4, 100]));
        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));
        $this->assertEquals($countBefore - 2, $this->conn->getRowCount('CustomFieldType'));

        $this->expectException(ConstraintException::class);

        self::$repository->deleteByIdBatch([1, 2]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $countBefore = $this->conn->getRowCount('CustomFieldType');

        $this->assertEquals(1, self::$repository->delete(3));
        $this->assertEquals(0, self::$repository->delete(100));
        $this->assertEquals($countBefore - 1, $this->conn->getRowCount('CustomFieldType'));

        $this->expectException(ConstraintException::class);

        self::$repository->delete(1);
        self::$repository->delete(2);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();

        $this->assertEquals(10, $result->getNumRows());

        /** @var CustomFieldTypeData[] $data */
        $data = $result->getDataAsArray();

        $this->assertCount(10, $data);
        $this->assertInstanceOf(CustomFieldTypeData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals('text', $data[0]->getName());
        $this->assertEquals('Texto', $data[0]->getText());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $data = new CustomFieldTypeData();
        $data->setId(10);
        $data->setName('textarea');
        $data->setText('Área de Texto');

        $result = self::$repository->getById(10);

        $this->assertEquals(1, $result->getNumRows());

        $this->assertEquals($data, $result->getData());

        $this->assertEquals(0, self::$repository->getById(11)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreate()
    {
        $data = new CustomFieldTypeData();
        $data->setId(11);
        $data->setName('prueba');
        $data->setText('Prueba');

        $this->assertEquals(11, self::$repository->create($data));

        $this->assertEquals($data, self::$repository->getById(11)->getData());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdate()
    {
        $data = new CustomFieldTypeData();
        $data->setId(10);
        $data->setName('prueba');
        $data->setText('Prueba');

        $this->assertEquals(1, self::$repository->update($data));

        $this->assertEquals($data, self::$repository->getById(10)->getData());
    }
}
