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
use SP\Core\Events\Event;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Ports\PluginCompatilityService;
use SP\Domain\Plugin\Ports\PluginLoaderService;
use SP\Domain\Plugin\Ports\PluginOperationInterface;
use SP\Domain\Plugin\Services\PluginBase;
use SP\Tests\UnitaryTestCase;

/**
 * Class PluginBaseTest
 */
#[Group('unitary')]
class PluginBaseTest extends UnitaryTestCase
{

    private PluginBase                          $pluginBase;
    private PluginOperationInterface|MockObject $pluginOperation;
    private PluginCompatilityService|MockObject $pluginCompatilityService;
    private PluginLoaderService|MockObject      $pluginLoaderService;

    public function testGetThemeDir()
    {
        $this->assertNull($this->pluginBase->getThemeDir());
    }

    public function testGetBase()
    {
        $this->assertNull($this->pluginBase->getBase());
    }

    public function testGetData()
    {
        $this->assertNull($this->pluginBase->getData());
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws CryptoException
     * @throws QueryException
     */
    public function testSaveData()
    {
        $data = (object)['a' => 'test', 'b' => ['test']];

        $this->pluginOperation
            ->expects($this->once())
            ->method('create')
            ->with(100, $data);

        $this->pluginBase->saveData(100, $data);

        $this->assertEquals($data, $this->pluginBase->getData());
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws CryptoException
     * @throws QueryException
     */
    public function testSaveDataWithUpdate()
    {
        $dataCreate = (object)['a' => 'test', 'b' => ['test']];

        $this->pluginOperation
            ->expects($this->once())
            ->method('create')
            ->with(100, $dataCreate);

        $this->pluginBase->saveData(100, $dataCreate);

        $dataUpdate = (object)['c' => 'test', 'd' => ['test']];

        $this->pluginOperation
            ->expects($this->once())
            ->method('update')
            ->with(100, $dataUpdate);

        $this->pluginBase->saveData(100, $dataUpdate);

        $this->assertEquals($dataUpdate, $this->pluginBase->getData());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginOperation = $this->createMock(PluginOperationInterface::class);
        $this->pluginCompatilityService = $this->createMock(PluginCompatilityService::class);
        $this->pluginLoaderService = $this->createMock(PluginLoaderService::class);

        $this->pluginCompatilityService
            ->expects($this->once())
            ->method('checkFor')
            ->with(self::isInstanceOf(PluginBase::class))
            ->willReturn(true);

        $this->pluginLoaderService
            ->expects($this->once())
            ->method('loadFor')
            ->with(self::isInstanceOf(PluginBase::class));

        $this->pluginBase = new class(
            $this->pluginOperation,
            $this->pluginCompatilityService,
            $this->pluginLoaderService
        ) extends PluginBase {

            public function update(string $eventType, Event $event): void
            {
                // TODO: Implement update() method.
            }

            public function getEvents(): ?string
            {
                // TODO: Implement getEventsString() method.
            }

            public function getAuthor(): ?string
            {
                // TODO: Implement getAuthor() method.
            }

            public function getVersion(): ?array
            {
                // TODO: Implement getVersion() method.
            }

            public function getCompatibleVersion(): ?array
            {
                // TODO: Implement getCompatibleVersion() method.
            }

            public function getName(): ?string
            {
                // TODO: Implement getName() method.
            }

            public function onLoad()
            {
                // TODO: Implement onLoad() method.
            }

            public function onUpgrade(string $version)
            {
                // TODO: Implement onUpgrade() method.
            }
        };
    }
}
