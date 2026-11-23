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
use SP\Core\ProvidersHelper;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Modules\Cli\CliCommandHelper;
use SP\Modules\Cli\Init;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CliApplicationTest
 */
#[Group('integration')]
final class CliApplicationTest extends CliTestCase
{
    public function testCliInitializesEventHandlers(): void
    {
        $application = self::$dic->get(Application::class);
        $application->setAutoExit(false);
        self::$dic->set(InputInterface::class, new ArrayInput(['command' => 'list']));
        self::$dic->set(OutputInterface::class, new NullOutput());
        self::$dic->get(Init::class)->initialize('');
        self::assertTrue(self::$dic->get(EventDispatcherInterface::class)->has(self::$dic->get(ProvidersHelper::class)->getLogHandler()));
    }

    public function testCommandsAreRegisteredWithTheirNames(): void
    {
        $application = self::$dic->get(Application::class);
        $application->addCommands(self::$dic->get(CliCommandHelper::class)->getCommands());

        self::assertTrue($application->has('sp:install'));
        self::assertTrue($application->has('sp:backup'));
        self::assertTrue($application->has('sp:crypt:update-master-password'));
    }
}
