<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SP\Modules\Cli\Commands\BackupCommand;
use SP\Modules\Cli\Commands\Crypt\UpdateMasterPasswordCommand;
use SP\Modules\Cli\Commands\InstallCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use function DI\autowire;
use function DI\create;

return [
    LoggerInterface::class => static function (ContainerInterface $c) {
        $logger = $c->get(Logger::class);
        $logger->pushHandler(new StreamHandler(LOG_FILE));

        return $logger;
    },
    Application::class => create(Application::class),
    OutputInterface::class => create(ConsoleOutput::class)
        ->constructor(OutputInterface::VERBOSITY_NORMAL, true),
    InputInterface::class => create(ArgvInput::class),
    InstallCommand::class => autowire(),
    BackupCommand::class => autowire(),
    UpdateMasterPasswordCommand::class => autowire()
];
