<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use SP\Bootstrap;

defined('APP_ROOT') || die();

// Core PATHS
define('BASE_PATH', __DIR__);
define('APP_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'app');
define('VENDOR_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'vendor');
define('SQL_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'schemas');
define('PUBLIC_PATH', APP_ROOT . DIRECTORY_SEPARATOR . 'public');
define('XML_SCHEMA', SQL_PATH . DIRECTORY_SEPARATOR . 'syspass.xsd');

// Start tracking the memory used
$memInit = memory_get_usage();

require __DIR__ . DIRECTORY_SEPARATOR . 'BaseFunctions.php';
require VENDOR_PATH . DIRECTORY_SEPARATOR . 'autoload.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'SplClassLoader.php';

$dotenv = Dotenv::createImmutable(APP_ROOT);
$dotenv->load();

defined('APP_MODULE') || define('APP_MODULE', 'web');
define('DEBUG', getenv('DEBUG') || false);

define('CONFIG_PATH', getenv('CONFIG_PATH')
    ?: APP_PATH . DIRECTORY_SEPARATOR . 'config');
define('RESOURCES_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'resources');

// Setup config files
define('CONFIG_FILE', getenv('CONFIG_FILE')
    ?: CONFIG_PATH . DIRECTORY_SEPARATOR . 'config.xml');
define('ACTIONS_FILE', getenv('ACTIONS_FILE')
    ?: RESOURCES_PATH . DIRECTORY_SEPARATOR . 'actions.xml');
define('MIMETYPES_FILE', getenv('MIMETYPES_FILE')
    ?: RESOURCES_PATH . DIRECTORY_SEPARATOR . 'mime.xml');
define('OLD_CONFIG_FILE', CONFIG_PATH . DIRECTORY_SEPARATOR . 'config.php');
define('LOG_FILE', getenv('LOG_FILE')
    ?: CONFIG_PATH . DIRECTORY_SEPARATOR . 'syspass.log');
define('LOCK_FILE', CONFIG_PATH . DIRECTORY_SEPARATOR . '.lock');

// Setup application paths
define('MODULES_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'modules');
define('LOCALES_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'locales');
define('BACKUP_PATH', getenv('BACKUP_PATH')
    ?: APP_PATH . DIRECTORY_SEPARATOR . 'backup');
define('CACHE_PATH', getenv('CACHE_PATH')
    ?: APP_PATH . DIRECTORY_SEPARATOR . 'cache');
define('TMP_PATH', getenv('TMP_PATH')
    ?: APP_PATH . DIRECTORY_SEPARATOR . 'temp');

initModule(APP_MODULE);

try {
    $builder = new ContainerBuilder();
    $builder->writeProxiesToFile(true, CACHE_PATH . DIRECTORY_SEPARATOR . 'proxies');
    $builder->addDefinitions(BASE_PATH . DIRECTORY_SEPARATOR . 'Definitions.php');

    Bootstrap::run($builder->build());
} catch (Exception $e) {
    processException($e);

    die($e->getMessage());
}