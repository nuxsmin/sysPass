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

use function SP\getFromEnv;
use function SP\initModule;
use function SP\processException;

// Core PATHS
const DS = DIRECTORY_SEPARATOR;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__ . DS . '..'));
}

const APP_PATH = APP_ROOT . DS . 'app';
const VENDOR_PATH = APP_ROOT . DS . 'vendor';
const SQL_PATH = APP_ROOT . DS . 'schemas';
const PUBLIC_PATH = APP_ROOT . DS . 'public';
const XML_SCHEMA = SQL_PATH . DS . 'syspass.xsd';
const RESOURCES_PATH = APP_PATH . DS . 'resources';
const MODULES_PATH = APP_PATH . DS . 'modules';
const LOCALES_PATH = APP_PATH . DS . 'locales';

// Start tracking the memory used
$memInit = memory_get_usage();

require __DIR__ . DS . 'BaseFunctions.php';
require VENDOR_PATH . DS . 'autoload.php';

$dotenv = Dotenv::createImmutable(APP_ROOT);
$dotenv->load();

defined('APP_MODULE') || define('APP_MODULE', 'web');

define('DEBUG', getFromEnv('DEBUG', false));
define('IS_TESTING', getFromEnv('IS_TESTING', defined('TEST_ROOT')));
define('CONFIG_PATH', getFromEnv('CONFIG_PATH', APP_PATH . DS . 'config'));
define('CONFIG_FILE', getFromEnv('CONFIG_FILE', CONFIG_PATH . DS . 'config.xml'));
define('ACTIONS_FILE', getFromEnv('ACTIONS_FILE', RESOURCES_PATH . DS . 'actions.xml'));
define('MIMETYPES_FILE', getFromEnv('MIMETYPES_FILE', RESOURCES_PATH . DS . 'mime.xml'));
define('LOG_FILE', getFromEnv('LOG_FILE', CONFIG_PATH . DS . 'syspass.log'));

const LOCK_FILE = CONFIG_PATH . DS . '.lock';

// Setup application paths
define('BACKUP_PATH', getFromEnv('BACKUP_PATH', APP_PATH . DS . 'backup'));
define('CACHE_PATH', getFromEnv('CACHE_PATH', APP_PATH . DS . 'cache'));
define('TMP_PATH', getFromEnv('TMP_PATH', APP_PATH . DS . 'temp'));

try {
    $moduleDefinitions = initModule(APP_MODULE);

    $containerBuilder = new ContainerBuilder();

    if (!DEBUG) {
        $containerBuilder->enableCompilation(CACHE_PATH);
        $containerBuilder->writeProxiesToFile(true, CACHE_PATH . DS . 'proxies');
    }

    return $containerBuilder
        ->addDefinitions(CoreDefinitions::getDefinitions(), DomainDefinitions::getDefinitions(), $moduleDefinitions)
        ->build();
} catch (Exception $e) {
    processException($e);

    die($e->getMessage());
}
