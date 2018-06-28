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

/**
 * Función para enviar mensajes al log de errores
 *
 * @param mixed $data
 * @param bool  $printLastCaller
 */
function debugLog($data, $printLastCaller = false)
{
    $line = date('Y-m-d H:i:s') . ' - ' . print_r($data, true) . PHP_EOL;
    $useOwn = (!defined('LOG_FILE')
        || !error_log($line, 3, LOG_FILE)
    );

    if ($useOwn === false) {
        error_log(print_r($data, true));
    }

    if ($printLastCaller === true) {
        if ($useOwn === true) {
            error_log(formatTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)), 3, LOG_FILE);
        } else {
            error_log(formatTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)));
        }
    }
}

/**
 * Procesar una excepción y registrarla en el log
 *
 * @param \Exception $exception
 */
function processException(\Exception $exception)
{
    debugLog(__($exception->getMessage()));
    debugLog($exception->getTraceAsString());

    $previous = $exception->getPrevious();

    if ($previous !== null) {
        debugLog(__($previous->getMessage()));
        debugLog($previous->getTraceAsString());
    }
}

/**
 * @param $trace
 * @return string
 */
function formatTrace($trace)
{
    $btLine = [];
    $i = 0;

    foreach ($trace as $caller) {
        $class = isset($caller['class']) ? $caller['class'] : '';
        $file = isset($caller['file']) ? $caller['file'] : '';
        $line = isset($caller['line']) ? $caller['line'] : 0;

        $btLine[] = sprintf('Caller %d: %s\%s (%s:%d)', $i, $class, $caller['function'], $file, $line);
        $i++;
    }

    return implode(PHP_EOL, $btLine);
}

/**
 * Alias gettext function
 *
 * @param string $message
 * @param bool   $translate Si es necesario traducir
 * @return string
 */
function __($message, $translate = true)
{
    return $translate === true && $message !== '' && mb_strlen($message) < 4096 ? gettext($message) : $message;
}

/**
 * Returns an untranslated string (gettext placeholder)
 *
 * @param string $message
 * @return string
 */
function __u($message)
{
    return $message;
}

/**
 * Alias para obtener las locales de un dominio
 *
 * @param string $domain
 * @param string $message
 * @param bool   $translate
 * @return string
 */
function _t($domain, $message, $translate = true)
{
    return $translate === true && $message !== '' && mb_strlen($message) < 4096 ? dgettext($domain, $message) : $message;
}

/**
 * Capitalización de cadenas multi byte
 *
 * @param $string
 * @return string
 */
function mb_ucfirst($string)
{
    return mb_strtoupper(mb_substr($string, 0, 1));
}

/**
 * Devuelve el tiempo actual en coma flotante.
 * Esta función se utiliza para calcular el tiempo de renderizado con coma flotante
 *
 * @param float $from
 * @returns float con el tiempo actual
 * @return float
 */
function getElapsedTime($from)
{
    if ($from === 0) {
        return 0;
    }

    return microtime(true) - floatval($from);
}

/**
 * Inicializar módulo
 *
 * @param $module
 */
function initModule($module)
{
    $dir = dir(MODULES_PATH);

    while (false !== ($entry = $dir->read())) {
        $moduleFile = MODULES_PATH . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'module.php';

        if ($entry === $module && file_exists($moduleFile)) {
            require $moduleFile;
        }
    }

    $dir->close();
}

/**
 * @param $dir
 * @param $levels
 * @return bool|string
 */
function nDirname($dir, $levels)
{
    if (version_compare(PHP_VERSION, '7.0') === -1) {
        debugLog(realpath(dirname($dir) . str_repeat('../', $levels)));

        return realpath(dirname($dir) . str_repeat('../', $levels));
    }

    return dirname($dir, $levels);
}

/**
 * @param Exception $exception
 * @throws ReflectionException
 */
function flattenExceptionBacktrace(\Exception $exception)
{
    $traceProperty = (new \ReflectionClass('Exception'))->getProperty('trace');
    $traceProperty->setAccessible(true);
    $flatten = function (&$value, $key) {
        if ($value instanceof \Closure) {
            $closureReflection = new \ReflectionFunction($value);
            $value = sprintf(
                '(Closure at %s:%s)',
                $closureReflection->getFileName(),
                $closureReflection->getStartLine()
            );
        } elseif (is_object($value)) {
            $value = sprintf('object(%s)', get_class($value));
        } elseif (is_resource($value)) {
            $value = sprintf('resource(%s)', get_resource_type($value));
        }
    };
    do {
        $trace = $traceProperty->getValue($exception);
        foreach ($trace as &$call) {
            array_walk_recursive($call['args'], $flatten);
        }
        $traceProperty->setValue($exception, $trace);
    } while ($exception = $exception->getPrevious());
    $traceProperty->setAccessible(false);
}

//set_exception_handler('\flattenExceptionBacktrace');