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

namespace SP\Core;

use PDO;
use PDOException;
use SP\Config\Config;
use SP\Config\ConfigDB;
use SP\DataModel\GroupData;
use SP\DataModel\ProfileData;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Users\User;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

define('IS_INSTALLER', 1);

/**
 * Esta clase es la encargada de instalar sysPass.
 */
class Installer
{
    /**
     * @var string Usuario de la BD
     */
    private static $dbUser;
    /**
     * @var string Clave de la BD
     */
    private static $dbPass;
    /**
     * @var string Nombre de la BD
     */
    private static $dbName;
    /**
     * @var string Host de la BD
     */
    private static $dbHost;
    /**
     * @var PDO Instancia a de conexión a la BD
     */
    private static $DB;
    /**
     * @var string Usuario 'admin' de sysPass
     */
    private static $username;
    /**
     * @var string Clave del usuario 'admin' de sysPass
     */
    private static $password;
    /**
     * @var string Clave maestra de sysPass
     */
    private static $masterPassword;
    /**
     * @var bool Activar/desactivar Modo hosting
     */
    private static $isHostingMode;

    /**
     * @param string $dbname
     */
    public static function setDbName($dbname)
    {
        self::$dbName = $dbname;
    }

    /**
     * @param string $username
     */
    public static function setUsername($username)
    {
        self::$username = $username;
    }

    /**
     * @param string $password
     */
    public static function setPassword($password)
    {
        self::$password = $password;
    }

    /**
     * @param string $masterPassword
     */
    public static function setMasterPassword($masterPassword)
    {
        self::$masterPassword = $masterPassword;
    }

    /**
     * @param boolean $isHostingMode
     */
    public static function setIsHostingMode($isHostingMode)
    {
        self::$isHostingMode = $isHostingMode;
    }

