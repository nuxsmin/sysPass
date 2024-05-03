<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Domain\Upgrade\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Log\Ports\FileHandlerProvider;
use SP\Domain\Upgrade\Ports\UpgradeHandlerService;
use SP\Domain\Upgrade\Services\Upgrade;
use SP\Domain\Upgrade\Services\UpgradeException;
use SP\Infrastructure\File\FileException;
use SP\Tests\Stubs\UpgradeHandlerStub;
use SP\Tests\UnitaryTestCase;
use stdClass;

/**
 * Class UpgradeTest
 */
#[Group('unitary')]
class UpgradeTest extends UnitaryTestCase
{

    private MockObject|ContainerInterface $container;
    private Upgrade                       $upgrade;

    /**
     * @throws ServiceException
     * @throws InvalidClassException
     * @throws Exception
     */
    public function testRegisterUpgradeHandler()
    {
        $handler = $this->createMock(UpgradeHandlerService::class);

        $this->upgrade->registerUpgradeHandler($handler::class);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @throws ServiceException
     * @throws InvalidClassException
     */
    public function testRegisterUpgradeHandlerWithClassException()
    {
        $this->expectException(InvalidClassException::class);
        $this->expectExceptionMessage('Class does not either exist or implement UpgradeService class');

        $this->upgrade->registerUpgradeHandler(stdClass::class);
    }

    /**
     * @throws ServiceException
     * @throws InvalidClassException
     * @throws Exception
     */
    public function testRegisterUpgradeHandlerWithDuplicate()
    {
        $handler = $this->createMock(UpgradeHandlerService::class);

        $this->upgrade->registerUpgradeHandler($handler::class);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Class already registered');

        $this->upgrade->registerUpgradeHandler($handler::class);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws FileException
     * @throws UpgradeException
     */
    public function testUpgrade()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $this->config->expects($this->never())
                     ->method('save');

        $this->upgrade->upgrade('400.00000000', $configData);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws FileException
     * @throws UpgradeException
     * @throws InvalidClassException
     */
    public function testUpgradeWithHandler()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $handler = $this->createMock(UpgradeHandlerService::class);
        $handler->expects($this->exactly(2))
                ->method('apply')
                ->with('400.00000000', $configData)
                ->willReturn(true);

        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->with(UpgradeHandlerStub::class)
            ->willReturn($handler);

        $this->config->expects($this->exactly(2))
                     ->method('save')
                     ->with($configData, false);

        $this->upgrade->registerUpgradeHandler(UpgradeHandlerStub::class);
        $this->upgrade->upgrade('400.00000000', $configData);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws FileException
     * @throws UpgradeException
     * @throws InvalidClassException
     */
    public function testUpgradeWithHandlerWithFailedApply()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $handler = $this->createMock(UpgradeHandlerService::class);
        $handler->expects($this->once())
                ->method('apply')
                ->with('400.00000000', $configData)
                ->willReturn(false);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(UpgradeHandlerStub::class)
            ->willReturn($handler);

        $this->config->expects($this->never())
                     ->method('save');

        $this->upgrade->registerUpgradeHandler(UpgradeHandlerStub::class);

        $this->expectException(UpgradeException::class);
        $this->expectExceptionMessage('Error while applying the update');

        $this->upgrade->upgrade('400.00000000', $configData);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws FileException
     * @throws UpgradeException
     * @throws InvalidClassException
     */
    public function testUpgradeWithException()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $handler = $this->createMock(UpgradeHandlerService::class);
        $handler->expects($this->never())
                ->method('apply');

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(UpgradeHandlerStub::class)
            ->willThrowException(new RuntimeException('test'));

        $this->config->expects($this->never())
                     ->method('save');

        $this->upgrade->registerUpgradeHandler(UpgradeHandlerStub::class);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->upgrade->upgrade('400.00000000', $configData);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $fileHandlerProvider = $this->createMock(FileHandlerProvider::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->upgrade = new Upgrade($this->application, $fileHandlerProvider, $this->container);
    }
}
