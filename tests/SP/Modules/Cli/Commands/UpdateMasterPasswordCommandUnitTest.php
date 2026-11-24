<?php
/*
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

namespace SP\Tests\Modules\Cli\Commands;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Modules\Cli\Commands\Crypt\UpdateMasterPasswordCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UpdateMasterPasswordCommandUnitTest
 */
#[Group('unitary')]
class UpdateMasterPasswordCommandUnitTest extends TestCase
{
    #[Test]
    public function completionAuditFailureStatesThatTheNewPasswordWasCommitted(): void
    {
        $data = $this->createConfiguredStub(ConfigDataInterface::class, ['isInstalled' => true, 'isMaintenance' => true]);
        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($data);
        $configService = $this->createStub(ConfigService::class);
        $configService->method('getByParam')->willReturn('synthetic hash');
        $service = $this->createStub(MasterPassService::class);
        $service->method('checkMasterPassword')->willReturn(true);
        $service->method('changeMasterPassword')->willThrowException(new \SP\Domain\Crypt\Exceptions\MasterPasswordChangeCommittedException());
        $command = new UpdateMasterPasswordCommand($service, $this->createStub(AccountService::class),
            $configService, $this->createStub(LoggerInterface::class), $config);
        $tester = new CommandTester($command);
        $tester->execute(['--currentMasterPassword' => 'old', '--masterPassword' => 'new', '--update' => true,
            '--acknowledge-backup' => true], ['interactive' => false]);
        self::assertSame(1, $tester->getStatusCode());
        self::assertMatchesRegularExpression('/Master password changed, but a completion audit or notification\s+failed/', $tester->getDisplay());
        self::assertMatchesRegularExpression('/Use\s+the new password/', $tester->getDisplay());
    }

    #[Test]
    public function testUpdateRequiresBackupAcknowledgement(): void
    {
        $configData = $this->createStub(ConfigDataInterface::class);
        $configData->method('isInstalled')->willReturn(true);
        $configData->method('isMaintenance')->willReturn(true);
        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);
        $masterPassService = $this->createMock(MasterPassService::class);
        $masterPassService->expects(self::never())->method('changeMasterPassword');
        $command = new UpdateMasterPasswordCommand(
            $masterPassService,
            $this->createStub(AccountService::class),
            $this->createStub(ConfigService::class),
            $this->createStub(LoggerInterface::class),
            $config
        );
        $tester = new CommandTester($command);
        $tester->execute(['--update' => null], ['interactive' => false]);
        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('backup', strtolower($tester->getDisplay()));
    }

    #[Test]
    public function testUpdateFlagSkipsInteractivePrompts(): void
    {
        $configData = $this->createStub(ConfigDataInterface::class);
        $configData->method('isInstalled')->willReturn(true);
        $configData->method('isMaintenance')->willReturn(true);

        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);

        $configService = $this->createStub(ConfigService::class);
        $configService->method('getByParam')->willReturn('hash');

        $accountService = $this->createStub(AccountService::class);
        $accountService->method('getTotalNumAccounts')->willReturn(3);

        $masterPassService = $this->createMock(MasterPassService::class);
        $masterPassService->method('checkMasterPassword')->willReturn(true);
        $masterPassService
            ->expects(self::once())
            ->method('changeMasterPassword')
            ->with(self::isInstanceOf(UpdateMasterPassRequest::class));

        $command = new UpdateMasterPasswordCommand(
            $masterPassService,
            $accountService,
            $configService,
            $this->createStub(LoggerInterface::class),
            $config
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                '--currentMasterPassword' => 'current-master-password',
                '--masterPassword' => 'new-master-password',
                '--update' => null,
                '--acknowledge-backup' => null,
            ],
            ['interactive' => true]
        );

        $output = $commandTester->getDisplay();

        self::assertSame(0, $commandTester->getStatusCode());
        self::assertStringContainsString('Master password updated', $output);
        self::assertStringNotContainsString('Please, press any key to continue', $output);
        self::assertStringNotContainsString('Aborted.', $output);
        self::assertStringContainsString('It will be updated 3 accounts', $output);
        self::assertStringNotContainsString('[WARNING] 70', $output);
    }
}