    /**
     * Iniciar instalación.
     *
     * @return array resultado del proceso
     */
    public static function install()
    {
        $error = array();

        if (!self::$username) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar nombre de usuario admin'),
                'hint' => _('Usuario admin para acceso a la aplicación'));
        } elseif (!self::$password) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar la clave de admin'),
                'hint' => _('Clave del usuario admin de la aplicación'));
        } elseif (!self::$masterPassword) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar la clave maestra'),
                'hint' => _('Clave maestra para encriptar las claves'));
        } elseif (strlen(self::$masterPassword) < 11) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Clave maestra muy corta'),
                'hint' => _('La longitud de la clave maestra ha de ser mayor de 11 caracteres'));
        } elseif (!self::$dbUser) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar el usuario de la BBDD'),
                'hint' => _('Usuario con permisos de administrador de la Base de Datos'));
        } elseif (!self::$dbPass) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar la clave de la BBDD'),
                'hint' => _('Clave del usuario administrador de la Base de Datos'));
        } elseif (!self::$dbName) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar el nombre de la BBDD'),
                'hint' => _('Nombre para la BBDD de la aplicación pej. syspass'));
        } elseif (substr_count(self::$dbName, '.') >= 1) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('El nombre de la BBDD no puede contener "."'),
                'hint' => _('Elimine los puntos del nombre de la Base de Datos'));
        } elseif (!self::$dbHost) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar el servidor de la BBDD'),
                'hint' => _('Servidor donde se instalará la Base de Datos'));
        }

        if (count($error) === 0) { //no errors, good
            $Config = Config::getConfig();

            // Generate a random salt that is used to salt the local user passwords
            $Config->setPasswordSalt(Util::generate_random_bytes(30));
            $Config->setConfigVersion(implode(Util::getVersion(true)));

            if (preg_match('/(.*):(\d{1,5})/', self::$dbHost, $match)) {
                self::setDbHost($match[1]);
                $dbport = $match[2];
            } else {
                $dbport = 3306;
            }

            // Save DB connection info
            $Config->setDbHost(self::$dbHost);
            $Config->setDbName(self::$dbName);


            try {
                self::checkDatabaseAdmin(self::$dbHost, self::$dbUser, self::$dbPass, $dbport);
                self::setupMySQLDatabase();
                self::createAdminAccount();
                ConfigDB::setValue('version', implode(Util::getVersion(true)));
                $Config->setInstalled(true);
                Config::saveConfig();
            } catch (SPException $e) {
                $error[] = array(
                    'type' => $e->getType(),
                    'description' => $e->getMessage(),
                    'hint' => $e->getHint());
                return $error;
            }
        }

        return $error;
    }

    /**
     * @param string $dbhost
     */
    public static function setDbHost($dbhost)
    {
        self::$dbHost = $dbhost;
    }

    /**
     * Comprobar la conexión con la BBDD.
     * Comprobar si la conexión con la base de datos para sysPass es posible con
     * los datos facilitados.
     *
     * @param string $dbhost  host de conexión
     * @param string $dbadmin usuario de conexión
     * @param string $dbpass  clave de conexión
     * @param string $dbport  puerto de conexión
     * @throws SPException
     */
    private static function checkDatabaseAdmin($dbhost, $dbadmin, $dbpass, $dbport)
    {
        try {
            $dsn = 'mysql:host=' . $dbhost . ';dbport=' . $dbport . ';charset=utf8';
            self::$DB = new PDO($dsn, $dbadmin, $dbpass);
            self::$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL
                , _('No es posible conectar con la BD')
                , _('Compruebe los datos de conexión') . '<br>' . $e->getMessage());
        }
    }

    /**
     * Configurar la base de datos.
     * Esta función crea la base de datos y el usuario necesario para sysPass.
     *
     * @throws SPException
     */
    private static function setupMySQLDatabase()
    {
        // Si no es modo hosting se crea un hash para la clave y un usuario con prefijo "sp_" para la DB
        if (!self::$isHostingMode) {
            self::setDbPass(md5(time() . self::$password));
            self::setDbUser(substr('sp_' . self::$username, 0, 16));

            // Comprobar si el usuario sumistrado existe
            $query = "SELECT COUNT(*) FROM mysql.user WHERE user='" . self::$username . "' AND host='" . self::$dbHost . "'";

            try {
                // Si no existe el usuario, se intenta crear
                if (intval(self::$DB->query($query)->fetchColumn()) === 0) {
                    // Se comprueba si el nuevo usuario es distinto del creado en otra instalación
                    if (self::$dbUser != Config::getConfig()->getDbUser()) {
                        self::createDBUser();
                    }
                }
            } catch (PDOException $e) {
                throw new SPException(SPException::SP_CRITICAL
                    , _('No es posible comprobar el usuario de sysPass') . ' (' . self::$username . ')'
                    , _('Compruebe los permisos del usuario de conexión a la BD'));
            }
        }

        // Guardar el nuevo usuario/clave de conexión a la BD
        Config::getConfig()->setDbUser(self::$dbUser);
        Config::getConfig()->setDbPass(self::$dbPass);

        try {
            self::createMySQLDatabase();
            self::createDBStructure();
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * @param string $dbpass
     */
    public static function setDbPass($dbpass)
    {
        self::$dbPass = $dbpass;
    }

    /**
     * @param string $dbuser
     */
    public static function setDbUser($dbuser)
    {
        self::$dbUser = $dbuser;
    }

    /**
     * Crear el usuario para conectar con la base de datos.
     * Esta función crea el usuario para conectar con la base de datos.
     * Si se marca en modo hosting, no se crea el usuario.
     *
     * @throws SPException
     */
    private static function createDBUser()
    {
        if (self::$isHostingMode) {
            return;
        }

        $query = "CREATE USER '" . self::$dbUser . "'@'localhost' IDENTIFIED BY '" . self::$dbPass . "'";

        try {
            self::$DB->query($query);
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL
                , _('El usuario de MySQL ya existe') . " (" . self::$dbUser . ")"
                , _('Indique un nuevo usuario o elimine el existente'));
        }
    }

    /**
     * Crear la base de datos en MySQL.
     *
     * @throws SPException
     */
    private static function createMySQLDatabase()
    {
        if (self::checkDatabaseExist()) {
            throw new SPException(SPException::SP_CRITICAL
                , _('La BBDD ya existe')
                , _('Indique una nueva Base de Datos o elimine la existente'));
        }

        $query = "CREATE SCHEMA `" . self::$dbName . "` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";

        try {
            self::$DB->query($query);
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL
                , _('Error al crear la BBDD') . " (" . $e->getMessage() . ")"
                , _('Verifique los permisos del usuario de la Base de Datos'));
        }

        if (!self::$isHostingMode) {
            $query = "GRANT ALL PRIVILEGES ON `" . self::$dbName . "`.* TO '" . self::$dbUser . "'@'" . self::$dbHost . "' IDENTIFIED BY '" . self::$dbPass . "';";

            try {
                self::$DB->query($query);
            } catch (PDOException $e) {
                throw new SPException(SPException::SP_CRITICAL
                    , _('Error al establecer permisos de la BBDD') . " (" . $e->getMessage() . ")"
                    , _('Verifique los permisos del usuario de la Base de Datos'));
            }
        }
    }

    /**
     * Comprobar si la base de datos indicada existe.
     *
     * @return bool
     */
    private static function checkDatabaseExist()
    {
        $query = "SELECT COUNT(*) "
            . "FROM information_schema.schemata "
            . "WHERE schema_name = '" . self::$dbName . "' LIMIT 1";

        return (intval(self::$DB->query($query)->fetchColumn()) > 0);
    }

    /**
     * Crear la estructura de la base de datos.
     * Esta función crea la estructura de la base de datos a partir del archivo dbsctructure.sql.
     *
     * @throws SPException
     */
    private static function createDBStructure()
    {
        $fileName = Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'dbstructure.sql';

        if (!file_exists($fileName)) {
            throw new SPException(SPException::SP_CRITICAL
                , _('El archivo de estructura de la BBDD no existe')
                , _('No es posible crear la BBDD de la aplicación. Descárguela de nuevo.'));
        }

        // Usar la base de datos de sysPass
        try {
            self::$DB->query('USE `' . self::$dbName . '`');
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL
                , _('Error al seleccionar la BBDD') . " '" . self::$dbName . "' (" . $e->getMessage() . ")"
                , _('No es posible usar la Base de Datos para crear la estructura. Compruebe los permisos y que no exista.'));
        }

        // Leemos el archivo SQL para crear las tablas de la BBDD
        $handle = fopen($fileName, 'rb');

        if ($handle) {
            while (!feof($handle)) {
                $buffer = stream_get_line($handle, 1000000, ";\n");
                if (strlen(trim($buffer)) > 0) {
                    try {
                        $query = str_replace("\n", '', $buffer);
                        self::$DB->query($query);
                    } catch (PDOException $e) {
                        // drop database on error
                        self::$DB->query("DROP DATABASE IF EXISTS " . self::$dbName . ";");

                        throw new SPException(SPException::SP_CRITICAL
                            , _('Error al crear la BBDD') . ' (' . $e->getMessage() . ')'
                            , _('Error al crear la estructura de la Base de Datos.'));
                    }
                }
            }
        }
    }

    /**
     * Crear el usuario admin de sysPass.
     * Esta función crea el grupo, perfil y usuario 'admin' para utilizar sysPass.
     *
     * @throws SPException
     */
    private static function createAdminAccount()
    {
        $GroupData = new GroupData();
        $GroupData->setUsergroupName('Admins');
        $GroupData->setUsergroupDescription('sysPass Admins');

        try {
            Group::getItem($GroupData)->add();
        } catch (SPException $e) {
            self::rollback();
            throw new SPException(SPException::SP_CRITICAL
                , _('Error al crear el grupo "admin"')
                , _('Informe al desarrollador'));
        }

        $User = new User();

        // Establecer el id de grupo del usuario al recién creado
        $User->setUserGroupId($GroupData->getUsergroupId());

        $ProfileData = new ProfileData();


        $ProfileData->setUserprofileName('Admin');
        $ProfileData->setAccAdd(true);
        $ProfileData->setAccView(true);
        $ProfileData->setAccViewPass(true);
        $ProfileData->setAccViewHistory(true);
        $ProfileData->setAccEdit(true);
        $ProfileData->setAccEditPass(true);
        $ProfileData->setAccDelete(true);
        $ProfileData->setAccFiles(true);
        $ProfileData->setConfigGeneral(true);
        $ProfileData->setConfigEncryption(true);
        $ProfileData->setConfigBackup(true);
        $ProfileData->setConfigImport(true);
        $ProfileData->setMgmCategories(true);
        $ProfileData->setMgmCustomers(true);
        $ProfileData->setMgmUsers(true);
        $ProfileData->setMgmGroups(true);
        $ProfileData->setMgmProfiles(true);
        $ProfileData->setMgmCustomFields(true);
        $ProfileData->setMgmApiTokens(true);
        $ProfileData->setMgmPublicLinks(true);
        $ProfileData->setEvl(true);

        try {
            $Profile = Profile::getItem($ProfileData)->add();
        } catch (SPException $e) {
            self::rollback();
            throw new SPException(SPException::SP_CRITICAL
                , _('Error al crear el perfil "admin"')
                , _('Informe al desarrollador'));
        }

        // Datos del usuario
        $User->setUserLogin(self::$username);
        $User->setUserPass(self::$password);
        $User->setUserName('Admin');
        $User->setUserProfileId($Profile->getItemData()->getUserprofileId());
        $User->setUserIsAdminApp(true);
        $User->setUserIsAdminAcc(false);
        $User->setUserIsDisabled(false);

        if (!$User->addUser()) {
            self::rollback();

            throw new SPException(SPException::SP_CRITICAL
                , _('Error al crear el usuario "admin"')
                , _('Informe al desarrollador'));
        }

        // Guardar el hash de la clave maestra
        ConfigDB::setCacheConfigValue('masterPwd', Crypt::mkHashPassword(self::$masterPassword));
        ConfigDB::setCacheConfigValue('lastupdatempass', time());
        ConfigDB::writeConfig(true);

        if (!$User->updateUserMPass(self::$masterPassword)) {
            self::rollback();

            throw new SPException(SPException::SP_CRITICAL
                , _('Error al actualizar la clave maestra del usuario "admin"')
                , _('Informe al desarrollador'));
        }
    }

    /**
     * Deshacer la instalación en caso de fallo.
     * Esta función elimina la base de datos y el usuario de sysPass
     */
    private static function rollback()
    {
        try {
            self::$DB->query("DROP DATABASE IF EXISTS " . self::$dbName . ";");
            self::$DB->query("DROP USER '" . self::$dbUser . "'@'" . self::$dbHost . "';");
            self::$DB->query("DROP USER '" . self::$dbUser . "'@'%';");
        } catch (PDOException $e) {
            return false;
        }
    }

}