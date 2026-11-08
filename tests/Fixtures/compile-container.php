<?php

declare(strict_types=1);
/**
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

use SP\Core\Definitions\ContainerFactory;
use SP\Infrastructure\File\FileSystem;

$root = dirname(__DIR__, 2);
$module = getenv('SP_COMPILE_MODULE') ?: '';
$cacheRoot = getenv('SP_COMPILE_CACHE_ROOT') ?: '';
$runRoot = getenv('SP_COMPILE_RUN_ROOT') ?: '';

if ($module === '' || $cacheRoot === '' || $runRoot === '') {
    fwrite(STDERR, "Missing compile-container environment\n");

    exit(2);
}

define('APP_PATH', $root . DIRECTORY_SEPARATOR . 'app');
define('DEBUG', false);

require $root . '/vendor/autoload.php';

$configPath = FileSystem::buildPath($runRoot, 'config');

if (!is_dir($configPath) && !mkdir($configPath, 0777, true) && !is_dir($configPath)) {
    fwrite(STDERR, "Cannot create run configuration directory\n");

    exit(2);
}

putenv('CONFIG_PATH=' . $configPath);
putenv('CONFIG_FILE=' . FileSystem::buildPath($configPath, 'config.xml'));
putenv('LOG_FILE=' . FileSystem::buildPath($configPath, 'syspass.log'));

require $root . '/lib/BaseFunctions.php';

$moduleDefinitions = require FileSystem::buildPath($root, 'app', 'modules', $module, 'module.php');

if (!is_array($moduleDefinitions)) {
    fwrite(STDERR, "Module definitions are not an array\n");

    exit(2);
}

$compilationPath = ContainerFactory::getCompilationPath($cacheRoot, $module);

ContainerFactory::createBuilder($root, $module, $moduleDefinitions, $compilationPath)->build();

echo $compilationPath;
