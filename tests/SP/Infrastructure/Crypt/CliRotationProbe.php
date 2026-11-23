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

namespace SP\Tests\Infrastructure\Crypt;

use DI\ContainerBuilder;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Modules\Cli\Init;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/** Builds the real CLI request over a caller-supplied, isolated test connection. */
final class CliRotationProbe
{
    public static function build(DbStorageHandler $storage, ConfigFileService $config, ConfigDataInterface $data): callable
    {
        $input = new ArrayInput([
            'command' => 'sp:crypt:update-master-password',
            '--currentMasterPassword' => '12345678900',
            '--masterPassword' => 'synthetic-new-master-password',
            '--update' => true,
            '--acknowledge-backup' => true,
        ]);
        $input->setInteractive(false);
        $output = new BufferedOutput();
        $container = (new ContainerBuilder())->addDefinitions(array_merge(
            CoreDefinitions::getDefinitions(REAL_APP_ROOT, 'cli'),
            DomainDefinitions::getDefinitions(),
            [Application::class => \DI\create(Application::class),
                \Psr\Log\LoggerInterface::class => new \Psr\Log\NullLogger(),
                DbStorageHandler::class => $storage, ConfigFileService::class => $config, ConfigDataInterface::class => $data,
                InputInterface::class => $input, OutputInterface::class => $output]
        ))->build();
        $container->get(Application::class)->setAutoExit(false);

        return static function () use ($container, $output): string {
            $container->get(Init::class)->initialize('');
            return $output->fetch();
        };
    }
}
