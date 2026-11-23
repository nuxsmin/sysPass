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
