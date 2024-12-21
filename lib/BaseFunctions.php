<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP;

use Exception;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\File\FileSystem;
use Throwable;

use const APP_PATH;

/**
 * [type] [caller] data
 */
const LOG_FORMAT = "[%s] [%s] %s";
/**
 * [timestamp] [type] [caller] data
 */
const LOG_FORMAT_OWN = '[%s] syspass.%s: logger {"message":"%s","caller":"%s"}' . PHP_EOL;

define('MODULES_PATH', FileSystem::buildPath(APP_PATH, 'modules'));

define('LOG_FILE', getFromEnv('LOG_FILE', FileSystem::buildPath(APP_PATH, 'config', 'syspass.log')));

/**
 * Basic logger to handle some debugging and exception messages.
 *
 * It will log messages into syspass.log or PHP error log file.
 *
 * In order to log debugging messages, DEBUG constant must be set to true.
 *
 * A more advanced event logging should be handled through EventDispatcher
 *
 * @param mixed $data
 * @param string $type
 */
function logger(mixed $data, string $type = 'DEBUG'): void
{
    if (defined('DEBUG') && !DEBUG && ($type === 'DEBUG')) {
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
               || !@file_put_contents(LOG_FILE, $line, FILE_APPEND)
    );

    if ($useOwn === false) {
        $line = sprintf(
            LOG_FORMAT,
            $type,
            is_scalar($data) ? $data : print_r($data, true),
            $caller
        );

        @file_put_contents(LOG_FILE, $line, FILE_APPEND);
    }
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
 * @param Exception $exception
 */
function processException(Exception $exception): void
{
    logger(
        sprintf(
            "%s\n%s",
            __($exception->getMessage()),
            formatStackTrace($exception)
        ),
        'EXCEPTION'
    );

    if (($previous = $exception->getPrevious()) !== null) {
        logger(
            sprintf(
                "(P) %s\n%s",
                __($previous->getMessage()),
                $previous->getTraceAsString()
            ),
            'EXCEPTION'
        );
    }
}

/**
 * Alias gettext function
 *
 * @param string $message
 * @param bool $translate Si es necesario traducir
 *
 * @return string
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
 * @throws SPException
 */
function initModule(string $module): array
{
    if (!defined('MODULES_PATH')) {
        return [];
    }

    logger(sprintf('Initializing module: %s', $module));

    try {
        $definitions = FileSystem::require(FileSystem::buildPath(MODULES_PATH, $module, 'module.php'));

        if (is_array($definitions)) {
            return $definitions;
        }
    } catch (Infrastructure\File\FileException $e) {
        throw new SPException('Either module dir or module file don\'t exist');
    }

    logger(sprintf('No definitions found for module: %s', $module));

    return [];
}

/**
 * Defines a constant by looking up its value in an environment variable with the same name.
 *
 * @param string $envVar
 * @param mixed|null $default
 * @return string|array|mixed|false
 */
function getFromEnv(string $envVar, mixed $default = null): mixed
{
    $env = getenv($envVar) ?: $default;

    if ($default !== null) {
        settype($env, gettype($default));
    }

    return $env;
}
