<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Services\Install;

use PDOException;
use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Storage\Database\DBUtil;
use SP\Storage\Database\MySQLFileParser;
use SP\Storage\Database\MySQLHandler;
use SP\Storage\FileHandler;
use SP\Util\Util;

/**
 * Class MySQL
 *
 * @package SP\Services\Install
 */
class MySQL implements DatabaseSetupInterface
{
    /**
     * @var InstallData
     */
    protected $installData;
    /**
     * @var \SP\Storage\Database\MySQLHandler
     */
    protected $dbs;
    /**
     * @var ConfigData
     */
    protected $configData;

    /**
     * MySQL constructor.
     *
     * @param InstallData $installData
     * @param ConfigData $configData
     * @throws SPException
     */
    public function __construct(InstallData $installData, ConfigData $configData)
    {
        $this->installData = $installData;
        $this->configData = $configData;

        $this->connectDatabase();
    }

    /**
     * Conectar con la BBDD
     *
     * Comprobar si la conexión con la base de datos para sysPass es posible con
     * los datos facilitados.
     *
     * @throws SPException
     */
    public function connectDatabase()
    {
        try {
            $dbc = (new DatabaseConnectionData())
                ->setDbHost($this->installData->getDbHost())
                ->setDbPort($this->installData->getDbPort())
                ->setDbSocket($this->installData->getDbSocket())
                ->setDbUser($this->installData->getDbAdminUser())
                ->setDbPass($this->installData->getDbAdminPass());

            $this->dbs = new MySQLHandler($dbc);
            $this->dbs->getConnectionSimple();
        } catch (SPException $e) {
            processException($e);

            throw new SPException(
                __u('No es posible conectar con la BD'),
                SPException::ERROR,
                __('Compruebe los datos de conexión') . '<br>' . $e->getHint(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws SPException
     */
    public function setupDbUser()
    {
        $user = substr(uniqid('sp_'), 0, 16);
        $pass = Util::randomPassword();

        try {
            // Comprobar si el usuario proporcionado existe
            $sth = $this->dbs->getConnectionSimple()
                ->prepare('SELECT COUNT(*) FROM mysql.user WHERE `user` = ? AND `host` = ?');
            $sth->execute([$user, $pass]);

            // Si no existe el usuario, se intenta crear
            if ((int)$sth->fetchColumn() === 0) {
                $this->createDBUser($user, $pass);
            }
        } catch (PDOException $e) {
            processException($e);

            throw new SPException(
                sprintf(__('No es posible comprobar el usuario de sysPass (%s)'), $user),
                SPException::CRITICAL,
                __u('Compruebe los permisos del usuario de conexión a la BD'),
                $e->getCode(),
                $e
            );
        }

        // Guardar el nuevo usuario/clave de conexión a la BD
        $this->configData->setDbUser($user);
        $this->configData->setDbPass($pass);
    }

    /**
     * Crear el usuario para conectar con la base de datos.
     * Esta función crea el usuario para conectar con la base de datos.
     *
     * @param string $user
     * @param string $pass
     * @throws SPException
     */
    public function createDBUser($user, $pass)
    {
        if ($this->installData->isHostingMode()) {
            return;
        }

        debugLog('Creating DB user');

        try {
            $dbc = $this->dbs->getConnectionSimple();

            $dbc->exec('CREATE USER `' . $user . '`@`' . $this->installData->getDbAuthHost() . '` IDENTIFIED BY \'' . $pass . '\'');
            $dbc->exec('CREATE USER `' . $user . '`@`' . $this->installData->getDbAuthHostDns() . '` IDENTIFIED BY \'' . $pass . '\'');
            $dbc->exec('FLUSH PRIVILEGES');
        } catch (PDOException $e) {
            processException($e);

            throw new SPException(
                sprintf(__u('Error al crear el usuario de conexión a MySQL \'%s\''), $user),
                SPException::CRITICAL,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Crear la base de datos
     *
     * @throws SPException
     */
    public function createDatabase()
    {
        if (!$this->installData->isHostingMode()) {

            if ($this->checkDatabaseExist()) {
                throw new SPException(
                    __u('La BBDD ya existe'),
                    SPException::ERROR,
                    __u('Indique una nueva Base de Datos o elimine la existente')
                );
            }

            try {
                $dbc = $this->dbs->getConnectionSimple();

                $dbc->exec('CREATE SCHEMA `' . $this->installData->getDbName() . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
            } catch (PDOException $e) {
                throw new SPException(
                    sprintf(__('Error al crear la BBDD (\'%s\')'), $e->getMessage()),
                    SPException::CRITICAL,
                    __u('Verifique los permisos del usuario de la Base de Datos'),
                    $e->getCode(),
                    $e
                );
            }

            $query = /** @lang SQL */
                'GRANT ALL PRIVILEGES ON `' . $this->installData->getDbName() . '`.* 
                  TO `' . $this->configData->getDbUser() . '`@`' . $this->installData->getDbAuthHost() . '`';

            $queryDns = /** @lang SQL */
                'GRANT ALL PRIVILEGES ON `' . $this->installData->getDbName() . '`.* 
                  TO `' . $this->configData->getDbUser() . '`@`' . $this->installData->getDbAuthHostDns() . '`';

            try {
                $dbc->exec($query);
                $dbc->exec($queryDns);
                $dbc->exec('FLUSH PRIVILEGES');
            } catch (PDOException $e) {
                processException($e);

                $this->rollback();

                throw new SPException(
                    sprintf(__('Error al establecer permisos de la BBDD (\'%s\')'), $e->getMessage()),
                    SPException::CRITICAL,
                    __u('Verifique los permisos del usuario de la Base de Datos'),
                    $e->getCode(),
                    $e
                );
            }
        } else {
            try {
                // Commprobar si existe al seleccionarla
                $this->dbs->getConnectionSimple()
                    ->exec('USE `' . $this->installData->getDbName() . '`');
            } catch (PDOException $e) {
                throw new SPException(
                    __u('La BBDD no existe'),
                    SPException::ERROR,
                    __u('Es necesario crearla y asignar los permisos necesarios'),
                    $e->getCode(),
                    $e
                );
            }
        }
    }

    /**
     * @return bool
     * @throws SPException
     */
    public function checkDatabaseExist()
    {
        $sth = $this->dbs->getConnectionSimple()
            ->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1');
        $sth->execute([$this->installData->getDbName()]);

        return (int)$sth->fetchColumn() === 1;
    }

    /**
     * @throws SPException
     */
    public function rollback()
    {
        $dbc = $this->dbs->getConnectionSimple();

        if ($this->installData->isHostingMode()) {
            foreach (DBUtil::$tables as $table) {
                $dbc->exec('DROP TABLE IF EXISTS `' . $this->installData->getDbName() . '`.`' . $table . '`');
            }
        } else {
            $dbc->exec('DROP DATABASE IF EXISTS `' . $this->installData->getDbName() . '`');
            $dbc->exec('DROP USER `' . $this->configData->getDbUser() . '`@`' . $this->installData->getDbAuthHost() . '`');
            $dbc->exec('DROP USER `' . $this->configData->getDbUser() . '`@`' . $this->installData->getDbAuthHostDns() . '`');
//            $this->DB->exec('DROP USER `' . $this->InstallData->getDbUser() . '`@`%`');
        }

        debugLog('Rollback');
    }

    /**
     * @throws SPException
     */
    public function createDBStructure()
    {
        $fileName = SQL_PATH . DIRECTORY_SEPARATOR . 'dbstructure.sql';

        if (!file_exists($fileName)) {
            throw new SPException(
                __u('El archivo de estructura de la BBDD no existe'),
                SPException::CRITICAL,
                __u('No es posible crear la BBDD de la aplicación. Descárguela de nuevo.')
            );
        }

        try {
            $dbc = $this->dbs->getConnectionSimple();

            // Usar la base de datos de sysPass
            $dbc->exec('USE `' . $this->installData->getDbName() . '`');
        } catch (PDOException $e) {
            throw new SPException(
                sprintf(__('Error al seleccionar la BBDD') . ' \'%s\' (%s)', $this->installData->getDbName(), $e->getMessage()),
                SPException::CRITICAL,
                __u('No es posible usar la Base de Datos para crear la estructura. Compruebe los permisos y que no exista.'),
                $e->getCode(),
                $e
            );
        }


        foreach ((new MySQLFileParser())
                     ->parse(new FileHandler($fileName)) as $query) {
            try {
                $dbc->exec($query);
            } catch (PDOException $e) {
                processException($e);

                debugLog('Query: ' . $query);

                $this->rollback();

                throw new SPException(
                    sprintf(__('Error al crear la BBDD (\'%s\')'), $e->getMessage()),
                    SPException::CRITICAL,
                    __u('Error al crear la estructura de la Base de Datos.'),
                    $e->getCode(),
                    $e
                );
            }
        }
    }

    /**
     * Comprobar la conexión a la BBDD
     *
     * @throws SPException
     */
    public function checkConnection()
    {
        if (!DBUtil::checkDatabaseExist($this->dbs, $this->installData->getDbName())) {
            $this->rollback();

            throw new SPException(
                __u('Error al comprobar la base de datos'),
                SPException::CRITICAL,
                __u('Intente de nuevo la instalación')
            );
        }
    }
}