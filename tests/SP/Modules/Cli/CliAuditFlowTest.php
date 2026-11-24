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
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Ports\MasterPasswordStorageGuard;
use SP\Domain\Crypt\Services\MasterPass;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Modules\Cli\Init;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[Group('integration')]
final class CliAuditFlowTest extends CliTestCase
{
    #[Test]
    public function testSuccessfulRotationReachesCliAuditHandler(): void
    {
        $app = self::$dic->get(Application::class);
        $app->setAutoExit(false);
        self::$dic->set(InputInterface::class, new ArrayInput(['command' => 'list']));
        self::$dic->set(OutputInterface::class, new NullOutput());
        $handler = new TestHandler();
        self::$dic->get(Logger::class)->pushHandler($handler);
        self::$dic->get(Init::class)->initialize('');

        $repository = $this->createStub(Repository::class);
        $repository->method('transactionAware')->willReturnCallback(
            static function (\Closure $operation, object $service): void { $operation->call($service); }
        );
        $config = $this->createStub(ConfigService::class);
        $config->method('save')->willReturn(true);
        $service = new MasterPass(
            self::$dic->get(ConfigFileService::class),
            self::$dic->get(EventDispatcherInterface::class),
            self::$dic->get(Context::class),
            $config,
            $this->createStub(AccountMasterPasswordService::class),
            $this->createStub(CustomFieldCryptService::class),
            $repository,
            $this->createStub(MasterPasswordStorageGuard::class)
        );
        $service->changeMasterPassword(new UpdateMasterPassRequest('old synthetic', 'new synthetic', 'hash'));
        self::assertTrue($handler->hasRecordThatContains('update.masterPassword.start', Level::Debug));
        self::assertTrue($handler->hasRecordThatContains('update.masterPassword.end', Level::Debug));
    }
}
