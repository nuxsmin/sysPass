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
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\GroupData;
use SP\DataModel\InstallData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserData;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Users\User;
use SP\Mgmt\Users\UserPass;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

define('IS_INSTALLER', 1);

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
     * @return array resultado del proceso
     */
    public function install()
    {
        try {
            $this->checkData();

            $Config = Config::getConfig();

            // Generate a random salt that is used to salt the local user passwords
            $Config->setPasswordSalt(Util::generate_random_bytes(30));
            $Config->setConfigVersion(implode(Util::getVersion(true)));

            if (preg_match('/(.*):(\d{1,5})/', $this->InstallData->getDbHost(), $match)) {
                $this->InstallData->setDbHost($match[1]);
                $this->InstallData->setDbPort($match[2]);
            } else {
                $this->InstallData->setDbPort(3306);
            }

            if (!preg_match('/(localhost|127.0.0.1)/', $this->InstallData->getDbHost())) {
                $this->InstallData->setDbAuthHost($_SERVER['SERVER_ADDR']);
            }

            // Save DB connection info
            $Config->setDbHost($this->InstallData->getDbHost());
            $Config->setDbName($this->InstallData->getDbName());


            $this->connectDatabase();
            $this->setupMySQLDatabase();
            $this->createAdminAccount();

            ConfigDB::setValue('version', implode(Util::getVersion(true)));

            $Config->setInstalled(true);
            Config::saveConfig($Config);
        } catch (SPException $e) {
            return [
                'type' => $e->getType(),
                'description' => $e->getMessage(),
                'hint' => $e->getHint()];
        }

        return true;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkData()
    {
        if (!$this->InstallData->getAdminLogin()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                _('Indicar nombre de usuario admin'),
                _('Usuario admin para acceso a la aplicación'));
        } elseif (!$this->InstallData->getAdminPass()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                _('Indicar la clave de admin'),
                _('Clave del usuario admin de la aplicación'));
        } elseif (!$this->InstallData->getMasterPassword()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                _('Indicar la clave maestra'),
                _('Clave maestra para encriptar las claves'));
        } elseif (strlen($this->InstallData->getMasterPassword()) < 11) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                _('Clave maestra muy corta'),
                _('La longitud de la clave maestra ha de ser mayor de 11 caracteres'));
        } elseif (!$this->InstallData->getDbAdminUser()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                _('Indicar el usuario de la BBDD'),
                _('Usuario con permisos de administrador de la Base de Datos'));
        } elseif (!$this->InstallData->getDbAdminPass()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                _('Indicar la clave de la BBDD'),
                _('Clave del usuario administrador de la Base de Datos'));
        } elseif (!$this->InstallData->getDbName()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                _('Indicar el nombre de la BBDD'),
                _('Nombre para la BBDD de la aplicación pej. syspass'));
        } elseif (substr_count($this->InstallData->getDbName(), '.') >= 1) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                _('El nombre de la BBDD no puede contener "."'),
                _('Elimine los puntos del nombre de la Base de Datos'));
        } elseif (!$this->InstallData->getDbHost()) {
            throw new InvalidArgumentException(
                SPException::SP_CRITICAL,
                _('Indicar el servidor de la BBDD'),
                _('Servidor donde se instalará la Base de Datos'));
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
            $dsn = 'mysql:host=' . $this->InstallData->getDbHost() . ';dbport=' . $this->InstallData->getDbPort() . ';charset=utf8';
            $this->DB = new PDO($dsn, $this->InstallData->getDbAdminUser(), $this->InstallData->getDbAdminPass());
            $this->DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL,
                _('No es posible conectar con la BD'),
                _('Compruebe los datos de conexión') . '<br>' . $e->getMessage());
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
            $this->InstallData->setDbPass(md5(time() . $this->InstallData->getDbPass()));
            $this->InstallData->setDbUser(substr('sp_' . $this->InstallData->getAdminLogin(), 0, 16));

            // Comprobar si el usuario sumistrado existe
            $query = sprintf(/** @lang SQL */
                'SELECT COUNT(*) FROM mysql.user 
                WHERE user=\'%s\' AND host=\'%s\'',
                $this->InstallData->getDbUser(),
                $this->InstallData->getDbAuthHost());

            try {
                // Si no existe el usuario, se intenta crear
                if ((int)$this->DB->query($query)->fetchColumn() === 0
                    // Se comprueba si el nuevo usuario es distinto del creado en otra instalación
                    && $this->InstallData->getDbUser() != Config::getConfig()->getDbUser()
                ) {
                    $this->createDBUser();
                }
            } catch (PDOException $e) {
                throw new SPException(SPException::SP_CRITICAL,
                    sprintf(_('No es posible comprobar el usuario de sysPass') . ' (%s)', $this->InstallData->getAdminLogin()),
                    _('Compruebe los permisos del usuario de conexión a la BD'));
            }
        }

        // Guardar el nuevo usuario/clave de conexión a la BD
        Config::getConfig()->setDbUser($this->InstallData->getDbUser());
        Config::getConfig()->setDbPass($this->InstallData->getDbPass());

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

        $query = sprintf(/** @lang SQL */
            'CREATE USER \'%s\'@\'%s\' IDENTIFIED BY \'%s\'',
            $this->InstallData->getDbUser(),
            $this->InstallData->getDbAuthHost(),
            $this->InstallData->getDbPass());

        try {
            $this->DB->query($query);
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL,
                sprintf(_('El usuario de MySQL ya existe') . ' (%s)', $this->InstallData->getDbUser()),
                _('Indique un nuevo usuario o elimine el existente'));
        }
    }

    /**
     * Crear la base de datos en MySQL.
     *
     * @throws SPException
     */
    private function createMySQLDatabase()
    {
        if ($this->checkDatabaseExist()) {
            throw new SPException(SPException::SP_CRITICAL,
                _('La BBDD ya existe'),
                _('Indique una nueva Base de Datos o elimine la existente'));
        }

        $query = sprintf(/** @lang SQL */
            'CREATE SCHEMA `%s` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci', $this->InstallData->getDbName());

        try {
            $this->DB->query($query);
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL,
                sprintf(_('Error al crear la BBDD') . ' (%s)', $e->getMessage()),
                _('Verifique los permisos del usuario de la Base de Datos'));
        }

        if (!$this->InstallData->isHostingMode()) {
            $query = sprintf(/** @lang SQL */
                'GRANT ALL PRIVILEGES ON `%s`.* TO \'%s\'@\'%s\' IDENTIFIED BY \'%s\';',
                $this->InstallData->getDbName(),
                $this->InstallData->getDbUser(),
                $this->InstallData->getDbAuthHost(),
                $this->InstallData->getDbPass());

            try {
                $this->DB->query($query);
            } catch (PDOException $e) {
                throw new SPException(SPException::SP_CRITICAL,
                    sprintf(_('Error al establecer permisos de la BBDD') . ' (%s)', $e->getMessage()),
                    _('Verifique los permisos del usuario de la Base de Datos'));
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
        $query = sprintf(/** @lang SQL */
            'SELECT COUNT(*) 
            FROM information_schema.schemata
            WHERE schema_name = \'%s\' LIMIT 1', $this->InstallData->getDbName());

        return ((int)$this->DB->query($query)->fetchColumn() > 0);
    }

    /**
     * Crear la estructura de la base de datos.
     * Esta función crea la estructura de la base de datos a partir del archivo dbsctructure.sql.
     *
     * @throws SPException
     */
    private function createDBStructure()
    {
        $fileName = Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'dbstructure.sql';

        if (!file_exists($fileName)) {
            throw new SPException(SPException::SP_CRITICAL,
                _('El archivo de estructura de la BBDD no existe'),
                _('No es posible crear la BBDD de la aplicación. Descárguela de nuevo.'));
        }

        // Usar la base de datos de sysPass
        try {
            $this->DB->query('USE `' . $this->InstallData->getDbName() . '`');
        } catch (PDOException $e) {
            throw new SPException(SPException::SP_CRITICAL,
                sprintf(_('Error al seleccionar la BBDD') . ' \'%s\' (%s)', $this->InstallData->getDbName(), $e->getMessage()),
                _('No es posible usar la Base de Datos para crear la estructura. Compruebe los permisos y que no exista.'));
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
                        // drop database on error
                        $this->DB->query('DROP DATABASE IF EXISTS ' . $this->InstallData->getDbName() . ';');

                        throw new SPException(SPException::SP_CRITICAL,
                            sprintf(_('Error al crear la BBDD') . ' (%s)', $e->getMessage()),
                            _('Error al crear la estructura de la Base de Datos.'));
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
    private function createAdminAccount()
    {
        $GroupData = new GroupData();
        $GroupData->setUsergroupName('Admins');
        $GroupData->setUsergroupDescription('sysPass Admins');

        try {
            Group::getItem($GroupData)->add();
        } catch (SPException $e) {
            $this->rollback();
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al crear el grupo "admin"'),
                _('Informe al desarrollador'));
        }

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
            Profile::getItem($ProfileData)->add();
        } catch (SPException $e) {
            $this->rollback();
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al crear el perfil "admin"'),
                _('Informe al desarrollador'));
        }

        // Datos del usuario
        $UserData = new UserData();
        $UserData->setUserGroupId($GroupData->getUsergroupId());
        $UserData->setUserProfileId($ProfileData->getUserprofileId());
        $UserData->setUserLogin($this->InstallData->getAdminLogin());
        $UserData->setUserPass($this->InstallData->getAdminPass());
        $UserData->setUserName('Admin');
        $UserData->setUserIsAdminApp(true);
        $UserData->setUserIsAdminAcc(false);
        $UserData->setUserIsDisabled(false);

        try {
            User::getItem($UserData)->add();
        } catch (SPException $e) {
            $this->rollback();
            throw new SPException(SPException::SP_CRITICAL,
                _('Error al crear el usuario "admin"'),
                _('Informe al desarrollador'));
        }

        // Guardar el hash de la clave maestra
        ConfigDB::setCacheConfigValue('masterPwd', Crypt::mkHashPassword($this->InstallData->getMasterPassword()));
        ConfigDB::setCacheConfigValue('lastupdatempass', time());
        ConfigDB::writeConfig(true);

        if (!UserPass::getItem($UserData)->updateUserMPass($this->InstallData->getMasterPassword())) {
            $this->rollback();

            throw new SPException(SPException::SP_CRITICAL,
                _('Error al actualizar la clave maestra del usuario "admin"'),
                _('Informe al desarrollador'));
        }
    }

    /**
     * Deshacer la instalación en caso de fallo.
     * Esta función elimina la base de datos y el usuario de sysPass
     */
    private function rollback()
    {
        try {
            $this->DB->query('DROP DATABASE IF EXISTS ' . $this->InstallData->getDbName() . ';');
            $this->DB->query('DROP USER \'' . $this->InstallData->getDbUser() . '\'@\'' . $this->InstallData->getDbAuthHost() . '\';');
            $this->DB->query('DROP USER \'' . $this->InstallData->getDbUser() . '\'@\'%\';');
        } catch (PDOException $e) {
            return false;
        }

        return true;
    }
}