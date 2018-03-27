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

use SP\Config\ConfigData;
use SP\Core\Crypt\Hash;
use SP\Core\Dic;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ProfileData;
use SP\DataModel\UserData;
use SP\DataModel\UserGroupData;
use SP\DataModel\UserProfileData;
use SP\Services\Config\ConfigService;
use SP\Services\Service;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;
use SP\Storage\DatabaseConnectionData;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de instalar sysPass.
 */
class Installer extends Service
{
    use Dic\InjectableTrait;

    /**
     * Versión y número de compilación de sysPass
     */
    const VERSION = [3, 0, 0];
    const VERSION_TEXT = '3.0-beta';
    const BUILD = 18032701;

    /**
     * @var ConfigService
     */
    protected $configService;
    /**
     * @var DatabaseSetupInterface
     */
    protected $dbs;
    /**
     * @var InstallData
     */
    private $installData;
    /**
     * @var ConfigData
     */
    private $configData;

    /**
     * @param InstallData $installData
     * @return static
     * @throws InvalidArgumentException
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function run(InstallData $installData)
    {
        $this->installData = $installData;

        $this->checkData();
        $this->install();

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkData()
    {
        if (!$this->installData->getAdminLogin()) {
            throw new InvalidArgumentException(
                __u('Indicar nombre de usuario admin'),
                SPException::ERROR,
                __u('Usuario admin para acceso a la aplicación'));
        }

        if (!$this->installData->getAdminPass()) {
            throw new InvalidArgumentException(
                __u('Indicar la clave de admin'),
                SPException::ERROR,
                __u('Clave del usuario admin de la aplicación'));
        }

        if (!$this->installData->getMasterPassword()) {
            throw new InvalidArgumentException(
                __u('Indicar la clave maestra'),
                SPException::ERROR,
                __u('Clave maestra para encriptar las claves'));
        }

        if (strlen($this->installData->getMasterPassword()) < 11) {
            throw new InvalidArgumentException(
                __u('Clave maestra muy corta'),
                SPException::CRITICAL,
                __u('La longitud de la clave maestra ha de ser mayor de 11 caracteres'));
        }

        if (!$this->installData->getDbAdminUser()) {
            throw new InvalidArgumentException(
                __u('Indicar el usuario de la BBDD'),
                SPException::CRITICAL,
                __u('Usuario con permisos de administrador de la Base de Datos'));
        }

        if (!$this->installData->getDbAdminPass()) {
            throw new InvalidArgumentException(
                __u('Indicar la clave de la BBDD'),
                SPException::ERROR,
                __u('Clave del usuario administrador de la Base de Datos'));
        }

        if (!$this->installData->getDbName()) {
            throw new InvalidArgumentException(
                __u('Indicar el nombre de la BBDD'),
                SPException::ERROR,
                __u('Nombre para la BBDD de la aplicación pej. syspass'));
        }

        if (substr_count($this->installData->getDbName(), '.') >= 1) {
            throw new InvalidArgumentException(
                __u('El nombre de la BBDD no puede contener "."'),
                SPException::CRITICAL,
                __u('Elimine los puntos del nombre de la Base de Datos'));
        }

        if (!$this->installData->getDbHost()) {
            throw new InvalidArgumentException(
                __u('Indicar el servidor de la BBDD'),
                SPException::ERROR,
                __u('Servidor donde se instalará la Base de Datos'));
        }
    }

    /**
     * Iniciar instalación.
     *
     * @throws SPException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    private function install()
    {
        $this->setupDbHost();
        $this->setupConfig();
        $this->setupDb();

        $this->setupDBConnectionData();
        $this->saveMasterPassword();
        $this->createAdminAccount();

        $version = Util::getVersionStringNormalized();

        $this->configService->create(new \SP\DataModel\ConfigData('version', $version));

        $this->configData->setInstalled(true);

        $this->config->saveConfig($this->configData, false);
    }

    /**
     * Setup database connection data
     */
    private function setupDbHost()
    {
        if (preg_match('/^(?:(?P<host>.*):(?P<port>\d{1,5}))|^(?:unix:(?P<socket>.*))/', $this->installData->getDbHost(), $match)) {
            if (!empty($match['socket'])) {
                $this->installData->setDbSocket($match['socket']);
            } else {
                $this->installData->setDbHost($match['host']);
                $this->installData->setDbPort($match['port']);
            }
        } else {
            $this->installData->setDbPort(3306);
        }

        if (strpos('localhost', $this->installData->getDbHost()) === false
            && strpos('127.0.0.1', $this->installData->getDbHost()) === false
        ) {
            $this->installData->setDbAuthHost($_SERVER['SERVER_ADDR']);
            $this->installData->setDbAuthHostDns(gethostbyaddr($_SERVER['SERVER_ADDR']));
        } else {
            $this->installData->setDbAuthHost('localhost');
        }
    }

