<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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

// TODO: modo hosting

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Extender la clase Exception para mostrar ayuda en los mensajes
 */
class InstallerException extends Exception {

    private $type;
    private $hint;

    public function __construct($type, $message, $hint, $code = 0, Exception $previous = null) {
        $this->type = $type;
        $this->hint = $hint;
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message} ({$this->hint})\n";
    }

    public function getHint() {
        return $this->hint;
    }

    public function getType() {
        return $this->type;
    }

}

/**
 * Esta clase es la encargada de instalar sysPass.
 */
class SP_Installer {

    private static $dbuser;
    private static $dbname;
    private static $dbhost;
    private static $dbc; // Database connection
    private static $username;
    private static $password;
    private static $masterPassword;
    private static $isHostingMode;

    /**
     * @brief Iniciar instalación
     * @param array $options datos de instalación
     * @return array resultado del proceso
     */    
    public static function install($options) {
        $error = array();

        if (empty($options['adminlogin'])) {
            $error[] = array(
                'type' => 'critical',
                'description' => _('Indicar nombre de usuario admin'),
                'hint' => _('Usuario admin para acceso a la aplicación'));
        }
        if (empty($options['adminpass'])) {
            $error[] = array(
                'type' => 'critical',
                'description' => _('Indicar la clave de admin'),
                'hint' => _('Clave del usuario admin de la aplicación'));
        }

        if (empty($options['masterpassword'])) {
            $error[] = array(
                'type' => 'critical',
                'description' => _('Indicar la clave maestra'),
                'hint' => _('Clave maestra para encriptar las claves'));
        }
        if (strlen($options['masterpassword']) < 11) {
            $error[] = array(
                'type' => 'critical',
                'description' => _('Clave maestra muy corta'),
                'hint' => _('La longitud de la clave maestra ha de ser mayor de 11 caracteres'));
        }

        if (empty($options['dbuser'])) {
            $error[] = array(
                'type' => 'critical',
                'description' => _('Indicar el usuario de la BBDD'),
                'hint' => _('Usuario con permisos de administrador de la Base de Datos'));
        }
        if (empty($options['dbpass'])) {
            $error[] = array(
                'type' => 'critical',
                'description' => _('Indicar la clave de la BBDD'),
                'hint' => _('Clave del usuario administrador de la Base de Datos'));
        }
        if (empty($options['dbname'])) {
            $error[] = array(
                'type' => 'critical',
                'description' => _('Indicar el nombre de la BBDD'),
                'hint' => _('Nombre para la BBDD de la aplicación pej. syspass'));
        }
        if (substr_count($options['dbname'], '.') >= 1) {
            $error[] = array(
                'type' => 'critical',
                'description' => _('El nombre de la BBDD no puede contener "."'),
                'hint' => _('Elimine los puntos del nombre de la Base de Datos'));
        }

        if (empty($options['dbhost'])) {
            $error[] = array(
                'type' => 'critical',
                'description' => _('Indicar el servidor de la BBDD'),
                'hint' => _('Servidor donde se instalará la Base de Datos'));
        }

        if (count($error) == 0) { //no errors, good
            self::$username = htmlspecialchars_decode($options['adminlogin']);
            self::$password = htmlspecialchars_decode($options['adminpass']);
            self::$masterPassword = htmlspecialchars_decode($options['masterpassword']);
            self::$dbname = $options['dbname'];
            self::$dbhost = $options['dbhost'];

            //generate a random salt that is used to salt the local user passwords
            $salt = SP_Util::generate_random_bytes(30);
            SP_Config::setValue('passwordsalt', $salt);
            SP_Config::setValue('version', implode('.', SP_Util::getVersion()));

            $dbadmin = $options['dbuser'];
            $dbpass = $options['dbpass'];
            $dbhost = $options['dbhost'];
            
            self::$isHostingMode = ( isset($options['hostingmode']) ) ? 1: 0;

            // Save DB connection info
            SP_Config::setValue('dbhost', $dbhost);
            SP_Config::setValue('dbname', self::$dbname);

            // Set some basic configuration options
            SP_Config::setDefaultValues();

            try {
                self::checkDatabaseAdmin($dbhost, $dbadmin, $dbpass);
                self::setupMySQLDatabase();
                self::createAdminAccount();
            } catch (InstallerException $e) {
                $error[] = array('type' => $e->getType(), 'description' => $e->getMessage(), 'hint' => $e->getHint());
                return($error);
            }

            SP_Config::$arrConfigValue['version'] = SP_Util::getVersionString();
            SP_Config::writeConfig(TRUE);
            SP_Config::setValue('installed', 1);
        }

        return($error);
    }

