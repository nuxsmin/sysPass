<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Domain\Log\Ports\FileHandlerProvider;
use SP\Domain\Upgrade\Ports\UpgradeHandlerService;
use SP\Domain\Upgrade\Services\Upgrade;
use SP\Domain\Upgrade\Services\UpgradeException;
use SP\Domain\Core\Exceptions\FileException;
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
    private AppLockHandler                $appLock;

    /**
     * @throws ServiceException
     * @throws InvalidClassException
     * @throws Exception
     */
    #[Test]
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
    #[Test]
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
    #[Test]
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
    #[Test]
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
    #[Test]
    public function testUpgradeWithHandler()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $configData->expects($this->once())
                   ->method('setAppVersion')
                   ->with(\SP\Domain\Common\Providers\Version::getVersionStringNormalized())
                   ->willReturnSelf();
        $configData->expects($this->once())
                   ->method('setConfigVersion')
                   ->with(\SP\Domain\Common\Providers\Version::getVersionStringNormalized())
                   ->willReturnSelf();

        $appliedVersions = [];
        $handler = $this->createMock(UpgradeHandlerService::class);
        $handler->expects($this->exactly(2))
                ->method('apply')
                ->willReturnCallback(static function (string $version, ConfigDataInterface $passedConfig) use (
                    $configData,
                    &$appliedVersions
                ): bool {
                    self::assertSame($configData, $passedConfig);
                    $appliedVersions[] = $version;

                    return true;
                });

        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->with(UpgradeHandlerStub::class)
            ->willReturn($handler);

        $this->config->expects($this->exactly(3))
                     ->method('save')
                     ->with($configData, true);

        $this->upgrade->registerUpgradeHandler(UpgradeHandlerStub::class);
        $this->upgrade->upgrade('400.00000000', $configData);

        self::assertSame(['400.00000002', '400.00000001'], $appliedVersions);
    }

    #[Test]
    public function testUpgradeLocksAndUnlocksAroundHandlers(): void
    {
        $appLock = $this->createMock(AppLockHandler::class);
        $appLock->expects($this->once())->method('tryLock')->with(0, 'upgrade')->willReturn(true);
        $appLock->expects($this->once())->method('unlock');

        $this->upgrade = $this->buildUpgrade($appLock);

        $configData = $this->createMock(ConfigDataInterface::class);
        $handler = $this->createMock(UpgradeHandlerService::class);
        $handler->expects($this->exactly(2))
                ->method('apply')
                ->with($this->logicalOr('400.00000002', '400.00000001'), $configData)
                ->willReturn(true);

        $this->container->expects($this->exactly(2))->method('get')->with(UpgradeHandlerStub::class)->willReturn($handler);
        $this->config->expects($this->exactly(3))->method('save')->with($configData, true);

        $this->upgrade->registerUpgradeHandler(UpgradeHandlerStub::class);
        $this->upgrade->upgrade('400.00000000', $configData);
    }

    #[Test]
    public function testUpgradeFailsWhenLockCannotBeAcquired(): void
    {
        $appLock = $this->createMock(AppLockHandler::class);
        $appLock->expects($this->once())->method('tryLock')->with(0, 'upgrade')->willReturn(false);
        $appLock->expects($this->never())->method('unlock');

        $this->upgrade = $this->buildUpgrade($appLock);

        $this->expectException(UpgradeException::class);
        $this->expectExceptionMessage('Another operation is in progress');

        $this->upgrade->upgrade('400.00000000', $this->createMock(ConfigDataInterface::class));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function testUpgradeWithHandlerWithFailedApply()
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $configData->expects($this->never())->method('setAppVersion');
        $configData->expects($this->never())->method('setConfigVersion');

        $handler = $this->createMock(UpgradeHandlerService::class);
        $handler->expects($this->once())
                ->method('apply')
                ->with('400.00000002', $configData)
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

    public static function pre32VersionsProvider(): array
    {
        return [
            'empty' => [''],
            'malformed' => ['invalid'],
            'v2.1' => ['2.1'],
            'v3.0 semver' => ['3.0'],
            'v3.1 semver' => ['3.1.9'],
            'v3.0 normalized' => ['300.18111901'],
            'v3.1 normalized' => ['310.19042701'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('pre32VersionsProvider')]
    public function testUpgradeRejectsVersionsBelow32(string $version): void
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $configData->expects($this->never())->method('setAppVersion');
        $configData->expects($this->never())->method('setConfigVersion');

        $this->container->expects($this->never())->method('get');
        $this->config->expects($this->never())->method('save');

        $this->upgrade->registerUpgradeHandler(UpgradeHandlerStub::class);

        $this->expectException(UpgradeException::class);
        $this->expectExceptionMessage('Unsupported version for direct upgrade');

        $this->upgrade->upgrade($version, $configData);
    }

    public static function supported32VersionsProvider(): array
    {
        return [
            'v3.2 semver' => ['3.2'],
            'v3.2.11 semver' => ['3.2.11'],
            'v3.2.0 normalized' => ['320.20071701'],
            'v3.2.11 normalized' => ['3211.22070201'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('supported32VersionsProvider')]
    public function testUpgradeAccepts32VersionsAndPassesTargetVersionToHandlers(string $version): void
    {
        $configData = $this->createMock(ConfigDataInterface::class);
        $configData->expects($this->once())
                   ->method('setAppVersion')
                   ->with(\SP\Domain\Common\Providers\Version::getVersionStringNormalized())
                   ->willReturnSelf();
        $configData->expects($this->once())
                   ->method('setConfigVersion')
                   ->with(\SP\Domain\Common\Providers\Version::getVersionStringNormalized())
                   ->willReturnSelf();

        $appliedVersions = [];
        $handler = $this->createMock(UpgradeHandlerService::class);
        $handler->expects($this->exactly(3))
                ->method('apply')
                ->willReturnCallback(static function (string $targetVersion) use (&$appliedVersions): bool {
                    $appliedVersions[] = $targetVersion;

                    return true;
                });

        $this->container->expects($this->exactly(3))->method('get')->with(UpgradeHandlerStub::class)->willReturn($handler);
        $this->config->expects($this->exactly(4))->method('save')->with($configData, true);

        $this->upgrade->registerUpgradeHandler(UpgradeHandlerStub::class);
        $this->upgrade->upgrade($version, $configData);

        self::assertSame(['400.00000002', '400.00000001', '400.00000000'], $appliedVersions);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws FileException
     * @throws UpgradeException
     * @throws InvalidClassException
     */
    #[Test]
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

        $this->container = $this->createMock(ContainerInterface::class);
        $this->appLock = $this->createStub(AppLockHandler::class);
        $this->appLock->method('tryLock')->willReturn(true);

        $this->upgrade = $this->buildUpgrade($this->appLock);
    }

    private function buildUpgrade(AppLockHandler $appLock): Upgrade
    {
        return new Upgrade(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->createStub(FileHandlerProvider::class),
            $this->container,
            $appLock
        );
    }
}