    /**
     * Setup sysPass config data
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function setupConfig()
    {
        // Generate a random salt that is used to salt the local user passwords
        $this->configData->setPasswordSalt(Util::generateRandomBytes(30));

        // Sets version and remove upgrade key
        $this->configData->setConfigVersion(Util::getVersionStringNormalized());
        $this->configData->setDatabaseVersion(Util::getVersionStringNormalized());
        $this->configData->setUpgradeKey(null);

        // Set DB connection info
        $this->configData->setDbHost($this->installData->getDbHost());
        $this->configData->setDbSocket($this->installData->getDbSocket());
        $this->configData->setDbPort($this->installData->getDbPort());
        $this->configData->setDbName($this->installData->getDbName());

        // Set site config
        $this->configData->setSiteLang($this->installData->getSiteLang());

        $this->config->updateConfig($this->configData);
    }

    /**
     * @param string $type
     * @throws SPException
     */
    private function setupDb($type = 'mysql')
    {
        switch ($type) {
            case 'mysql':
                $this->dbs = new MySQL($this->installData, $this->configData);
                break;
        }

        // Si no es modo hosting se crea un hash para la clave y un usuario con prefijo "sp_" para la DB
        if ($this->installData->isHostingMode()) {
            // Guardar el usuario/clave de conexión a la BD
            $this->configData->setDbUser($this->installData->getDbAdminUser());
            $this->configData->setDbPass($this->installData->getDbAdminPass());
        } else {
            $this->dbs->setupDbUser();
        }

        $this->dbs->createDatabase();
        $this->dbs->createDBStructure();
        $this->dbs->checkConnection();
    }

    /**
     * Setup database connection for sysPass
     */
    private function setupDBConnectionData()
    {
        // FIXME: ugly!!
        $this->dic->get(DatabaseConnectionData::class)
            ->refreshFromConfig($this->configData);
    }

    /**
     * Saves the master password metadata
     *
     * @throws SPException
     */
    private function saveMasterPassword()
    {
        try {
            $this->configService->create(new \SP\DataModel\ConfigData('masterPwd', Hash::hashKey($this->installData->getMasterPassword())));
            $this->configService->create(new \SP\DataModel\ConfigData('lastupdatempass', time()));
        } catch (\Exception $e) {
            processException($e);

            $this->dbs->rollback();

            throw new SPException(
                $e->getMessage(),
                SPException::CRITICAL,
                __u('Informe al desarrollador')
            );
        }
    }

    /**
     * Crear el usuario admin de sysPass.
     * Esta función crea el grupo, perfil y usuario 'admin' para utilizar sysPass.
     *
     * @throws SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function createAdminAccount()
    {
        try {
            $userGroupData = new UserGroupData();
            $userGroupData->setName('Admins');
            $userGroupData->setDescription('sysPass Admins');

            $userProfileData = new UserProfileData();
            $userProfileData->setName('Admin');
            $userProfileData->setProfile(new ProfileData());

            $userService = $this->dic->get(UserService::class);
            $userGroupService = $this->dic->get(UserGroupService::class);
            $userProfileService = $this->dic->get(UserProfileService::class);

            // Datos del usuario
            $userData = new UserData();
            $userData->setUserGroupId($userGroupService->create($userGroupData));
            $userData->setUserProfileId($userProfileService->create($userProfileData));
            $userData->setLogin($this->installData->getAdminLogin());
            $userData->setName('sysPass Admin');
            $userData->setIsAdminApp(1);

            $userService->createWithMasterPass($userData, $this->installData->getAdminPass(), $this->installData->getMasterPassword());

//                    __u('Error al actualizar la clave maestra del usuario "admin"'),
        } catch (\Exception $e) {
            processException($e);

            $this->dbs->rollback();

            throw new SPException(
                $e->getMessage(),
                SPException::CRITICAL,
                __u('Informe al desarrollador')
            );
        }
    }

    /**
     * initialize
     */
    protected function initialize()
    {
        $this->configData = $this->config->getConfigData();
        $this->configService = $this->dic->get(ConfigService::class);
    }
}