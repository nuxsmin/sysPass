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

use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Context\Session;
use SP\Core\Crypt\Csrf;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CsrfHandler;
use SP\Domain\Html\Services\MinifyCss;
use SP\Domain\Html\Services\MinifyJs;
use SP\Infrastructure\File\FileCache;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Web\Bootstrap;
use SP\Modules\Web\Controllers\Helpers\Account\AccountSearchData;
use SP\Modules\Web\Controllers\Resource\CssController;
use SP\Modules\Web\Controllers\Resource\JsController;
use SP\Modules\Web\Init;

use function DI\add;
use function DI\autowire;
use function DI\factory;
use function DI\get;

return [
    'paths' => add([
                       [Path::VIEW, FileSystem::buildPath(__DIR__, 'themes')],
                       [Path::PLUGINS, FileSystem::buildPath(__DIR__, 'plugins')],
                   ]),
    BootstrapInterface::class => autowire(Bootstrap::class),
    ModuleInterface::class => autowire(Init::class),
    CssController::class => autowire(
        CssController::class
    )->constructorParameter('minify', autowire(MinifyCss::class)),
    JsController::class => autowire(
        JsController::class
    )->constructorParameter('minify', autowire(MinifyJs::class)),
    Context::class => factory(
        static function (ConfigDataInterface $configData, SessionHandlerInterface $sessionHandler = null) {
            return new Session($sessionHandler);
        }
    ),
    CsrfHandler::class => autowire(Csrf::class)
        ->constructorParameter('context', get(Context::class)),
    AccountSearchData::class => autowire(AccountSearchData::class)
        ->constructorParameter(
            'fileCache',
            factory(static function (PathsContext $pathsContext) {
                return new FileCache(
                    FileSystem::buildPath($pathsContext[Path::CACHE], 'colors.cache')
                );
            })
        )
];
