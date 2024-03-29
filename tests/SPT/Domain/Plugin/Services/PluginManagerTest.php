<?php
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

namespace SPT\Domain\Plugin\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Plugin\Models\Plugin as PluginModel;
use SP\Domain\Plugin\Ports\PluginRepository;
use SP\Domain\Plugin\Services\PluginManager;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\PluginGenerator;
use SPT\UnitaryTestCase;

/**
 * Class PluginManagerTest
 */
#[Group('unitary')]
class PluginManagerTest extends UnitaryTestCase
{

    private PluginRepository|MockObject $pluginRepository;
    private PluginManager               $pluginManager;

    /**
     * @throws NoSuchItemException
     * @throws ContextException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetByName()
    {
        $plugin = PluginGenerator::factory()->buildPlugin();

        $queryResult = new QueryResult([$plugin]);

        $this->pluginRepository
            ->expects($this->once())
            ->method('getByName')
            ->with('test_plugin')
            ->willReturn($queryResult);

        $out = $this->pluginManager->getByName('test_plugin');

        $this->assertEquals($plugin, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws ContextException
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetByNameWithNotFound()
    {
        $queryResult = new QueryResult([]);

        $this->pluginRepository
            ->expects($this->once())
            ->method('getByName')
            ->with('test_plugin')
            ->willReturn($queryResult);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin not found');

        $this->pluginManager->getByName('test_plugin');
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testToggleAvailable()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('toggleAvailable')
            ->with(100, true)
            ->willReturn(1);

        $this->pluginManager->toggleAvailable(100, true);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testToggleAvailableWithNotFound()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('toggleAvailable')
            ->with(100, true)
            ->willReturn(0);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin not found');

        $this->pluginManager->toggleAvailable(100, true);
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult(null, 1));

        $this->pluginManager->delete(100);
    }

    /**
     * @throws ConstraintException
     * @throws SPException
     * @throws QueryException
     */
    public function testDeleteWithNotFound()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('delete')
            ->with(100)
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin not found');

        $this->pluginManager->delete(100);
    }

    public function testGetAll()
    {
        $plugin = PluginGenerator::factory()->buildPlugin();
        $queryResult = new QueryResult([$plugin]);

        $this->pluginRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($queryResult);

        $out = $this->pluginManager->getAll();

        $this->assertEquals($plugin, $out[0]);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testToggleAvailableByName()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('toggleAvailableByName')
            ->with('test_plugin', true)
            ->willReturn(1);

        $this->pluginManager->toggleAvailableByName('test_plugin', true);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testToggleAvailableByNameWithNotFound()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('toggleAvailableByName')
            ->with('test_plugin', true)
            ->willReturn(0);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin not found');

        $this->pluginManager->toggleAvailableByName('test_plugin', true);
    }

    /**
     * @throws Exception
     */
    public function testGetByIdBatch()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getDataAsArray')
                    ->with(PluginModel::class)
                    ->willReturn([1]);

        $this->pluginRepository
            ->expects($this->once())
            ->method('getByIdBatch')
            ->with([100, 200, 300])
            ->willReturn($queryResult);

        $out = $this->pluginManager->getByIdBatch([100, 200, 300]);

        $this->assertEquals([1], $out);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testToggleEnabledByName()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('toggleEnabledByName')
            ->with('test_plugin', true)
            ->willReturn(1);

        $this->pluginManager->toggleEnabledByName('test_plugin', true);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testToggleEnabledByNameWithNotFound()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('toggleEnabledByName')
            ->with('test_plugin', true)
            ->willReturn(0);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin not found');

        $this->pluginManager->toggleEnabledByName('test_plugin', true);
    }

    /**
     * @throws ConstraintException
     * @throws Exception
     * @throws QueryException
     * @throws SPException
     */
    public function testGetEnabled()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getDataAsArray')
                    ->with(PluginModel::class)
                    ->willReturn([1]);

        $this->pluginRepository
            ->expects($this->once())
            ->method('getEnabled')
            ->willReturn($queryResult);

        $out = $this->pluginManager->getEnabled();

        $this->assertEquals([1], $out);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testToggleEnabled()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('toggleEnabled')
            ->with(100, true)
            ->willReturn(1);

        $this->pluginManager->toggleEnabled(100, true);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testToggleEnabledWithNotFound()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('toggleEnabled')
            ->with(100, true)
            ->willReturn(0);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin not found');

        $this->pluginManager->toggleEnabled(100, true);
    }


    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDeleteByIdBatch()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 3));

        $this->pluginManager->deleteByIdBatch([100, 200, 300]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDeleteByIdBatchWithException()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('deleteByIdBatch')
            ->with([100, 200, 300])
            ->willReturn(new QueryResult(null, 1));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while deleting the plugins');

        $this->pluginManager->deleteByIdBatch([100, 200, 300]);
    }


    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();

        $queryResult = new QueryResult([1]);

        $this->pluginRepository
            ->expects($this->once())
            ->method('search')
            ->with($itemSearchData)
            ->willReturn($queryResult);

        $out = $this->pluginManager->search($itemSearchData);

        $this->assertEquals($queryResult, $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $plugin = PluginGenerator::factory()->buildPlugin();

        $this->pluginRepository
            ->expects($this->once())
            ->method('update')
            ->with($plugin)
            ->willReturn(100);

        $out = $this->pluginManager->update($plugin);

        $this->assertEquals(100, $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $plugin = PluginGenerator::factory()->buildPlugin();

        $this->pluginRepository
            ->expects($this->once())
            ->method('create')
            ->with($plugin)
            ->willReturn(new QueryResult(null, 0, 100));

        $out = $this->pluginManager->create($plugin);

        $this->assertEquals(100, $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws Exception
     */
    public function testGetById()
    {
        $queryResult = $this->createMock(QueryResult::class);
        $queryResult->expects($this->once())
                    ->method('getNumRows')
                    ->willReturn(1);

        $plugin = PluginGenerator::factory()->buildPlugin();

        $queryResult->expects($this->once())
                    ->method('getData')
                    ->with(PluginModel::class)
                    ->willReturn($plugin);

        $this->pluginRepository
            ->expects($this->once())
            ->method('getById')
            ->with(100)
            ->willReturn($queryResult);

        $out = $this->pluginManager->getById(100);

        $this->assertEquals($plugin, $out);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testResetById()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('resetById')
            ->with(100)
            ->willReturn(1);

        $this->pluginManager->resetById(100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testResetByIdWithNotFound()
    {
        $this->pluginRepository
            ->expects($this->once())
            ->method('resetById')
            ->with(100)
            ->willReturn(0);

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin not found');

        $this->pluginManager->resetById(100);
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginRepository = $this->createMock(PluginRepository::class);

        $this->pluginManager = new PluginManager(
            $this->application,
            $this->pluginRepository
        );
    }
}
