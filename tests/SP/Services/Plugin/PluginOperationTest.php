<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Services\Plugin;

use Closure;
use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Ports\PluginOperationInterface;
use SP\Domain\Plugin\Services\PluginData;
use SP\Domain\Plugin\Services\PluginOperation;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
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
    public static function setUpBeforeClass(): void
    {
        $dic = setupContext();

        self::$loadFixtures = true;

        // Inicializar el servicio
        self::$pluginOperation = function ($name) use ($dic) {
            return new PluginOperation($dic->get(PluginData::class), $name);
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
        /** @var \SP\Domain\Plugin\Ports\PluginOperationInterface $pluginOperation */
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
        /** @var \SP\Domain\Plugin\Ports\PluginOperationInterface $pluginOperation */
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
        /** @var PluginOperationInterface $pluginOperation */
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
        $this->assertEquals(4, self::getRowCount('PluginData'));

        /** @var PluginOperationInterface $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Authenticator');
        $pluginOperation->delete(1);

        $this->assertEquals(3, self::getRowCount('PluginData'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testDeleteUnknown()
    {
        /** @var PluginOperationInterface $pluginOperation */
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
        /** @var PluginOperationInterface $pluginOperation */
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
        /** @var PluginOperationInterface $pluginOperation */
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
        /** @var \SP\Domain\Plugin\Ports\PluginOperationInterface $pluginOperation */
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
        /** @var PluginOperationInterface $pluginOperation */
        $pluginOperation = self::$pluginOperation->call($this, 'Test');

        $data = new stdClass();
        $data->id = 1;
        $data->name = 'test';
        $data->test = new stdClass();

        $this->expectException(ConstraintException::class);

        $this->assertEquals(1, $pluginOperation->create(2, $data));
    }
}
