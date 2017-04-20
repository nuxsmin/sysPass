<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Init;

defined('APP_ROOT') || die();

// Please, notice that this file should be outside the webserver root. You can move it and then update this path
define('XML_CONFIG_FILE', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.xml');

define('BASE_DIR', __DIR__);
define('CONFIG_FILE', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');
define('MODEL_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'SP');
define('CONTROLLER_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'web');
define('VIEW_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'themes');
define('EXTENSIONS_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'Exts');
define('PLUGINS_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'Plugins');
define('LOCALES_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'locales');
define('SQL_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'sql');
define('LOG_FILE', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'syspass.log');

define('DEBUG', false);

// Required random_compat polyfill for random_bytes() and random_int()
// @see https://github.com/paragonie/random_compat/tree/v2.0.4#random_compat
require_once EXTENSIONS_PATH . DIRECTORY_SEPARATOR . 'random_compat' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'random.php';

require __DIR__ . DIRECTORY_SEPARATOR . 'SplClassLoader.php';

$ClassLoader = new SplClassLoader('SP');
$ClassLoader->setFileExtension('.class.php');
$ClassLoader->addExcluded('SP\\Profile');
$ClassLoader->addExcluded('SP\\Mgmt\\User\\Profile');
$ClassLoader->addExcluded('SP\\UserPreferences');
$ClassLoader->addExcluded('SP\\Mgmt\\User\\UserPreferences');
$ClassLoader->addExcluded('SP\\CustomFieldDef');
$ClassLoader->addExcluded('SP\\Mgmt\\CustomFieldDef');
$ClassLoader->addExcluded('SP\\PublicLink');
$ClassLoader->register();

require __DIR__ . DIRECTORY_SEPARATOR . 'BaseFunctions.php';

// Empezar a calcular la memoria utilizada
$memInit = memory_get_usage();

// Inicializar sysPass
Init::start();
