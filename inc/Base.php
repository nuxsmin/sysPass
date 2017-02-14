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

define('DEBUG', false);

require 'SplClassLoader.php';

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

// Empezar a calcular el tiempo y memoria utilizados
$memInit = memory_get_usage();
$timeStart = Init::microtime_float();

/**
 * Función para enviar mensajes al log de errores
 *
 * @param mixed $data
 * @param bool $printLastCaller
 */
function debugLog($data, $printLastCaller = false)
{
    error_log(print_r($data, true));

    if ($printLastCaller === true) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $n = count($backtrace);

        for ($i = 1; $i <= $n - 1; $i++) {
            $class = isset($backtrace[$i]['class']) ? $backtrace[$i]['class'] : '';
            error_log(sprintf('Caller %d: %s\%s', $i, $class, $backtrace[$i]['function']));
        }
    }
}

/**
 * Alias gettext function
 *
 * @param string $string
 * @param bool   $tranlate Si es necesario traducir
 * @return string
 */
function __($string, $tranlate = true)
{
    return $tranlate === true && $string !== '' && strlen($string) < 4096 ? gettext($string) : $string;
}

/**
 * Alias para obtener las locales de un dominio
 *
 * @param $domain
 * @param $message
 * @return string
 */
function _t($domain, $message)
{
    return dgettext($domain, $message);
}

// Inicializar sysPass
Init::start();
