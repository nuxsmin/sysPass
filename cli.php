<?php
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

use Psr\Container\ContainerInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Util\FileSystem;

use function SP\logger;
use function SP\processException;

const APP_ROOT = __DIR__;
const APP_MODULE = 'cli';

try {
    $dic = FileSystem::require(FileSystem::buildPath(APP_ROOT, 'lib', 'Base.php'), ContainerInterface::class);

    logger('------------');
    logger('Boostrap:cli');

    $cli = $dic->get(ModuleInterface::class);
    $cli->initialize('');
} catch (Throwable $e) {
    processException($e);
}
