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

use SP\Domain\Api\Ports\ApiRequestService;
use SP\Domain\Api\Services\ApiRequest;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Modules\Api\Bootstrap;
use SP\Modules\Api\Init;

use function DI\autowire;
use function DI\factory;

const MODULE_PATH = __DIR__;
const PLUGINS_PATH = MODULE_PATH . DIRECTORY_SEPARATOR . 'plugins';

return [
    ApiRequestService::class => factory([ApiRequest::class, 'buildFromRequest']),
    BootstrapInterface::class => autowire(Bootstrap::class),
    ModuleInterface::class => autowire(Init::class)
];