    /**
     * @brief Comprobar la conexión con la BBDD.
     * @param string $dbhost host de conexión
     * @param string $dbadmin usuario de conexión
     * @param string $dbpass clave de conexión
     * @return none
     *
     * Comprobar si la conexión con la base de datos para sysPass es posible con
     * los datos facilitados.
     */
    private static function checkDatabaseAdmin($dbhost, $dbadmin, $dbpass) {
        self::$dbc = new mysqli($dbhost, $dbadmin, $dbpass);

        if (self::$dbc->connect_errno) {
            throw new InstallerException('critical'
            , _('El usuario/clave de MySQL no es correcto')
            , _('Verifique el usuario de conexión con la Base de Datos'));
        }
    }

    /**
     * @brief Configurar la base de datos
     * @return none
     *
     * Esta función crea la base de datos y el usuario necesario para sysPass.
     */
    private static function setupMySQLDatabase() {
        $oldUser = SP_Config::getValue('dbuser', false);

        //this should be enough to check for admin rights in mysql
        $query = "SELECT user "
                . "FROM mysql.user "
                . "WHERE user='" . self::$username . "' and host='" . self::$dbhost . "';";

        // Hash DB connection password
        $dbpassword = ( ! self::$isHostingMode ) ? md5(time() . self::$password) : self::$password;
        
        self::$dbuser = ( ! self::$isHostingMode ) ? substr('sp_' . self::$username, 0, 16) : self::$username;

        if (!self::$dbc->query($query)) {
            if (self::$dbuser != $oldUser) {
                self::createDBUser($dbpassword);

                SP_Config::setValue('dbuser', self::$dbuser);
                SP_Config::setValue('dbpass', $dbpassword);
            }
        } else {
            if (self::$username != $oldUser) {
                SP_Config::setValue('dbuser', self::$dbuser);
                SP_Config::setValue('dbpass', $dbpassword);
            }
        }

        self::createMySQLDatabase($dbpassword);

        if (!self::checkDatabaseExist()) {
            self::createDBStructure();
        } else {
            throw new InstallerException('critical'
            , _('La BBDD ya existe')
            , _('Indique una nueva Base de Datos o elimine la existente'));
        }
        
//        self::$dbc->close();
    }

    /**
     * @brief Comprobar si la base de datos indicada existe
     * @return bool
     */
    private static function checkDatabaseExist() {
        $query = "SELECT COUNT(*) "
                . "FROM information_schema.tables "
                . "WHERE table_schema = '" . self::$dbname . "' "
                . "AND table_name = 'usrData';";

        $resquery = self::$dbc->query($query);

        if ($resquery) {
            $row = $resquery->fetch_row();
        }

        if (!$resquery || $row[0] == 0) {
            return false;
        }

        return true;
    }

    /**
     * @brief Crear la base de datos
     * @param string $dbpassword clave del usuario de sysPass
     * @return none
     *
     * Esta función crea la base de datos y asigna los permisos para el usuario de sysPass.
     * Si se marca el modo hosting, no se establecen los permisos.
     */
    private static function createMySQLDatabase($dbpassword) {
        $query = "CREATE DATABASE IF NOT EXISTS  `" . self::$dbname . "`";

        if (!self::$dbc->query($query)) {
            throw new InstallerException('critical'
            , _('Error al crear la BBDD') . " (" . self::$dbc->error . ")"
            , _('Verifique los permisos del usuario de la Base de Datos'));
        }

        if ( ! self::$isHostingMode ){
            $query = "GRANT ALL PRIVILEGES ON `" . self::$dbname . "`.* TO '" . self::$dbuser . "'@'" . self::$dbhost . "' IDENTIFIED BY '$dbpassword';";

            self::$dbc->query($query);

            if (!self::$dbc->query($query)) {
                throw new InstallerException('critical'
                , _('Error al establecer permisos de la BBDD') . " (" . self::$dbc->error . ")"
                , _('Verifique los permisos del usuario de la Base de Datos'));
            }
        }
    }

    /**
     * @brief Crear el usuario para conectar con la base de datos.
     * @param string $dbpassword clave del usuario de sysPass
     * @return none
     *
     * Esta función crea el usuario para conectar con la base de datos.
     * Si se marca en modo hosting, no se crea el usuario.
     */
    private static function createDBUser($dbpassword) {
        if ( self::$isHostingMode ){
            return;
        }
        
        $query = "CREATE USER '" . self::$dbuser . "'@'localhost' IDENTIFIED BY '" . $dbpassword . "'";

        if (!self::$dbc->query($query)) {
            throw new InstallerException('critical'
            , _('El usuario de MySQL ya existe') . " (" . self::$dbuser . ")"
            , _('Indique un nuevo usuario o elimine el existente'));
        }
    }

