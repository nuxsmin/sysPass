<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

define('IS_INSTALLER',1);

/**
 * Esta clase es la encargada de instalar sysPass.
 */
class Installer
{
    private static $_dbuser;
    private static $_dbname;
    private static $_dbhost;
    private static $_dbc; // Database connection
    private static $_username;
    private static $_password;
    private static $_masterPassword;
    private static $_isHostingMode;

    /**
     * Iniciar instalación.
     *
     * @param array $options datos de instalación
     * @return array resultado del proceso
     */
    public static function install($options)
    {
        $error = array();

        if (empty($options['adminlogin'])) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar nombre de usuario admin'),
                'hint' => _('Usuario admin para acceso a la aplicación'));
        }
        if (empty($options['adminpass'])) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar la clave de admin'),
                'hint' => _('Clave del usuario admin de la aplicación'));
        }

        if (empty($options['masterpassword'])) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar la clave maestra'),
                'hint' => _('Clave maestra para encriptar las claves'));
        }
        if (strlen($options['masterpassword']) < 11) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Clave maestra muy corta'),
                'hint' => _('La longitud de la clave maestra ha de ser mayor de 11 caracteres'));
        }

        if (empty($options['dbuser'])) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar el usuario de la BBDD'),
                'hint' => _('Usuario con permisos de administrador de la Base de Datos'));
        }
        if (empty($options['dbpass'])) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar la clave de la BBDD'),
                'hint' => _('Clave del usuario administrador de la Base de Datos'));
        }
        if (empty($options['dbname'])) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar el nombre de la BBDD'),
                'hint' => _('Nombre para la BBDD de la aplicación pej. syspass'));
        }
        if (substr_count($options['dbname'], '.') >= 1) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('El nombre de la BBDD no puede contener "."'),
                'hint' => _('Elimine los puntos del nombre de la Base de Datos'));
        }

        if (empty($options['dbhost'])) {
            $error[] = array(
                'type' => SPException::SP_CRITICAL,
                'description' => _('Indicar el servidor de la BBDD'),
                'hint' => _('Servidor donde se instalará la Base de Datos'));
        }

        if (count($error) == 0) { //no errors, good
            self::$_username = htmlspecialchars_decode($options['adminlogin']);
            self::$_password = htmlspecialchars_decode($options['adminpass']);
            self::$_masterPassword = htmlspecialchars_decode($options['masterpassword']);
            self::$_dbname = $options['dbname'];
            self::$_dbhost = $options['dbhost'];

            //generate a random salt that is used to salt the local user passwords
            $salt = Util::generate_random_bytes(30);
            Config::setValue('passwordsalt', $salt);
            Config::setValue('version', implode(Util::getVersion(true)));

            $dbadmin = $options['dbuser'];
            $dbpass = $options['dbpass'];

            if (preg_match('/(.*):(\d{1,5})/', $options['dbhost'], $match)){
                $dbhost = $match[1];
                $dbport = $match[2];
            } else {
                $dbhost = $options['dbhost'];
                $dbport = 3306;
            }

            self::$_isHostingMode = (isset($options['hostingmode'])) ? 1 : 0;

            // Save DB connection info
            Config::setValue('dbhost', $dbhost);
            Config::setValue('dbname', self::$_dbname);

            // Set some basic configuration options
            Config::setDefaultValues();

            try {
                self::checkDatabaseAdmin($dbhost, $dbadmin, $dbpass, $dbport);
                self::setupMySQLDatabase();
                self::createAdminAccount();
            } catch (SPException $e) {
                $error[] = array(
                    'type' => $e->getType(),
                    'description' => $e->getMessage(),
                    'hint' => $e->getHint());
                return ($error);
            }

            Config::setConfigDbValue('version', implode(Util::getVersion(true)));
            Config::setValue('installed', 1);
        }

        return ($error);
    }

    /**
     * Comprobar la conexión con la BBDD.
     * Comprobar si la conexión con la base de datos para sysPass es posible con
     * los datos facilitados.
     *
     * @param string $dbhost host de conexión
     * @param string $dbadmin usuario de conexión
     * @param string $dbpass clave de conexión
     * @param string $dbport puerto de conexión
     * @throws SPException
     */
    private static function checkDatabaseAdmin($dbhost, $dbadmin, $dbpass, $dbport)
    {
        try {
            $dsn = 'mysql:host=' . $dbhost . ';dbport=' . $dbport . ';charset=utf8';
            self::$_dbc = new \PDO($dsn, $dbadmin, $dbpass);
        } catch (\PDOException $e){
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
        $oldUser = Config::getValue('dbuser', false);

        //this should be enough to check for admin rights in mysql
        $query = "SELECT user "
            . "FROM mysql.user "
            . "WHERE user='" . self::$_username . "' and host='" . self::$_dbhost . "';";

        // Hash DB connection password
        $dbpassword = (!self::$_isHostingMode) ? md5(time() . self::$_password) : self::$_password;

        self::$_dbuser = (!self::$_isHostingMode) ? substr('sp_' . self::$_username, 0, 16) : self::$_username;

        if (!self::$_dbc->query($query)) {
            if (self::$_dbuser != $oldUser) {
                self::createDBUser($dbpassword);

                Config::setValue('dbuser', self::$_dbuser);
                Config::setValue('dbpass', $dbpassword);
            }
        } else {
            if (self::$_username != $oldUser) {
                Config::setValue('dbuser', self::$_dbuser);
                Config::setValue('dbpass', $dbpassword);
            }
        }

        self::createMySQLDatabase($dbpassword);

        if (!self::checkDatabaseExist()) {
            self::createDBStructure();
        } else {
            throw new SPException(SPException::SP_CRITICAL
                , _('La BBDD ya existe')
                , _('Indique una nueva Base de Datos o elimine la existente'));
        }

//        self::$dbc->close();
    }

    /**
     * Crear el usuario para conectar con la base de datos.
     * Esta función crea el usuario para conectar con la base de datos.
     * Si se marca en modo hosting, no se crea el usuario.
     *
     * @param string $dbpassword clave del usuario de sysPass
     * @throws SPException
     */
    private static function createDBUser($dbpassword)
    {
        if (self::$_isHostingMode) {
            return;
        }

        $query = "CREATE USER '" . self::$_dbuser . "'@'localhost' IDENTIFIED BY '" . $dbpassword . "'";

        try {
            self::$_dbc->query($query);
        } catch (\PDOException $e){
            throw new SPException(SPException::SP_CRITICAL
                , _('El usuario de MySQL ya existe') . " (" . self::$_dbuser . ")"
                , _('Indique un nuevo usuario o elimine el existente'));
        }
    }

    /**
     * Crear la base de datos.
     *
     * @param string $dbpassword clave del usuario de sysPass
     * @throws SPException
     */
    private static function createMySQLDatabase($dbpassword)
    {
        $query = "CREATE DATABASE IF NOT EXISTS `" . self::$_dbname . "`";

        try {
            self::$_dbc->query($query);
        } catch (\PDOException $e){
            throw new SPException(SPException::SP_CRITICAL
                , _('Error al crear la BBDD') . " (" . $e->getMessage() . ")"
                , _('Verifique los permisos del usuario de la Base de Datos'));
        }

        if (!self::$_isHostingMode) {
            $query = "GRANT ALL PRIVILEGES ON `" . self::$_dbname . "`.* TO '" . self::$_dbuser . "'@'" . self::$_dbhost . "' IDENTIFIED BY '$dbpassword';";

            try {
                self::$_dbc->query($query);
            } catch (\PDOException $e){
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
            . "FROM information_schema.tables "
            . "WHERE table_schema = '" . self::$_dbname . "' "
            . "AND table_name = 'usrData' LIMIT 1";

        return (intval(self::$_dbc->query($query)->fetchColumn()) === 0);
    }

    /**
     * Crear la estructura de la base de datos.
     * Esta función crea la estructura de la base de datos a partir del archivo dbsctructure.sql.
     *
     * @throws SPException
     */
    private static function createDBStructure()
    {
        $fileName = dirname(__FILE__) . '/dbstructure.sql';

        if (!file_exists($fileName)) {
            throw new SPException(SPException::SP_CRITICAL
                , _('El archivo de estructura de la BBDD no existe')
                , _('No es posible crear la BBDD de la aplicación. Descárguela de nuevo.'));
        }

        // Usar la base de datos de sysPass
        try {
            self::$_dbc->query('USE ' . self::$_dbname);
        } catch (\PDOException $e){
            throw new SPException(SPException::SP_CRITICAL
                , _('Error al seleccionar la BBDD') . " '" . self::$_dbname . "' (" . $e->getMessage() . ")"
                , _('No es posible usar la Base de Datos para crear la estructura. Compruebe los permisos y que no exista.'));
        }

        // Leemos el archivo SQL para crear las tablas de la BBDD
        $handle = fopen($fileName, 'rb');

        if ($handle) {
            while (!feof($handle)) {
                $buffer = stream_get_line($handle, 1000000, ";\n");
                if (strlen(trim($buffer)) > 0) {
                    try {
                        self::$_dbc->query($buffer);
                    } catch (\PDOException $e) {
                        // drop database on error
                        self::$_dbc->query("DROP DATABASE " . self::$_dbname . ";");

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
        $user = new Users;

        // Datos del grupo
        Groups::$groupName = "Admins";
        Groups::$groupDescription = "Admins";

        if (!Groups::addGroup()) {
            self::rollback();

            throw new SPException("critical"
                , _('Error al crear el grupo "admin"')
                , _('Informe al desarrollador'));
        }

        // Establecer el id de grupo del usuario al recién creado
        $user->userGroupId = Groups::$queryLastId;

        $profileProp = array("pAccView" => 1,
            "pAccViewPass" => 1,
            "pAccViewHistory" => 1,
            "pAccEdit" => 1,
            "pAccEditPass" => 1,
            "pAccAdd" => 1,
            "pAccDel" => 1,
            "pAccFiles" => 1,
            "pConfig" => 1,
            "pConfigMpw" => 1,
            "pConfigBack" => 1,
            "pAppMgmtCat" => 1,
            "pAppMgmtCust" => 1,
            "pUsers" => 1,
            "pGroups" => 1,
            "pProfiles" => 1,
            "pEventlog" => 1);

        Profiles::$profileName = 'Admin';

        if (!Profiles::addProfile($profileProp)) {
            self::rollback();

            throw new SPException("critical"
                , _('Error al crear el perfil "admin"')
                , _('Informe al desarrollador'));
        }

        // Establecer el id de perfil del usuario al recién creado
        $user->userProfileId = Profiles::$queryLastId;

        // Datos del usuario
        $user->userLogin = self::$_username;
        $user->userPass = self::$_password;
        $user->userName = "Admin";
        $user->userIsAdminApp = 1;


        if (!$user->addUser()) {
            self::rollback();

            throw new SPException(SPException::SP_CRITICAL
                , _('Error al crear el usuario "admin"')
                , _('Informe al desarrollador'));
        }

        // Guardar el hash de la clave maestra
        Config::setArrConfigValue('masterPwd', Crypt::mkHashPassword(self::$_masterPassword));
        Config::setArrConfigValue('lastupdatempass', time());
        Config::writeConfigDb(true);

        $user->userId = $user->queryLastId; // Needed for update user's master password

        if (!$user->updateUserMPass(self::$_masterPassword, false)) {
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
            self::$_dbc->query("DROP DATABASE IF EXISTS " . self::$_dbname . ";");
            self::$_dbc->query("DROP USER '" . self::$_dbuser . "'@'" . self::$_dbhost . "';");
            self::$_dbc->query("DROP USER '" . self::$_dbuser . "'@'%';");
        } catch(\PDOException $e){
            Config::deleteKey('dbuser');
            Config::deleteKey('dbpass');
        }
    }

}