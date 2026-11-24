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

namespace SP\Tests\Modules\Cli;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock;
use SP\Modules\Cli\Init;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[Group('integration')]
final class CliRequestLockTest extends CliTestCase
{
    #[Test]
    public function testInstalledOrdinaryCommandAcquiresSharedLock(): void
    {
        $data = $this->createStub(ConfigDataInterface::class);
        $data->method('isInstalled')->willReturn(true);
        $data->method('getLogEvents')->willReturn([]);
        $data->method('getSiteLang')->willReturn('en_US');
        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($data);
        self::$dic->set(ConfigDataInterface::class, $data);
        self::$dic->set(ConfigFileService::class, $config);
        $lock = $this->createMock(MasterPasswordRequestLock::class);
        $lock->expects(self::once())->method('acquire');
        $lock->expects(self::once())->method('release');
        self::$dic->set(MasterPasswordRequestLock::class, $lock);
        self::$dic->set(InputInterface::class, new ArrayInput(['command' => 'list']));
        self::$dic->set(OutputInterface::class, new NullOutput());
        self::$dic->get(Application::class)->setAutoExit(false);
        self::$dic->get(Init::class)->initialize('');
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\TestWith(['sp:crypt:update-master-password'])]
    #[\PHPUnit\Framework\Attributes\TestWith(['sp:crypt:update-master-p'])]
    #[Test]
    public function testRotationCommandDoesNotAcquireSharedLock(string $command): void
    {
        $data = $this->createStub(ConfigDataInterface::class);
        $data->method('isInstalled')->willReturn(true);
        $data->method('getLogEvents')->willReturn([]);
        $data->method('getSiteLang')->willReturn('en_US');
        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($data);
        self::$dic->set(ConfigDataInterface::class, $data);
        self::$dic->set(ConfigFileService::class, $config);
        $lock = $this->createMock(MasterPasswordRequestLock::class);
        $lock->expects(self::never())->method('acquire');
        self::$dic->set(MasterPasswordRequestLock::class, $lock);
        $input = new ArrayInput(['command' => $command]);
        $input->setInteractive(false);
        self::$dic->set(InputInterface::class, $input);
        self::$dic->set(OutputInterface::class, new NullOutput());
        self::$dic->get(Application::class)->setAutoExit(false);
        self::$dic->get(Init::class)->initialize('');
    }
}
