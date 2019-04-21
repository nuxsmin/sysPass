<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Services\Plugin;

use Closure;
use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Plugin\PluginOperation;
use SP\Repositories\NoSuchItemException;
use SP\Services\Plugin\PluginDataService;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class PluginOperationTest
 *
 * @package SP\Tests\Services\Plugin
 */
class PluginOperationTest extends DatabaseTestCase
{

    /**
     * @var Closure
     */
    private static $pluginOperation;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_plugin.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$pluginOperation = function ($name) use ($dic) {
            return new PluginOperation($dic->get(PluginDataService::class), $name);
        };
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testUpdate()
    {
        /** @var PluginOperation $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Authenticator');

        $data = [1, 2, 3];

        $this->assertEquals(1, $pluginOperation->update(1, $data));
        $this->assertEquals($data, $pluginOperation->get(1));

        $data = new stdClass();
        $data->id = 1;
        $data->name = 'test';
        $data->test = new stdClass();

        $this->assertEquals(1, $pluginOperation->update(1, $data));
        $this->assertEquals($data, $pluginOperation->get(1));
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testUpdateUnknown()
    {
        /** @var PluginOperation $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Authenticator');

        $data = [1, 2, 3];

        $this->assertEquals(0, $pluginOperation->update(4, $data));
        $this->assertNull($pluginOperation->get(4));

    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testUpdateWrongPlugin()
    {
        /** @var PluginOperation $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Test');

        $data = [1, 2, 3];

        $this->assertEquals(0, $pluginOperation->update(1, $data));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testDelete()
    {
        $this->assertTableRowCount('PluginData', 4);

        /** @var PluginOperation $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Authenticator');
        $pluginOperation->delete(1);

        $this->assertTableRowCount('PluginData', 3);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testDeleteUnknown()
    {
        /** @var PluginOperation $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Authenticator');

        $this->expectException(NoSuchItemException::class);

        $pluginOperation->delete(4);
    }


    public function testGet()
    {
        $this->markTestSkipped('Already tested');
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testGetUnknown()
    {
        /** @var PluginOperation $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Authenticator');

        $this->assertNull($pluginOperation->get(4));
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testCreate()
    {
        /** @var PluginOperation $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Authenticator');

        $data = new stdClass();
        $data->id = 1;
        $data->name = 'test';
        $data->test = new stdClass();

        $pluginOperation->create(4, $data);

        $this->assertEquals($data, $pluginOperation->get(4));
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testCreateDuplicated()
    {
        /** @var PluginOperation $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Authenticator');

        $data = new stdClass();
        $data->id = 1;
        $data->name = 'test';
        $data->test = new stdClass();

        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, $pluginOperation->create(2, $data));
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testCreateWrongPlugin()
    {
        /** @var PluginOperation $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Test');

        $data = new stdClass();
        $data->id = 1;
        $data->name = 'test';
        $data->test = new stdClass();

        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, $pluginOperation->create(2, $data));
    }
}
