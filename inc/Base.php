<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

use SP\Core\Init;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

define('XML_CONFIG_FILE', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.xml');
define('CONFIG_FILE', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');
define('MODEL_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'SP');
define('CONTROLLER_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'web');
define('VIEW_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'themes');
define('EXTENSIONS_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'Exts');
define('LOCALES_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'locales');

define('DEBUG', true);

require 'SplClassLoader.php';

$ClassLoader = new SplClassLoader();
$ClassLoader->setFileExtension('.class.php');
$ClassLoader->addExcluded('SP\\Profile');
$ClassLoader->addExcluded('SP\\UserPreferences');
$ClassLoader->addExcluded('SP\\CustomFieldDef');
$ClassLoader->addExcluded('SP\\PublicLink');
$ClassLoader->register();

// Empezar a calcular el tiempo y memoria utilizados
$memInit = memory_get_usage();
$timeStart = Init::microtime_float();

// Inicializar sysPass
Init::start();