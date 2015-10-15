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

namespace SP\Config;

use SP\Storage\DB;
use SP\Log\Email;
use SP\Log\Log;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class ConfigDB para la gestión de la configuración en la BD
 *
 * @package SP
 */
class ConfigDB implements ConfigInterface
{
    /**
     * @var array
     */
    protected static $_cache;
    /**
     * @var bool
     */
    protected static $_init;

    /**
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return bool
     */
    public static function readConfig()
    {
        $query = 'SELECT config_parameter, config_value FROM config';

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $config) {
            self::$_cache[$config->config_parameter] = $config->config_value;
        }
    }

    /**
     * Guardar la configuración en la BBDD.
     *
     * @param bool $isInsert realizar un 'insert'?
     * @return bool
     */
    public static function writeConfig($isInsert = false)
    {
        foreach (self::$_cache as $param => $value) {
            if ($isInsert) {
                $query = 'INSERT INTO config VALUES (:param,:value) ON DUPLICATE KEY UPDATE config_value = :valuedup';

                $data['valuedup'] = $value;
            } else {
                $query = 'UPDATE config SET config_value = :value WHERE config_parameter = :param';
            }

            $data['param'] = $param;
            $data['value'] = $value;

            if (DB::getQuery($query, __FUNCTION__, $data) === false) {
                return false;
            }
        }

        $Log = new Log(_('Configuración'));
        $Log->addDescription(_('Modificar configuración'));
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Guardar un parámetro de configuración en la BBDD.
     *
     * @param string $param con el parámetro a guardar
     * @param string $value con el valor a guardar
     * @param bool   $email enviar email?
     * @return bool
     */
    public static function setValue($param, $value, $email = true)
    {
        $query = "INSERT INTO config "
            . "SET config_parameter = :param,"
            . "config_value = :value "
            . "ON DUPLICATE KEY UPDATE config_value = :valuedup";

        $data['param'] = $param;
        $data['value'] = $value;
        $data['valuedup'] = $value;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $log = new Log(_('Configuración'));
        $log->addDescription(_('Modificar configuración'));
        $log->addDetails(_('Parámetro'), $param);
        $log->addDetails(_('Valor'), $value);
        $log->writeLog();

        if ($email === true) {
            Email::sendEmail($log);
        }

        return true;
    }

    /**
     * Actualizar el array de parámetros de configuración
     *
     * @param $param   string La clave a actualizar
     * @param $value mixed El valor a actualizar
     */
    public static function setCacheConfigValue($param, $value)
    {
        self::$_cache[$param] = $value;
    }

    /**
     * Obtener un parámetro del el array de parámetros de configuración
     *
     * @param null $param La clave a obtener
     * @return mixed
     */
    public static function getCacheConfigValue($param = null)
    {
        if (!is_null($param) && isset(self::$_cache[$param])) {
            return self::$_cache[$param];
        }

        return self::$_cache;
    }

    /**
     * Obtiene un valor desde la configuración en la BBDD.
     *
     * @param string $param con el parámetro de configuración
     * @param string $default El valor por defecto
     * @return false|string con el valor
     */
    public static function getValue($param, $default = null)
    {
        $query = 'SELECT config_value FROM config WHERE config_parameter = :parameter LIMIT 1';

        $data['parameter'] = $param;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return ($queryRes->config_value) ? $queryRes->config_value : $default;
    }

    /**
     * Elimina un parámetro de la configuración.
     *
     * @param string $param clave
     * @return bool
     */
    public static function deleteParam($param)
    {
        $query = 'DELETE FROM config WHERE config_parameter = :param LIMIT 1';
        $data['param'] = $param;

        return (DB::getQuery($query, __FUNCTION__, $data) !== false);
    }
}