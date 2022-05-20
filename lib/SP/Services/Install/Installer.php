<?php

/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Services\Install;

use Exception;
use SP\Config\Config;
use SP\Config\ConfigData as ConfigSettings;
use SP\Config\ConfigDataInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ConfigData;
use SP\DataModel\ProfileData;
use SP\DataModel\UserData;
use SP\DataModel\UserGroupData;
use SP\DataModel\UserProfileData;
use SP\Http\Request;
use SP\Services\Config\ConfigService;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Storage\Database\MySQLHandler;
use SP\Util\VersionUtil;

defined('APP_ROOT') || die();

/**
 * Installer class
 */
final class Installer
{
    /**
     * sysPass' version and build number
     */
    public const VERSION      = [3, 2, 2];
    public const VERSION_TEXT = '3.2';
    public const BUILD        = 21031301;

    private Request                 $request;
    private Config                  $config;
    private UserService             $userService;
    private UserGroupService        $userGroupService;
    private UserProfileService      $userProfileService;
    private ConfigService           $configService;
    private ?DatabaseSetupInterface $databaseSetup = null;
    private ?InstallData            $installData   = null;

    public function __construct(
        Request $request,
        Config $config,
        UserService $userService,
        UserGroupService $userGroupService,
        UserProfileService $userProfileService,
        ConfigService $configService
    ) {
        $this->request = $request;
        $this->config = $config;
        $this->userService = $userService;
        $this->userGroupService = $userGroupService;
        $this->userProfileService = $userProfileService;
        $this->configService = $configService;
    }

    /**
     * @param  \SP\Services\Install\InstallData  $installData
     * @param $configData
     *
     * @return \SP\Services\Install\DatabaseSetupInterface
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function getDatabaseSetup(InstallData $installData, $configData): DatabaseSetupInterface
    {
        $connectionData = (new DatabaseConnectionData())
            ->setDbHost($installData->getDbHost())
            ->setDbPort($installData->getDbPort())
            ->setDbSocket($installData->getDbSocket())
            ->setDbUser($installData->getDbAdminUser())
            ->setDbPass($installData->getDbAdminPass());

        return new MySQL(new MySQLHandler($connectionData), $installData, $configData);
    }


    /**
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function run(DatabaseSetupInterface $databaseSetup, InstallData $installData): Installer
    {
        $this->databaseSetup = $databaseSetup;
        $this->installData = $installData;

        $this->checkData();
        $this->install();

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkData(): void
    {
        if (empty($this->installData->getAdminLogin())) {
            throw new InvalidArgumentException(
                __u('Please, enter the admin username'),
                SPException::ERROR,
                __u('Admin user to log into the application')
            );
        }

        if (empty($this->installData->getAdminPass())) {
            throw new InvalidArgumentException(
                __u('Please, enter the admin\'s password'),
                SPException::ERROR,
                __u('Application administrator\'s password')
            );
        }

        if (empty($this->installData->getMasterPassword())) {
            throw new InvalidArgumentException(
                __u('Please, enter the Master Password'),
                SPException::ERROR,
                __u('Master password to encrypt the passwords')
            );
        }

        if (strlen($this->installData->getMasterPassword()) < 11) {
            throw new InvalidArgumentException(
                __u('Master password too short'),
                SPException::CRITICAL,
                __u('The Master Password length need to be at least 11 characters')
            );
        }

        if (empty($this->installData->getDbAdminUser())) {
            throw new InvalidArgumentException(
                __u('Please, enter the database user'),
                SPException::CRITICAL,
                __u('An user with database administrative rights')
            );
        }

        if (IS_TESTING
            && empty($this->installData->getDbAdminPass())) {
            throw new InvalidArgumentException(
                __u('Please, enter the database password'),
                SPException::ERROR,
                __u('Database administrator\'s password')
            );
        }

        if (empty($this->installData->getDbName())) {
            throw new InvalidArgumentException(
                __u('Please, enter the database name'),
                SPException::ERROR,
                __u('Application database name. eg. syspass')
            );
        }

        if (substr_count($this->installData->getDbName(), '.') > 0) {
            throw new InvalidArgumentException(
                __u('Database name cannot contain "."'),
                SPException::CRITICAL,
                __u('Please, remove dots in database name')
            );
        }

        if (empty($this->installData->getDbHost())) {
            throw new InvalidArgumentException(
                __u('Please, enter the database server'),
                SPException::ERROR,
                __u('Server where the database will be installed')
            );
        }
    }

    /**
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    private function install(): void
    {
        $this->setupDbHost();

        $configData = $this->setupConfig();

        $this->setupDb($configData);
        $this->saveMasterPassword();
        $this->createAdminAccount();

        $this->configService->create(new ConfigData('version', VersionUtil::getVersionStringNormalized()));

        $configData->setInstalled(true);

        $this->config->saveConfig($configData, false);
    }

    /**
     * Setup database connection data
     */
    private function setupDbHost(): void
    {
        if (preg_match(
            '/^(?P<host>.*):(?P<port>\d{1,5})|^unix:(?P<socket>.*)/',
            $this->installData->getDbHost(),
            $match
        )
        ) {
            if (!empty($match['socket'])) {
                $this->installData->setDbSocket($match['socket']);
            } else {
                $this->installData->setDbHost($match['host']);
                $this->installData->setDbPort($match['port']);
            }
        } else {
            $this->installData->setDbPort(3306);
        }

        if (strpos($this->installData->getDbHost(), 'localhost') === false
            && strpos($this->installData->getDbHost(), '127.0.0.1') === false
        ) {
            // Use real IP address when unitary testing, because no HTTP request is performed
            if (defined('SELF_IP_ADDRESS')) {
                $address = SELF_IP_ADDRESS;
            } else {
                $address = $this->request->getServer('SERVER_ADDR');
            }

            // Check whether sysPass is running on docker. Not so accurate,
            // but avoid some overhead from parsing /proc/self/cgroup
            if (getenv('SYSPASS_DIR') !== false) {
                $this->installData->setDbAuthHost('%');
            } else {
                $this->installData->setDbAuthHost($address);

                $dnsHostname = gethostbyaddr($address);

                if (strlen($dnsHostname) < 60) {
                    $this->installData->setDbAuthHostDns($dnsHostname);
                }
            }
        } else {
            $this->installData->setDbAuthHost('localhost');
        }
    }

