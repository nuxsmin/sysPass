<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Exceptions\SPException;

/**
 * [type] [caller] data
 */
const LOG_FORMAT = "[%s] [%s] %s";
/**
 * [timestamp] [type] [caller] data
 */
const LOG_FORMAT_OWN = '[%s] syspass.%s: logger {"message":"%s","caller":"%s"}' . PHP_EOL;

/**
 * Basic logger to handle some debugging and exception messages.
 *
 * It will log messages into syspass.log or PHP error log file.
 *
 * In order to log debugging messages, DEBUG constant must be set to true.
 *
 * A more advanced event logging should be handled through EventDispatcher
 *
 * @param mixed       $data
 * @param string|null $type
 */
function logger($data, ?string $type = 'DEBUG')
{
    if (!DEBUG && $type === 'DEBUG') {
        return;
    }

    $date = date('Y-m-d H:i:s');
    $caller = getLastCaller();
    $line = sprintf(
        LOG_FORMAT_OWN,
        $date,
        $type,
        is_scalar($data) ? $data : print_r($data, true),
        $caller
    );

    $useOwn = (!defined('LOG_FILE')
        || !@error_log($line, 3, LOG_FILE)
    );

    if ($useOwn === false) {
        $line = sprintf(
            LOG_FORMAT,
            $type,
            is_scalar($data) ? $data : print_r($data, true),
            $caller
        );

        @error_log($line);
    }
}

/**
 * Print last callers from backtrace
 */
function printLastCallers()
{
    logger(formatTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)));
}

/**
 * Print last caller from backtrace
 */
function getLastCaller(int $skip = 2): string
{
    $callers = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

    if (isset($callers[$skip]['class'], $callers[$skip]['function'])) {
        return $callers[$skip]['class'] . '::' . $callers[$skip]['function'];
    }

    return 'N/A';
}

function formatStackTrace(Throwable $e): string
{
    $out = [];

    foreach ($e->getTrace() as $index => $trace) {
        if (isset($trace['file'])) {
            $file = sprintf(
                '%s(%d)',
                $trace['file'],
                $trace['line']
            );
        } else {
            $file = '[internal function]';
        }

        if (isset($trace['class'])) {
            $function = sprintf(
                '%s->%s',
                $trace['class'],
                $trace['function']
            );
        } else {
            $function = $trace['function'];
        }

        $args = [];

        if (!empty($trace['args'])) {
            foreach ($trace['args'] as $arg) {
                $type = ucfirst(gettype($arg));

                if ($type === 'Object') {
                    $type = sprintf('Object(%s)', get_class($arg));
                }

                $args[] = $type;
            }
        }

        $out[] = sprintf(
            '#%d %s: %s(%s)',
            $index,
            $file,
            $function,
            implode(',', $args)
        );
    }

    return implode(PHP_EOL, $out);
}

/**
 * Process an exception and log into the error log
 *
 * @param \Exception $exception
 */
function processException(Exception $exception)
{
    logger(sprintf(
        "%s\n%s",
        __($exception->getMessage()),
        formatStackTrace($exception)),
        'EXCEPTION');

    if (($previous = $exception->getPrevious()) !== null) {
        logger(sprintf(
            "(P) %s\n%s",
            __($previous->getMessage()),
            $previous->getTraceAsString()),
            'EXCEPTION');
    }
}

function formatTrace(array $trace): string
{
    $btLine = [];
    $i = 0;

    foreach ($trace as $caller) {
        $class = $caller['class'] ?? '';
        $file = $caller['file'] ?? '';
        $line = $caller['line'] ?? 0;

        $btLine[] = sprintf(
            'Caller %d: %s\%s (%s:%d)',
            $i,
            $class,
            $caller['function'],
            $file,
            $line
        );
        $i++;
    }

    return implode(PHP_EOL, $btLine);
}

/**
 * Alias gettext function
 *
 * @param string $message
 * @param bool   $translate Si es necesario traducir
 */
function __(string $message, bool $translate = true): string
{
    return $translate === true
    && $message !== ''
    && mb_strlen($message) < 4096
        ? gettext($message)
        : $message;
}

/**
 * Returns an untranslated string (gettext placeholder).
 * Dummy function to extract strings from source code
 */
function __u(string $message): string
{
    return $message;
}

/**
 * Alias para obtener las locales de un dominio
 */
function _t(string $domain, string $message, bool $translate = true): string
{
    return $translate === true
    && $message !== ''
    && mb_strlen($message) < 4096
        ? dgettext($domain, $message)
        : $message;
}

/**
 * Capitalización de cadenas multi byte
 */
function mb_ucfirst($string): string
{
    return mb_strtoupper(mb_substr($string, 0, 1));
}

/**
 * Devuelve el tiempo actual en coma flotante.
 * Esta función se utiliza para calcular el tiempo de renderizado con coma flotante
 *
 * @returns float con el tiempo actual
 */
function getElapsedTime(float $from): float
{
    if ($from === 0.0) {
        return 0;
    }

    return microtime(true) - $from;
}

/**
 * Inicializar módulo
 *
 * @throws \SP\Core\Exceptions\SPException
 */
function initModule(string $module): array
{
    logger(sprintf('Initializing module: %s', $module));

    $moduleFile = MODULES_PATH . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'module.php';

    if (is_dir(MODULES_PATH) && file_exists($moduleFile)) {
        $definitions = require $moduleFile;

        if (is_array($definitions)) {
            return $definitions;
        }
    } else {
        throw new SPException('Either module dir or module file don\'t exist');
    }

    logger(sprintf('No definitions found for module: %s', $module));

    return [];
}

/**
 * @return bool|string
 */
function nDirname($dir, $levels)
{
    if (version_compare(PHP_VERSION, '7.0') === -1) {
        logger(realpath(dirname($dir) . str_repeat('../', $levels)));

        return realpath(dirname($dir) . str_repeat('../', $levels));
    }

    return dirname($dir, $levels);
}

/**
 * Prints a fancy trace info using Xdebug extension
 */
function printTraceInfo()
{
    if (DEBUG && extension_loaded('xdebug')) {
        xdebug_print_function_stack();
    }
}
