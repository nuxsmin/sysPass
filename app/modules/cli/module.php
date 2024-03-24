<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Modules\Cli\Init;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function DI\autowire;
use function DI\create;

const MODULE_PATH = __DIR__;
const PLUGINS_PATH = MODULE_PATH . DIRECTORY_SEPARATOR . 'plugins';

return [
    LoggerInterface::class => static fn(Logger $logger) => $logger->pushHandler(new StreamHandler(LOG_FILE)),
    Application::class => create(Application::class),
    OutputInterface::class => create(ConsoleOutput::class)
        ->constructor(OutputInterface::VERBOSITY_NORMAL, true),
    InputInterface::class => create(ArgvInput::class),
    ModuleInterface::class => autowire(Init::class)
];
