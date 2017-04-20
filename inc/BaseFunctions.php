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
 * @param bool $printLastCaller
 */
function debugLog($data, $printLastCaller = false)
{
    $useOwn = true;
    $line = date('Y-m-d H:i:s') . ' - ' . print_r($data, true) . PHP_EOL;

    if (!error_log($line, 3, LOG_FILE)) {
        $useOwn = false;
        error_log(print_r($data, true));
    }

    if ($printLastCaller === true) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $n = count($backtrace);

        for ($i = 1; $i <= $n - 1; $i++) {
            $class = isset($backtrace[$i]['class']) ? $backtrace[$i]['class'] : '';
            $btLine = sprintf('Caller %d: %s\%s' . PHP_EOL, $i, $class, $backtrace[$i]['function']);

            if ($useOwn === true) {
                error_log($btLine, 3, LOG_FILE);
            } else {
                error_log($btLine);
            }
        }
    }
}

/**
 * Alias gettext function
 *
 * @param string $message
 * @param bool $translate Si es necesario traducir
 * @return string
 */
function __($message, $translate = true)
{
    return $translate === true && $message !== '' && mb_strlen($message) < 4096 ? gettext($message) : $message;
}

/**
 * Alias para obtener las locales de un dominio
 *
 * @param string $domain
 * @param string $message
 * @param bool $translate
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
 * @returns float con el tiempo actual
 */
function getElapsedTime()
{
    return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
}