<?php

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Config\ConfigData;
use SP\Core\Crypt\Hash;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ProfileData;
use SP\DataModel\UserData;
use SP\DataModel\UserGroupData;
use SP\DataModel\UserProfileData;
use SP\Http\Request;
use SP\Services\Config\ConfigService;
use SP\Services\Service;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;
use SP\Storage\Database\Database;
use SP\Storage\Database\DBStorageInterface;
use SP\Util\VersionUtil;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de instalar sysPass.
 */
final class Installer extends Service
{
    /**
     * sysPass' version and build number
     */
    const VERSION = [3, 1, 0];
    const VERSION_TEXT = '3.1-RC5';
    const BUILD = 19072001;

    /**
     * @var DatabaseSetupInterface
     */
    private $dbs;
    /**
     * @var Request
     */
    private $request;
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
     *
     * @return static
     * @throws InvalidArgumentException
     * @throws SPException
     * @throws EnvironmentIsBrokenException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
        if (empty($this->installData->getAdminLogin())) {
            throw new InvalidArgumentException(
                __u('Please, enter the admin username'),
                SPException::ERROR,
                __u('Admin user to log into the application'));
        }

        if (empty($this->installData->getAdminPass())) {
            throw new InvalidArgumentException(
                __u('Please, enter the admin\'s password'),
                SPException::ERROR,
                __u('Application administrator\'s password'));
        }

        if (empty($this->installData->getMasterPassword())) {
            throw new InvalidArgumentException(
                __u('Please, enter the Master Password'),
                SPException::ERROR,
                __u('Master password to encrypt the passwords'));
        }

        if (strlen($this->installData->getMasterPassword()) < 11) {
            throw new InvalidArgumentException(
                __u('Master password too short'),
                SPException::CRITICAL,
                __u('The Master Password length need to be at least 11 characters'));
        }

        if (empty($this->installData->getDbAdminUser())) {
            throw new InvalidArgumentException(
                __u('Please, enter the database user'),
                SPException::CRITICAL,
                __u('An user with database administrative rights'));
        }

        if (empty($this->installData->getDbAdminPass()) && APP_MODULE !== 'tests') {
            throw new InvalidArgumentException(
                __u('Please, enter the database password'),
                SPException::ERROR,
                __u('Database administrator\'s password'));
        }

        if (empty($this->installData->getDbName())) {
            throw new InvalidArgumentException(
                __u('Please, enter the database name'),
                SPException::ERROR,
                __u('Application database name. eg. syspass'));
        }

        if (substr_count($this->installData->getDbName(), '.') >= 1) {
            throw new InvalidArgumentException(
                __u('Database name cannot contain "."'),
                SPException::CRITICAL,
                __u('Please, remove dots in database name'));
        }

        if (empty($this->installData->getDbHost())) {
            throw new InvalidArgumentException(
                __u('Please, enter the database server'),
                SPException::ERROR,
                __u('Server where the database will be installed'));
        }
    }

    /**
     * Iniciar instalación.
     *
     * @throws SPException
     * @throws EnvironmentIsBrokenException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConstraintException
     * @throws QueryException
     */
    private function install()
    {
        $this->setupDbHost();
        $this->setupConfig();
        $this->setupDb();

        $this->updateConnectionData();
        $this->saveMasterPassword();
        $this->createAdminAccount();

        $version = VersionUtil::getVersionStringNormalized();

        $this->dic->get(ConfigService::class)
            ->create(new \SP\DataModel\ConfigData('version', $version));

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
            if (APP_MODULE === 'tests') {
                $address = SELF_IP_ADDRESS;
            } else {
                $address = $this->request->getServer('SERVER_ADDR');
            }

            $this->installData->setDbAuthHost($address);
            $this->installData->setDbAuthHostDns(gethostbyaddr($address));
        } else {
            $this->installData->setDbAuthHost('localhost');
        }
    }

    /**
     * Setup sysPass config data
     */
    private function setupConfig()
    {
        // Sets version and remove upgrade key
        $this->configData->setConfigVersion(VersionUtil::getVersionStringNormalized());
        $this->configData->setDatabaseVersion(VersionUtil::getVersionStringNormalized());
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
     *
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
     * Setup database connection for sysPass.
     *
     * Updates the database storage interface in the dependency container
     */
    private function updateConnectionData()
    {
        $this->dic->set(DBStorageInterface::class, $this->dbs->createDbHandlerFromInstaller());
        $this->dic->set(
            Database::class,
            new Database(
                $this->dic->get(DBStorageInterface::class),
                $this->dic->get(EventDispatcher::class)
            )
        );
    }

    /**
     * Saves the master password metadata
     *
     * @throws SPException
     */
    private function saveMasterPassword()
    {
        try {
            // This service needs to be called after a successful database setup, since
            // DI container stores the definition on its first call, so it would contain
            // an incomplete database setup
            $configService = $this->dic->get(ConfigService::class);

            $configService->create(new \SP\DataModel\ConfigData('masterPwd', Hash::hashKey($this->installData->getMasterPassword())));
            $configService->create(new \SP\DataModel\ConfigData('lastupdatempass', time()));
        } catch (Exception $e) {
            processException($e);

            $this->dbs->rollback();

            throw new SPException(
                $e->getMessage(),
                SPException::CRITICAL,
                __u('Warn to developer'),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Crear el usuario admin de sysPass.
     * Esta función crea el grupo, perfil y usuario 'admin' para utilizar sysPass.
     *
     * @throws SPException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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

            $id = $userService->createWithMasterPass($userData, $this->installData->getAdminPass(), $this->installData->getMasterPassword());

            if ($id === 0) {
                throw new SPException(__u('Error while creating \'admin\' user'));
            }
        } catch (Exception $e) {
            processException($e);

            $this->dbs->rollback();

            throw new SPException(
                $e->getMessage(),
                SPException::CRITICAL,
                __u('Warn to developer'),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * initialize
     */
    protected function initialize()
    {
        $this->configData = $this->config->getConfigData();
        $this->request = $this->dic->get(Request::class);
    }
}