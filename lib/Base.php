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

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Infrastructure\File\FileSystem;

use function SP\getFromEnv;
use function SP\initModule;
use function SP\processException;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..'));
}

require 'BaseFunctions.php';
require FileSystem::buildPath(APP_ROOT, 'vendor', 'autoload.php');

define('APP_PATH', FileSystem::buildPath(APP_ROOT, 'app'));

$dotenv = Dotenv::createImmutable(APP_ROOT);
$dotenv->load();

defined('APP_MODULE') || define('APP_MODULE', 'web');

define('DEBUG', getFromEnv('DEBUG', false));

try {
    $moduleDefinitions = initModule(APP_MODULE);

    $containerBuilder = new ContainerBuilder();

    if (!DEBUG) {
        $cachePath = getFromEnv('CACHE_PATH', FileSystem::buildPath(APP_PATH, 'cache'));
        $containerBuilder->enableCompilation($cachePath);
        $containerBuilder->writeProxiesToFile(true, FileSystem::buildPath($cachePath, 'proxies'));
    }

    return $containerBuilder
        ->addDefinitions(
            CoreDefinitions::getDefinitions(APP_ROOT, APP_MODULE),
            DomainDefinitions::getDefinitions(),
            $moduleDefinitions
        )
        ->build();
} catch (Exception $e) {
    processException($e);

    die($e->getMessage());
}
