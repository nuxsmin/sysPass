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

namespace SP\Storage;


use SP\Config\Config;
use SP\Core\DiFactory;
use SP\Core\Exceptions\SPException;

/**
 * Class DBUtil con utilidades de la BD
 *
 * @package SP\Storage
 */
class DBUtil
{
    /**
     * Comprobar que la base de datos existe.
     *
     * @return bool
     * @throws SPException
     */
    public static function checkDatabaseExist()
    {
        try {
            $db = DiFactory::getDBStorage()->getConnection();

            $query = /** @lang SQL */
                'SELECT COUNT(*) 
                FROM information_schema.tables
                WHERE table_schema = \'' . Config::getConfig()->getDbName() . '\'
                AND table_name = \'usrData\'';

            if ((int)$db->query($query)->fetchColumn() !== 0) {
                return true;
            }
        } catch (\Exception $e) {
            throw new SPException(SPException::SP_CRITICAL, $e->getMessage(), $e->getCode());
        }

        return false;
    }

    /**
     * Escapar una cadena de texto con funciones de mysqli.
     *
     * @param $str string con la cadena a escapar
     * @return string con la cadena escapada
     */
    public static function escape($str)
    {
        try {
            $db = DiFactory::getDBStorage()->getConnection();

            return $db->quote(trim($str));
        } catch (SPException $e) {
            return $str;
        }
    }

    /**
     * Obtener la información del servidor de base de datos
     *
     * @return array
     */
    public static function getDBinfo()
    {
        $dbinfo = array();

        try {
            $db = DiFactory::getDBStorage()->getConnection();

            $attributes = array(
                'SERVER_VERSION',
                'CLIENT_VERSION',
                'SERVER_INFO',
                'CONNECTION_STATUS',
            );

            foreach ($attributes as $val) {
                $dbinfo[$val] = $db->getAttribute(constant('PDO::ATTR_' . $val));
            }
        } catch (SPException $e) {
            return $dbinfo;
        }

        return $dbinfo;
    }
}