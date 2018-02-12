<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Install;

/**
 * Interface DatabaseInterface
 *
 * @package SP\Core\Install
 */
interface DatabaseSetupInterface
{
    /**
     * Conectar con la BBDD
     *
     * Comprobar si la conexión con la base de datos para sysPass es posible con
     * los datos facilitados.
     */
    public function connectDatabase();

    /**
     * @return mixed
     */
    public function setupDbUser();

    /**
     * Crear el usuario para conectar con la base de datos.
     * Esta función crea el usuario para conectar con la base de datos.
     */
    public function createDBUser();

    /**
     * Crear la base de datos
     */
    public function createDatabase();

    /**
     * @return mixed
     */
    public function checkDatabaseExist();

    /**
     * Deshacer la instalación en caso de fallo.
     * Esta función elimina la base de datos y el usuario de sysPass
     */
    public function rollback();

    /**
     * Crear la estructura de la base de datos.
     * Esta función crea la estructura de la base de datos a partir del archivo dbsctructure.sql.
     */
    public function createDBStructure();

    /**
     * Comprobar la conexión a la BBDD
     */
    public function checkConnection();
}