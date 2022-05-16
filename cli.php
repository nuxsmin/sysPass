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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

use SP\Modules\Cli\Init as InitCli;

const APP_ROOT = __DIR__;
const APP_MODULE = 'cli';

$dic = require APP_ROOT.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'Base.php';

logger('------------');
logger('Boostrap:cli');

$cli = $dic->get(InitCli::class);
$cli->initialize('');