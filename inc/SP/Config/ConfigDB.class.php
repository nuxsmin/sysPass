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

namespace SP\Config;

use SP\Core\Exceptions\SPException;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die();

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
    protected static $cache;
    /**
     * @var bool
     */
    protected static $init;

    /**
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return bool
     */
    public static function readConfig()
    {
        $query = 'SELECT config_parameter, config_value FROM config';

        $Data = new QueryData();
        $Data->setUseKeyPair(true);
        $Data->setQuery($query);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        self::$cache = $queryRes;

        return $queryRes;
    }

    /**
     * Guardar la configuración en la BBDD.
     *
     * @param bool $isInsert realizar un 'insert'?
     * @return bool
     */
    public static function writeConfig($isInsert = false)
    {
        foreach (self::$cache as $param => $value) {
            $Data = new QueryData();

            if ($isInsert) {
                $query = 'INSERT INTO config VALUES (:param,:value) ON DUPLICATE KEY UPDATE config_value = :valuedup';

                $Data->addParam($value, 'valuedup');
            } else {
                $query = 'UPDATE config SET config_value = :value WHERE config_parameter = :param';
            }

            $Data->setQuery($query);
            $Data->addParam($param, 'param');
            $Data->addParam($value, 'value');

            try {
                DB::getQuery($Data);
            } catch (SPException $e) {
                return false;
            }
        }

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Configuración', false));
        $LogMessage->addDescription(__('Modificar configuración', false));
        $Log->writeLog();

        Email::sendEmail($LogMessage);

        return true;
    }

    /**
     * Guardar un parámetro de configuración en la BBDD.
     *
     * @param string $param     con el parámetro a guardar
     * @param string $value     con el valor a guardar
     * @param bool   $email     enviar email?
     * @param bool   $hideValue Ocultar el valor del registro en el log
     * @return bool
     */
    public static function setValue($param, $value, $email = true, $hideValue = false)
    {
        $query = /** @lang SQL */
            'INSERT INTO config '
            . 'SET config_parameter = :param,'
            . 'config_value = :value '
            . 'ON DUPLICATE KEY UPDATE config_value = :valuedup';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($param, 'param');
        $Data->addParam($value, 'value');
        $Data->addParam($value, 'valuedup');

        try {
            DB::getQuery($Data);
        } catch (SPException $e) {
            return false;
        }

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Configuración', false));
        $LogMessage->addDescription(__('Modificar configuración', false));
        $LogMessage->addDetails(__('Parámetro', false), $param);

        if ($hideValue === false) {
            $LogMessage->addDetails(__('Valor', false), $value);
        }

        $Log->writeLog();

        if ($email === true) {
            Email::sendEmail($LogMessage);
        }

        return true;
    }

    /**
     * Actualizar el array de parámetros de configuración
     *
     * @param $param   string La clave a actualizar
     * @param $value   mixed El valor a actualizar
     */
    public static function setCacheConfigValue($param, $value)
    {
        self::$cache[$param] = $value;
    }

    /**
     * Obtener un parámetro del el array de parámetros de configuración
     *
     * @param null $param La clave a obtener
     * @return mixed
     */
    public static function getCacheConfigValue($param = null)
    {
        if (null !== $param && isset(self::$cache[$param])) {
            return self::$cache[$param];
        }

        return self::$cache;
    }

    /**
     * Obtiene un valor desde la configuración en la BBDD.
     *
     * @param string $param   con el parámetro de configuración
     * @param string $default El valor por defecto
     * @return false|string con el valor
     */
    public static function getValue($param, $default = null)
    {
        $query = 'SELECT config_value FROM config WHERE config_parameter = :param LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($param, 'param');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return is_object($queryRes) ? $queryRes->config_value : $default;
    }

    /**
     * Elimina un parámetro de la configuración.
     *
     * @param string $param clave
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public static function deleteParam($param)
    {
        $query = 'DELETE FROM config WHERE config_parameter = :param LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($param, 'param');

        return DB::getQuery($Data);
    }
}