    /**
     * Setup sysPass config data
     */
    private function setupConfig(): ConfigDataInterface
    {
        $configData = new ConfigSettings();
        $configData->setConfigVersion(VersionUtil::getVersionStringNormalized())
            ->setDatabaseVersion(VersionUtil::getVersionStringNormalized())
            ->setUpgradeKey(null)
            ->setDbHost($this->installData->getDbHost())
            ->setDbSocket($this->installData->getDbSocket())
            ->setDbPort($this->installData->getDbPort())
            ->setDbName($this->installData->getDbName())
            ->setSiteLang($this->installData->getSiteLang());

        $this->config->updateConfig($configData);

        return $configData;
    }

    /**
     * @param  ConfigDataInterface  $configData
     */
    private function setupDb(ConfigDataInterface $configData): void
    {
        if ($this->installData->isHostingMode()) {
            // Save DB connection user and pass
            $configData->setDbUser($this->installData->getDbAdminUser());
            $configData->setDbPass($this->installData->getDbAdminPass());
        } else {
            [$user, $pass] = $this->databaseSetup->setupDbUser();

            $configData->setDbUser($user);
            $configData->setDbPass($pass);
        }

        $this->config->updateConfig($configData);

        $this->databaseSetup->connectDatabase();
        $this->databaseSetup->createDatabase();
        $this->databaseSetup->createDBStructure();
        $this->databaseSetup->checkConnection();
    }

    /**
     * Saves the master password metadata
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private function saveMasterPassword(): void
    {
        try {
            $this->configService->create(
                new ConfigData(
                    'masterPwd',
                    Hash::hashKey($this->installData->getMasterPassword())
                )
            );
            $this->configService->create(
                new ConfigData(
                    'lastupdatempass',
                    time()
                )
            );
        } catch (Exception $e) {
            processException($e);

            $this->databaseSetup->rollback();

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
     * @throws SPException
     */
    private function createAdminAccount(): void
    {
        try {
            $userGroupData = new UserGroupData();
            $userGroupData->setName('Admins');
            $userGroupData->setDescription('sysPass Admins');

            $userProfileData = new UserProfileData();
            $userProfileData->setName('Admin');
            $userProfileData->setProfile(new ProfileData());

            $userData = new UserData();
            $userData->setUserGroupId($this->userGroupService->create($userGroupData));
            $userData->setUserProfileId($this->userProfileService->create($userProfileData));
            $userData->setLogin($this->installData->getAdminLogin());
            $userData->setName('sysPass Admin');
            $userData->setIsAdminApp(1);

            $id = $this->userService->createWithMasterPass(
                $userData,
                $this->installData->getAdminPass(),
                $this->installData->getMasterPassword()
            );

            if ($id === 0) {
                throw new SPException(__u('Error while creating \'admin\' user'));
            }
        } catch (Exception $e) {
            processException($e);

            $this->databaseSetup->rollback();

            throw new SPException(
                $e->getMessage(),
                SPException::CRITICAL,
                __u('Warn to developer'),
                $e->getCode(),
                $e
            );
        }
    }
}