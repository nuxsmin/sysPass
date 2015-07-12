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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class DBConnectionFactory
 *
 * Esta clase se encarga de crear las conexiones a la BD
 */
class DBConnectionFactory
{
    /**
     * @var DBConnectionFactory
     */
    private static $_factory;
    /**
     * @var \PDO
     */
    private $_db;

    /**
     * Obtener una instancia de la clase
     *
     * @return DBConnectionFactory
     */
    public static function getFactory()
    {
        if (!self::$_factory) {
//             FIXME
//            error_log('NEW FACTORY');
            self::$_factory = new DBConnectionFactory();
        }

        return self::$_factory;
    }

    /**
     * Realizar la conexión con la BBDD.
     * Esta función utiliza PDO para conectar con la base de datos.
     *
     * @throws SPException
     * @return \PDO
     */

    public function getConnection()
    {
        if (!$this->_db) {
//             FIXME
//            error_log('NEW DB_CONNECTION');
            $isInstalled = Config::getValue('installed');

            $dbhost = Config::getValue('dbhost');
            $dbuser = Config::getValue('dbuser');
            $dbpass = Config::getValue('dbpass');
            $dbname = Config::getValue('dbname');
            $dbport = Config::getValue('dbport', 3306);

            if (empty($dbhost) || empty($dbuser) || empty($dbpass) || empty($dbname)) {
                if ($isInstalled) {
                    Init::initError(_('No es posible conectar con la BD'), _('Compruebe los datos de conexión'));
                } else {
                    throw new SPException(SPException::SP_CRITICAL, _('No es posible conectar con la BD'), _('Compruebe los datos de conexión'));
                }
            }

            try {
                $dsn = 'mysql:host=' . $dbhost . ';port=' . $dbport . ';dbname=' . $dbname . ';charset=utf8';
//                $this->db = new PDO($dsn, $dbuser, $dbpass, array(PDO::ATTR_PERSISTENT => true));
                $this->_db = new \PDO($dsn, $dbuser, $dbpass);
            } catch (\Exception $e) {
                if ($isInstalled) {
                    if ($e->getCode() === 1049) {
                        Config::setValue('installed', '0');
                    }

                    Init::initError(_('No es posible conectar con la BD'), 'Error ' . $e->getCode() . ': ' . $e->getMessage());
                } else {
                    throw new SPException(SPException::SP_CRITICAL, $e->getMessage(), $e->getCode());
                }
            }
        }

        $this->_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $this->_db;
    }
}