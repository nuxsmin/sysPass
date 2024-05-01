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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Context\ContextException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Plugin\Models\PluginData as PluginDataModel;
use SP\Domain\Plugin\Ports\PluginDataRepository;
use SP\Domain\Plugin\Services\PluginData;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Tests\Generators\PluginDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class PluginDataTest
 */
#[Group('unitary')]
class PluginDataTest extends UnitaryTestCase
{

    private PluginData                      $pluginData;
    private PluginDataRepository|MockObject $pluginDataRepository;
    private CryptInterface|MockObject       $crypt;

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->pluginDataRepository
            ->expects($this->once())
            ->method('delete')
            ->with('test_plugin')
            ->willReturn(new QueryResult(null, 1));

        $this->pluginData->delete('test_plugin');
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteWithException()
    {
        $this->pluginDataRepository
            ->expects($this->once())
            ->method('delete')
            ->with('test_plugin')
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin\'s data not found');

        $this->pluginData->delete('test_plugin');
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteByItemId()
    {
        $this->pluginDataRepository
            ->expects($this->once())
            ->method('deleteByItemId')
            ->with('test_plugin', 100)
            ->willReturn(new QueryResult(null, 1));

        $this->pluginData->deleteByItemId('test_plugin', 100);
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function testDeleteByItemIdWithException()
    {
        $this->pluginDataRepository
            ->expects($this->once())
            ->method('deleteByItemId')
            ->with('test_plugin', 100)
            ->willReturn(new QueryResult(null, 0));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin\'s data not found');

        $this->pluginData->deleteByItemId('test_plugin', 100);
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws CryptException
     * @throws QueryException
     * @throws ContextException
     */
    public function testUpdate()
    {
        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'super_secret');

        $pluginData = PluginDataGenerator::factory()->buildPluginData();

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with($pluginData->getData(), self::anything(), 'super_secret')
            ->willReturn('encrypt_data');

        $this->pluginDataRepository
            ->expects($this->once())
            ->method('update')
            ->with(
                self::callback(function (PluginDataModel $pluginDataModel) use ($pluginData) {
                    return $pluginDataModel->getData() !== $pluginData->getData()
                           && $pluginDataModel->getKey() !== $pluginData->getKey()
                           && $pluginDataModel->getName() === $pluginData->getName()
                           && $pluginDataModel->getItemId() === $pluginData->getItemId();
                })
            )
            ->willReturn(1);

        $this->pluginData->update($pluginData);
    }

    /**
     * @throws ServiceException
     * @throws ContextException
     * @throws ConstraintException
     * @throws CryptException
     * @throws QueryException
     */
    public function testCreate()
    {
        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'super_secret');

        $pluginData = PluginDataGenerator::factory()->buildPluginData();

        $this->crypt
            ->expects($this->once())
            ->method('encrypt')
            ->with($pluginData->getData(), self::anything(), 'super_secret')
            ->willReturn('encrypt_data');

        $this->pluginDataRepository
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(function (PluginDataModel $pluginDataModel) use ($pluginData) {
                    return $pluginDataModel->getData() !== $pluginData->getData()
                           && $pluginDataModel->getKey() !== $pluginData->getKey()
                           && $pluginDataModel->getName() === $pluginData->getName()
                           && $pluginDataModel->getItemId() === $pluginData->getItemId();
                })
            )
            ->willReturn(new QueryResult());

        $this->pluginData->create($pluginData);
    }

    /**
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws ContextException
     * @throws ConstraintException
     * @throws CryptException
     * @throws QueryException
     */
    public function testGetByItemId()
    {
        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'super_secret');

        $pluginData = PluginDataGenerator::factory()->buildPluginData();

        $queryResult = new QueryResult([$pluginData]);

        $this->pluginDataRepository
            ->expects($this->once())
            ->method('getByItemId')
            ->with('test_plugin', 100)
            ->willReturn($queryResult);

        $this->crypt
            ->expects($this->once())
            ->method('decrypt')
            ->with($pluginData->getData(), $pluginData->getKey(), 'super_secret')
            ->willReturn('plain_data');

        $out = $this->pluginData->getByItemId('test_plugin', 100);

        $this->assertEquals('plain_data', $out->getData());
    }

    /**
     * @throws ConstraintException
     * @throws CryptException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws ServiceException
     */
    public function testGetByItemIdWithNoRows()
    {
        $this->pluginDataRepository
            ->expects($this->once())
            ->method('getByItemId')
            ->with('test_plugin', 100)
            ->willReturn(new QueryResult([]));

        $this->crypt
            ->expects($this->never())
            ->method('decrypt');

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin\'s data not found');

        $this->pluginData->getByItemId('test_plugin', 100);
    }

    /**
     * @throws NoSuchItemException
     * @throws ContextException
     * @throws ServiceException
     * @throws CryptException
     */
    public function testGetByName()
    {
        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'super_secret');

        $pluginData = PluginDataGenerator::factory()->buildPluginData();

        $queryResult = new QueryResult([$pluginData, $pluginData]);

        $this->pluginDataRepository
            ->expects($this->once())
            ->method('getByName')
            ->with('test_plugin')
            ->willReturn($queryResult);

        $this->crypt
            ->expects($this->exactly(2))
            ->method('decrypt')
            ->with($pluginData->getData(), $pluginData->getKey(), 'super_secret')
            ->willReturn('plain_data');

        $out = $this->pluginData->getByName('test_plugin');

        $this->assertCount(2, $out);
        $this->assertEquals('plain_data', $out[0]->getData());
        $this->assertEquals('plain_data', $out[1]->getData());
    }

    /**
     * @throws CryptException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function testGetByNameWithNoRows()
    {
        $queryResult = new QueryResult([]);

        $this->pluginDataRepository
            ->expects($this->once())
            ->method('getByName')
            ->with('test_plugin')
            ->willReturn($queryResult);

        $this->crypt
            ->expects($this->never())
            ->method('decrypt');

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage('Plugin\'s data not found');

        $this->pluginData->getByName('test_plugin');
    }

    /**
     * @throws ServiceException
     * @throws ContextException
     * @throws ConstraintException
     * @throws CryptException
     * @throws SPException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $this->context->setTrasientKey(Context::MASTER_PASSWORD_KEY, 'super_secret');

        $pluginData = PluginDataGenerator::factory()->buildPluginData();

        $queryResult = new QueryResult([$pluginData, $pluginData]);

        $this->pluginDataRepository
            ->expects($this->once())
            ->method('getAll')
            ->willReturn($queryResult);

        $this->crypt
            ->expects($this->exactly(2))
            ->method('decrypt')
            ->with($pluginData->getData(), $pluginData->getKey(), 'super_secret')
            ->willReturn('plain_data');

        $out = $this->pluginData->getAll();

        $this->assertCount(2, $out);
        $this->assertEquals('plain_data', $out[0]->getData());
        $this->assertEquals('plain_data', $out[1]->getData());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginDataRepository = $this->createMock(PluginDataRepository::class);
        $this->crypt = $this->createMock(CryptInterface::class);

        $this->pluginData = new PluginData($this->application, $this->pluginDataRepository, $this->crypt);
    }
}
