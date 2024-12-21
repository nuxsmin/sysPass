<?php
declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Domain\Plugin\Services;

use Defuse\Crypto\Exception\CryptoException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Models\PluginData;
use SP\Domain\Plugin\Ports\PluginDataService;
use SP\Domain\Plugin\Services\PluginOperation;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\PluginDataGenerator;
use SP\Tests\Stubs\PluginDataStub;
use SP\Tests\UnitaryTestCase;

/**
 * Class PluginOperationTest
 */
#[Group('unitary')]
class PluginOperationTest extends UnitaryTestCase
{

    private PluginOperation              $pluginOperation;
    private PluginDataService|MockObject $pluginDataService;

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws CryptoException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $pluginDataStorage = new PluginDataStub(100, 'test_data');

        $this->pluginDataService
            ->expects($this->once())
            ->method('update')
            ->with(
                self::callback(function (PluginData $current) use ($pluginDataStorage) {
                    return $current->getName() === 'test_plugin'
                           && $current->getItemId() === 100
                           && $current->getData() === serialize($pluginDataStorage)
                           && $current->getKey() === null;
                })
            )
            ->willReturn(1);

        $out = $this->pluginOperation->update(100, $pluginDataStorage);

        $this->assertEquals(1, $out);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->pluginDataService
            ->expects($this->once())
            ->method('deleteByItemId')
            ->with('test_plugin', 100);

        $this->pluginOperation->delete(100);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws CryptoException
     * @throws QueryException
     */
    public function testCreate()
    {
        $pluginDataStorage = new PluginDataStub(100, 'test_data');

        $this->pluginDataService
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(function (PluginData $current) use ($pluginDataStorage) {
                    return $current->getName() === 'test_plugin'
                           && $current->getItemId() === 100
                           && $current->getData() === serialize($pluginDataStorage)
                           && $current->getKey() === null;
                })
            )
            ->willReturn(new QueryResult(null, 0, 10));

        $out = $this->pluginOperation->create(100, $pluginDataStorage);

        $this->assertEquals(10, $out);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws CryptoException
     * @throws QueryException
     */
    public function testGet()
    {
        $pluginDataStorage = new PluginDataStub(100, 'test_data');

        $pluginData = PluginDataGenerator::factory()
                                         ->buildPluginData()
                                         ->mutate(['data' => serialize($pluginDataStorage)]);

        $this->pluginDataService
            ->expects($this->once())
            ->method('getByItemId')
            ->with('test_plugin', 100)
            ->willReturn($pluginData);

        $out = $this->pluginOperation->get(100, PluginDataStub::class);

        $this->assertEquals($pluginDataStorage, $out);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws CryptoException
     * @throws QueryException
     */
    public function testGetWithNoItem()
    {
        $this->pluginDataService
            ->expects($this->once())
            ->method('getByItemId')
            ->with('test_plugin', 100)
            ->willThrowException(NoSuchItemException::error('test'));

        $out = $this->pluginOperation->get(100, PluginDataStub::class);

        $this->assertNull($out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginDataService = $this->createMock(PluginDataService::class);

        $this->pluginOperation = new PluginOperation($this->pluginDataService, 'test_plugin');
    }
}
