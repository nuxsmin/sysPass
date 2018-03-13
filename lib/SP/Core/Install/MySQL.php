<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

use PDOException;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\DataModel\InstallData;
use SP\Storage\DatabaseConnectionData;
use SP\Storage\DBUtil;
use SP\Storage\MySQLHandler;
use SP\Util\Util;

/**
 * Class MySQL
 *
 * @package SP\Core\Install
 */
class MySQL implements DatabaseSetupInterface
{
    use SP\Core\Dic\InjectableTrait;

    /**
     * @var InstallData
     */
    protected $installData;
    /**
     * @var MySQLHandler
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
     * @throws SPException
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct(InstallData $installData)
    {
        $this->injectDependencies();

        $this->installData = $installData;

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
            throw new SPException(
                __u('No es posible conectar con la BD'),
                SPException::CRITICAL,
                __('Compruebe los datos de conexión') . '<br>' . $e->getHint()
            );
        }
    }

    /**
     * @param Config $config
     */
    public function inject(Config $config)
    {
        $this->configData = $config->getConfigData();
    }

    /**
     * @throws SPException
     */
    public function setupDbUser()
    {
        $this->installData->setDbPass(Util::randomPassword());
        $this->installData->setDbUser(substr(uniqid('sp_'), 0, 16));

        // Comprobar si el usuario sumistrado existe
        $query = /** @lang SQL */
            'SELECT COUNT(*) FROM mysql.user WHERE user = ? AND `host` = ?';

        try {
            $sth = $this->dbs->getConnectionSimple()->prepare($query);
            $sth->execute([$this->installData->getDbUser(), $this->installData->getDbAuthHost()]);

            // Si no existe el usuario, se intenta crear
            if ((int)$sth->fetchColumn() === 0
                // Se comprueba si el nuevo usuario es distinto del creado en otra instalación
                && $this->installData->getDbUser() !== $this->configData->getDbUser()
            ) {
                $this->createDBUser();
            }
        } catch (PDOException $e) {
            throw new SPException(
                sprintf(__('No es posible comprobar el usuario de sysPass (%s)'), $this->installData->getAdminLogin()),
                SPException::CRITICAL,
                __u('Compruebe los permisos del usuario de conexión a la BD')
            );
        }

        // Guardar el nuevo usuario/clave de conexión a la BD
        $this->configData->setDbUser($this->installData->getDbUser());
        $this->configData->setDbPass($this->installData->getDbPass());
    }

    /**
     * Crear el usuario para conectar con la base de datos.
     * Esta función crea el usuario para conectar con la base de datos.
     *
     * @throws SPException
     */
    public function createDBUser()
    {
        if ($this->installData->isHostingMode()) {
            return;
        }

        $query = /** @lang SQL */
            'CREATE USER `' . $this->installData->getDbUser() . '`@`' . $this->installData->getDbAuthHost() . '` IDENTIFIED BY \'' . $this->installData->getDbPass() . '\'';

        $queryDns = /** @lang SQL */
            'CREATE USER `' . $this->installData->getDbUser() . '`@`' . $this->installData->getDbAuthHostDns() . '` IDENTIFIED BY \'' . $this->installData->getDbPass() . '\'';

        try {
            $dbc = $this->dbs->getConnectionSimple();

            $dbc->exec($query);
            $dbc->exec($queryDns);
            $dbc->exec('FLUSH PRIVILEGES');
        } catch (PDOException $e) {
            throw new SPException(
                sprintf(__u('Error al crear el usuario de conexión a MySQL \'%s\''), $this->installData->getDbUser()),
                SPException::CRITICAL, $e->getMessage()
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
        $checkDatabase = $this->checkDatabaseExist();

        if ($checkDatabase && !$this->installData->isHostingMode()) {
            throw new SPException(
                __u('La BBDD ya existe'),
                SPException::ERROR,
                __u('Indique una nueva Base de Datos o elimine la existente')
            );
        }

        if (!$checkDatabase && $this->installData->isHostingMode()) {
            throw new SPException(
                __u('La BBDD no existe'),
                SPException::ERROR,
                __u('Es necesario crearla y asignar los permisos necesarios')
            );
        }

        if (!$this->installData->isHostingMode()) {

            try {
                $dbc = $this->dbs->getConnectionSimple();

                $dbc->exec(/** @lang SQL */
                    'CREATE SCHEMA `' . $this->installData->getDbName() . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
            } catch (PDOException $e) {
                throw new SPException(
                    sprintf(__('Error al crear la BBDD (\'%s\')'), $e->getMessage()),
                    SPException::CRITICAL,
                    __u('Verifique los permisos del usuario de la Base de Datos'));
            }

            $query = /** @lang SQL */
                'GRANT ALL PRIVILEGES ON `' . $this->installData->getDbName() . '`.* 
                  TO `' . $this->installData->getDbUser() . '`@`' . $this->installData->getDbAuthHost() . '`';

            $queryDns = /** @lang SQL */
                'GRANT ALL PRIVILEGES ON `' . $this->installData->getDbName() . '`.* 
                  TO `' . $this->installData->getDbUser() . '`@`' . $this->installData->getDbAuthHostDns() . '`';

            try {
                $dbc->exec($query);
                $dbc->exec($queryDns);
                $dbc->exec('FLUSH PRIVILEGES');
            } catch (PDOException $e) {
                $this->rollback();

                throw new SPException(
                    sprintf(__('Error al establecer permisos de la BBDD (\'%s\')'), $e->getMessage()),
                    SPException::CRITICAL,
                    __u('Verifique los permisos del usuario de la Base de Datos')
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
        $query = /** @lang SQL */
            'SELECT COUNT(*) FROM information_schema.schemata WHERE `schema_name` = ? LIMIT 1';

        $sth = $this->dbs->getConnectionSimple()->prepare($query);
        $sth->execute([$this->installData->getDbName()]);

        return ((int)$sth->fetchColumn() > 0);
    }

    /**
     * @throws SPException
     */
    public function rollback()
    {
        $dbc = $this->dbs->getConnectionSimple();

        $dbc->exec('DROP DATABASE IF EXISTS `' . $this->installData->getDbName() . '`');
        $dbc->exec('DROP USER `' . $this->installData->getDbUser() . '`@`' . $this->installData->getDbAuthHost() . '`');
        $dbc->exec('DROP USER `' . $this->installData->getDbUser() . '`@`' . $this->installData->getDbAuthHostDns() . '`');
//            $this->DB->exec('DROP USER `' . $this->InstallData->getDbUser() . '`@`%`');

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
                __u('No es posible usar la Base de Datos para crear la estructura. Compruebe los permisos y que no exista.')
            );
        }

        // Leemos el archivo SQL para crear las tablas de la BBDD
        $handle = fopen($fileName, 'rb');

        if ($handle) {
            while (!feof($handle)) {
                $buffer = stream_get_line($handle, 1000000, ";\n");

                if (strlen(trim($buffer)) > 0 && strpos($buffer, '--') !== 0) {
                    try {
                        $query = str_replace("\n", '', $buffer);
                        $dbc->query($query);
                    } catch (PDOException $e) {
                        $this->rollback();

                        debugLog($e->getMessage());

                        throw new SPException(
                            sprintf(__('Error al crear la BBDD (\'%s\')'), $e->getMessage()),
                            SPException::CRITICAL,
                            __u('Error al crear la estructura de la Base de Datos.')
                        );
                    }
                }
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