    /**
     * @brief Crear la estructura de la base de datos
     * @return none
     *
     * Esta función crea la estructura de la base de datos a partir del archivo dbsctructure.sql.
     */
    private static function createDBStructure() {
        $fileName = dirname(__FILE__) . '/dbstructure.sql';

        if (!file_exists($fileName)) {
            throw new InstallerException('critical'
            , _('El archivo de estructura de la BBDD no existe')
            , _('No es posible crear la BBDD de la aplicación. Descárguela de nuevo.'));
        }

        // Usar la base de datos de sysPass
        if (!self::$dbc->select_db(self::$dbname)) {
            throw new InstallerException('critical'
            , _('Error al seleccionar la BBDD') . " '" . self::$dbname . "' (" . self::$dbc->error . ")"
            , _('No es posible usar la Base de Datos para crear la estructura. Compruebe los permisos y que no exista.'));
        }

        // Leemos el archivo SQL para crear las tablas de la BBDD
        $handle = fopen($fileName, 'rb');

        if ($handle) {
            while (!feof($handle)) {
                $buffer = stream_get_line($handle, 1000000, ";\n");
                if (strlen(trim($buffer)) > 0) {
                    if (!self::$dbc->query($buffer)) {
                        // drop database on error
                        self::$dbc->query("DROP DATABASE " . self::$dbc . ";");
                        throw new InstallerException('critical'
                        , _('Error al crear la BBDD')
                        , _('Error al crear la estructura de la Base de Datos.'));
                    }
                }
            }
        }
    }

    /**
     * @brief Crear el usuario admin de sysPass.
     * @return none
     *
     * Esta función crea el grupo, perfil y usuario 'admin' para utilizar sysPass.
     */
    private static function createAdminAccount() {
        $user = new SP_Users;

        // Datos del grupo
        $user->groupName = "Admins";
        $user->groupDesc = "Admins";

        if (!$user->manageGroup("add")) {
            self::rollback();

            throw new InstallerException("critical"
            , _('Error al crear el grupo "admin"')
            , _('Informe al desarrollador'));
        }

        // Establecer el id de grupo del usuario al recién creado
        $user->userGroupId = $user->queryLastId;

        $profileProp = array("pAccView" => 1
            , "pAccViewPass" => 1
            , "pAccViewHistory" => 1
            , "pAccEdit" => 1
            , "pAccEditPass" => 1
            , "pAccAdd" => 1
            , "pAccDel" => 1
            , "pAccFiles" => 1
            , "pConfigMenu" => 1
            , "pConfig" => 1
            , "pConfigCat" => 1
            , "pConfigMpw" => 1
            , "pConfigBack" => 1
            , "pUsersMenu" => 1
            , "pUsers" => 1
            , "pGroups" => 1
            , "pProfiles" => 1
            , "pEventlog" => 1);


        $user->profileName = 'Admin';

        if (!$user->manageProfiles("add", $profileProp)) {
            self::rollback();

            throw new InstallerException("critical"
            , _('Error al crear el perfil "admin"')
            , _('Informe al desarrollador'));
        }

        // Establecer el id de perfil del usuario al recién creado
        $user->userProfileId = $user->queryLastId;
        
        // Datos del usuario
        $user->userLogin = self::$username;
        $user->userPass = self::$password;
        $user->userName = "Admin";
        $user->userIsAdminApp = 1;


        if (!$user->manageUser('add')) {
            self::rollback();

            throw new InstallerException('critical'
            , _('Error al crear el usuario "admin"')
            , _('Informe al desarrollador'));
        }

        // Guardar el hash de la clave maestra
        SP_Config::$arrConfigValue["masterPwd"] = SP_Crypt::mkHashPassword(self::$masterPassword);
        SP_Config::$arrConfigValue["lastupdatempass"] = time();
        SP_Config::writeConfig(TRUE);

        $user->userId = $user->queryLastId; // Needed for update user's master password

        if (!$user->updateUserMPass(self::$masterPassword, FALSE)) {
            self::rollback();

            throw new InstallerException('critical'
            , _('Error al actualizar la clave maestra del usuario "admin"')
            , _('Informe al desarrollador'));
        }
    }

    /**
     * @brief Deshacer la instalación en caso de fallo
     * @return none
     *
     * Esta función elimina la base de datos y el usuario de sysPass
     */
    private static function rollback() {
        self::$dbc->query("DROP DATABASE IF EXISTS " . self::$dbname . ";");
        self::$dbc->query("DROP USER '" . self::$dbuser . "'@'" . self::$dbhost . "';");
        self::$dbc->query("DROP USER '" . self::$dbuser . "'@'%';");
        SP_Config::deleteKey('dbuser');
        SP_Config::deleteKey('dbpass');
    }

}