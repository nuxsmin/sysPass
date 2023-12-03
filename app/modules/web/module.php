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

use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Html\MinifyInterface;
use SP\Html\MinifyCss;
use SP\Html\MinifyJs;
use SP\Modules\Web\Bootstrap;
use SP\Modules\Web\Controllers\Resource\CssController;
use SP\Modules\Web\Controllers\Resource\JsController;
use SP\Modules\Web\Init;

use function DI\autowire;

const MODULE_PATH = __DIR__;
const VIEW_PATH = MODULE_PATH . DIRECTORY_SEPARATOR . 'themes';
const PLUGINS_PATH = MODULE_PATH . DIRECTORY_SEPARATOR . 'plugins';

return [
    BootstrapInterface::class => autowire(Bootstrap::class),
    ModuleInterface::class => autowire(Init::class),
    CssController::class => autowire(
        CssController::class
    )->constructorParameter(MinifyInterface::class, autowire(MinifyCss::class)),
    JsController::class => autowire(
        JsController::class
    )->constructorParameter(MinifyInterface::class, autowire(MinifyJs::class))

];
