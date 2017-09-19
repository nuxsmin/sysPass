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

namespace SP\Core;

use PDO;
use PDOException;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Config\ConfigDB;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\GroupData;
use SP\DataModel\InstallData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserLoginData;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserPass;
use SP\Storage\DBUtil;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de instalar sysPass.
 */
class Installer
{
    /**
     * @var PDO Instancia de conexión a la BD
     */
    private $DB;
    /**
     * @var InstallData
     */
    private $InstallData;
    /**
     * @var ConfigData
     */
    private $Config;

    /**
     * Installer constructor.
     *
     * @param InstallData $InstallData
     */
    public function __construct(InstallData $InstallData)
    {
        $this->InstallData = $InstallData;
    }

    /**
     * Iniciar instalación.
     *
     * @return bool
     * @throws SPException
     */
    public function install()
    {
        $this->checkData();

        $this->Config = Config::getConfig();

        // Generate a random salt that is used to salt the local user passwords
        $this->Config->setPasswordSalt(Util::generateRandomBytes(30));
        $this->Config->setConfigVersion(implode(Util::getVersion(true)));

        if (preg_match('/unix:(.*)/', $this->InstallData->getDbHost(), $match)) {
            $this->InstallData->setDbSocket($match[1]);
        } elseif (preg_match('/(.*):(\d{1,5})/', $this->InstallData->getDbHost(), $match)) {
            $this->InstallData->setDbHost($match[1]);
            $this->InstallData->setDbPort($match[2]);
        } else {
            $this->InstallData->setDbPort(3306);
        }

        if (!preg_match('/(localhost|127.0.0.1)/', $this->InstallData->getDbHost())) {
            $this->InstallData->setDbAuthHost($_SERVER['SERVER_ADDR']);
            $this->InstallData->setDbAuthHostDns(gethostbyaddr($_SERVER['SERVER_ADDR']));
        } else {
            $this->InstallData->setDbAuthHost('localhost');
        }

        // Set DB connection info
        $this->Config->setDbHost($this->InstallData->getDbHost());
        $this->Config->setDbSocket($this->InstallData->getDbSocket());
        $this->Config->setDbPort($this->InstallData->getDbPort());
        $this->Config->setDbName($this->InstallData->getDbName());

        // Set site config
        $this->Config->setSiteLang($this->InstallData->getSiteLang());

        $this->connectDatabase();
        $this->setupMySQLDatabase();
        $this->checkConnection();
        $this->createAdminAccount();

        ConfigDB::setValue('version', implode(Util::getVersion(true)));

        $this->Config->setInstalled(true);
        Config::saveConfig($this->Config);

        return true;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function checkData()
    {
        if (!$this->InstallData->getAdminLogin()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                __('Indicar nombre de usuario admin', false),
                __('Usuario admin para acceso a la aplicación', false));
        } elseif (!$this->InstallData->getAdminPass()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                __('Indicar la clave de admin', false),
                __('Clave del usuario admin de la aplicación', false));
        } elseif (!$this->InstallData->getMasterPassword()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                __('Indicar la clave maestra', false),
                __('Clave maestra para encriptar las claves', false));
        } elseif (strlen($this->InstallData->getMasterPassword()) < 11) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                __('Clave maestra muy corta', false),
                __('La longitud de la clave maestra ha de ser mayor de 11 caracteres', false));
        } elseif (!$this->InstallData->getDbAdminUser()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                __('Indicar el usuario de la BBDD', false),
                __('Usuario con permisos de administrador de la Base de Datos', false));
        } elseif (!$this->InstallData->getDbAdminPass()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                __('Indicar la clave de la BBDD'),
                __('Clave del usuario administrador de la Base de Datos'));
        } elseif (!$this->InstallData->getDbName()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                __('Indicar el nombre de la BBDD', false),
                __('Nombre para la BBDD de la aplicación pej. syspass', false));
        } elseif (substr_count($this->InstallData->getDbName(), '.') >= 1) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                __('El nombre de la BBDD no puede contener "."', false),
                __('Elimine los puntos del nombre de la Base de Datos', false));
        } elseif (!$this->InstallData->getDbHost()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                __('Indicar el servidor de la BBDD', false),
                __('Servidor donde se instalará la Base de Datos', false));
        }
    }

    /**
     * Conectar con la BBDD.
     * Comprobar si la conexión con la base de datos para sysPass es posible con
     * los datos facilitados.
     *
     * @throws SPException
     */
    private function connectDatabase()
    {
        try {
            if (null !== $this->InstallData->getDbSocket()) {
                $dsn = 'mysql:unix_socket=' . $this->InstallData->getDbSocket() . ';charset=utf8';
            } else {
                $dsn = 'mysql:host=' . $this->InstallData->getDbHost() . ';dbport=' . $this->InstallData->getDbPort() . ';charset=utf8';
            }
            $this->DB = new PDO($dsn, $this->InstallData->getDbAdminUser(), $this->InstallData->getDbAdminPass());
            $this->DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL,
                __('No es posible conectar con la BD', false),
                __('Compruebe los datos de conexión') . '<br>' . $e->getMessage());
        }
    }

    /**
     * Configurar la base de datos.
     * Esta función crea la base de datos y el usuario necesario para sysPass.
     *
     * @throws SPException
     */
    private function setupMySQLDatabase()
    {
        // Si no es modo hosting se crea un hash para la clave y un usuario con prefijo "sp_" para la DB
        if (!$this->InstallData->isHostingMode()) {
            $this->InstallData->setDbPass(Util::randomPassword());
            $this->InstallData->setDbUser(substr('sp_' . $this->InstallData->getAdminLogin(), 0, 16));

            // Comprobar si el usuario sumistrado existe
            $query = /** @lang SQL */
                'SELECT COUNT(*) FROM mysql.user WHERE user = ? AND host = ?';

            try {
                $sth = $this->DB->prepare($query);
                $sth->execute([$this->InstallData->getDbUser(), $this->InstallData->getDbAuthHost()]);

                // Si no existe el usuario, se intenta crear
                if ((int)$sth->fetchColumn() === 0
                    // Se comprueba si el nuevo usuario es distinto del creado en otra instalación
                    && $this->InstallData->getDbUser() != $this->Config->getDbUser()
                ) {
                    $this->createDBUser();
                }
            } catch (PDOException $e) {
                throw new SPException(SPException::SP_CRITICAL,
                    sprintf(__('No es posible comprobar el usuario de sysPass') . ' (%s)', $this->InstallData->getAdminLogin()),
                    __('Compruebe los permisos del usuario de conexión a la BD', false));
            }

            // Guardar el nuevo usuario/clave de conexión a la BD
            $this->Config->setDbUser($this->InstallData->getDbUser());
            $this->Config->setDbPass($this->InstallData->getDbPass());
        } else {
            // Guardar el usuario/clave de conexión a la BD
            $this->Config->setDbUser($this->InstallData->getDbAdminUser());
            $this->Config->setDbPass($this->InstallData->getDbAdminPass());
        }

        try {
            $this->createMySQLDatabase();
            $this->createDBStructure();
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * Crear el usuario para conectar con la base de datos.
     * Esta función crea el usuario para conectar con la base de datos.
     * Si se marca en modo hosting, no se crea el usuario.
     *
     * @throws SPException
     */
    private function createDBUser()
    {
        if ($this->InstallData->isHostingMode()) {
            return;
        }

        $query = /** @lang SQL */
            'CREATE USER `' . $this->InstallData->getDbUser() . '`@`' . $this->InstallData->getDbAuthHost() . '` IDENTIFIED BY \'' . $this->InstallData->getDbPass() . '\'';

        $queryDns = /** @lang SQL */
            'CREATE USER `' . $this->InstallData->getDbUser() . '`@`' . $this->InstallData->getDbAuthHostDns() . '` IDENTIFIED BY \'' . $this->InstallData->getDbPass() . '\'';

        try {
            $this->DB->exec($query);
            $this->DB->exec($queryDns);
            $this->DB->exec('FLUSH PRIVILEGES');
        } catch (PDOException $e) {
            throw new SPException(
                SPException::SP_CRITICAL,
                sprintf(__('Error al crear el usuario de conexión a MySQL \'%s\'', false), $this->InstallData->getDbUser()),
                $e->getMessage());
        }
    }

    /**
     * Crear la base de datos en MySQL.
     *
     * @throws SPException
     */
    private function createMySQLDatabase()
    {
        $checkDatabase = $this->checkDatabaseExist();

        if ($checkDatabase && !$this->InstallData->isHostingMode()) {
            throw new SPException(SPException::SP_CRITICAL,
                __('La BBDD ya existe', false),
                __('Indique una nueva Base de Datos o elimine la existente', false));
        } elseif (!$checkDatabase && $this->InstallData->isHostingMode()) {
            throw new SPException(SPException::SP_CRITICAL,
                __('La BBDD no existe', false),
                __('Es necesario crearla y asignar los permisos necesarios', false));
        }

        if (!$this->InstallData->isHostingMode()) {

            try {
                $this->DB->exec(/** @lang SQL */
                    'CREATE SCHEMA `' . $this->InstallData->getDbName() . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
            } catch (PDOException $e) {
                throw new SPException(SPException::SP_CRITICAL,
                    sprintf(__('Error al crear la BBDD') . ' (%s)', $e->getMessage()),
                    __('Verifique los permisos del usuario de la Base de Datos', false));
            }

            $query = /** @lang SQL */
                'GRANT ALL PRIVILEGES ON `' . $this->InstallData->getDbName() . '`.* 
                  TO `' . $this->InstallData->getDbUser() . '`@`' . $this->InstallData->getDbAuthHost() . '`';

            $queryDns = /** @lang SQL */
                'GRANT ALL PRIVILEGES ON `' . $this->InstallData->getDbName() . '`.* 
                  TO `' . $this->InstallData->getDbUser() . '`@`' . $this->InstallData->getDbAuthHostDns() . '`';

            try {
                $this->DB->exec($query);
                $this->DB->exec($queryDns);
                $this->DB->exec('FLUSH PRIVILEGES');
            } catch (PDOException $e) {
                $this->rollback();

                throw new SPException(SPException::SP_CRITICAL,
                    sprintf(__('Error al establecer permisos de la BBDD (\'%s\')'), $e->getMessage()),
                    __('Verifique los permisos del usuario de la Base de Datos', false));
            }
        }
    }

    /**
     * Comprobar si la base de datos indicada existe.
     *
     * @return bool
     */
    private function checkDatabaseExist()
    {
        $query = /** @lang SQL */
            'SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ? LIMIT 1';

        $sth = $this->DB->prepare($query);
        $sth->execute([$this->InstallData->getDbName()]);

        return ((int)$sth->fetchColumn() > 0);
    }

    /**
     * Deshacer la instalación en caso de fallo.
     * Esta función elimina la base de datos y el usuario de sysPass
     */
    private function rollback()
    {
        try {
            $this->DB->exec('DROP DATABASE IF EXISTS `' . $this->InstallData->getDbName() . '`');
            $this->DB->exec('DROP USER `' . $this->InstallData->getDbUser() . '`@`' . $this->InstallData->getDbAuthHost() . '`');
            $this->DB->exec('DROP USER `' . $this->InstallData->getDbUser() . '`@`' . $this->InstallData->getDbAuthHostDns() . '`');
//            $this->DB->exec('DROP USER `' . $this->InstallData->getDbUser() . '`@`%`');

            debugLog('Rollback');

            return true;
        } catch (PDOException $e) {
            debugLog($e->getMessage());

            return false;
        }
    }

    /**
     * Crear la estructura de la base de datos.
     * Esta función crea la estructura de la base de datos a partir del archivo dbsctructure.sql.
     *
     * @throws SPException
     */
    private function createDBStructure()
    {
        $fileName = SQL_PATH . DIRECTORY_SEPARATOR . 'dbstructure.sql';

        if (!file_exists($fileName)) {
            throw new SPException(SPException::SP_CRITICAL,
                __('El archivo de estructura de la BBDD no existe', false),
                __('No es posible crear la BBDD de la aplicación. Descárguela de nuevo.', false));
        }

        try {
            // Usar la base de datos de sysPass
            $this->DB->exec('USE `' . $this->InstallData->getDbName() . '`');
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL,
                sprintf(__('Error al seleccionar la BBDD') . ' \'%s\' (%s)', $this->InstallData->getDbName(), $e->getMessage()),
                __('No es posible usar la Base de Datos para crear la estructura. Compruebe los permisos y que no exista.', false));
        }

        // Leemos el archivo SQL para crear las tablas de la BBDD
        $handle = fopen($fileName, 'rb');

        if ($handle) {
            while (!feof($handle)) {
                $buffer = stream_get_line($handle, 1000000, ";\n");
                if (strlen(trim($buffer)) > 0) {
                    try {
                        $query = str_replace("\n", '', $buffer);
                        $this->DB->query($query);
                    } catch (PDOException $e) {
                        $this->rollback();

                        throw new SPException(SPException::SP_CRITICAL,
                            sprintf(__('Error al crear la BBDD') . ' (%s)', $e->getMessage()),
                            __('Error al crear la estructura de la Base de Datos.', false));
                    }
                }
            }
        }
    }

    /**
     * Comprobar la conexión a la BBDD
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function checkConnection()
    {
        if (!DBUtil::checkDatabaseExist()) {
            $this->rollback();

            throw new SPException(SPException::SP_CRITICAL,
                __('Error al comprobar la base de datos', false),
                __('Intente de nuevo la instalación', false));
        }
    }

    /**
     * Crear el usuario admin de sysPass.
     * Esta función crea el grupo, perfil y usuario 'admin' para utilizar sysPass.
     *
     * @throws SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    private function createAdminAccount()
    {
        try {
            $GroupData = new GroupData();
            $GroupData->setUsergroupName('Admins');
            $GroupData->setUsergroupDescription('sysPass Admins');

            Group::getItem($GroupData)->add();

            $ProfileData = new ProfileData();
            $ProfileData->setUserprofileName('Admin');

            Profile::getItem($ProfileData)->add();

            // Datos del usuario
            $UserData = new UserLoginData();
            $UserData->setUserGroupId($GroupData->getUsergroupId());
            $UserData->setUserProfileId($ProfileData->getUserprofileId());
            $UserData->setUserLogin($this->InstallData->getAdminLogin());
            $UserData->setLogin($this->InstallData->getAdminLogin());
            $UserData->setUserPass($this->InstallData->getAdminPass());
            $UserData->setLoginPass($this->InstallData->getAdminPass());
            $UserData->setUserName('Admin');
            $UserData->setUserIsAdminApp(1);

            User::getItem($UserData)->add();

            // Guardar el hash de la clave maestra
            ConfigDB::setCacheConfigValue('masterPwd', Hash::hashKey($this->InstallData->getMasterPassword()));
            ConfigDB::setCacheConfigValue('lastupdatempass', time());
            ConfigDB::writeConfig(true);

            if (!UserPass::updateUserMPass($this->InstallData->getMasterPassword(), $UserData)) {
                throw new SPException(SPException::SP_CRITICAL,
                    __('Error al actualizar la clave maestra del usuario "admin"', false));
            }
        } catch (\Exception $e) {
            $this->rollback();
            throw new SPException(SPException::SP_CRITICAL,
                $e->getMessage(),
                __('Informe al desarrollador', false));
        }
    }
}