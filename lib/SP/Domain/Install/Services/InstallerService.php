<?php

/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Install\Services;

use Exception;
use SP\Core\Crypt\Hash;
use SP\DataModel\ProfileData;
use SP\Domain\Config\Models\Config;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Install\Adapters\InstallData;
use SP\Domain\Install\Ports\InstallerServiceInterface;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Models\UserProfile;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\Ports\UserService;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\File\FileException;
use SP\Util\VersionUtil;

use function SP\__u;
use function SP\processException;

/**
 * Installer class
 */
final class InstallerService implements InstallerServiceInterface
{
    /**
     * sysPass' version and build number
     */
    public const VERSION      = [4, 0, 0];
    public const VERSION_TEXT = '4.0';
    public const BUILD        = 21031301;

    private RequestInterface $request;
    private ?InstallData     $installData = null;

    public function __construct(
        RequestInterface                        $request,
        private readonly ConfigFileService      $config,
        private readonly UserService $userService,
        private readonly UserGroupService       $userGroupService,
        private readonly UserProfileService     $userProfileService,
        private readonly ConfigService          $configService,
        private readonly DatabaseConnectionData $databaseConnectionData,
        private readonly DatabaseSetupInterface $databaseSetup
    ) {
        $this->request = $request;
    }

    /**
     * @throws InvalidArgumentException
     * @throws SPException
     */
    public function run(InstallData $installData): InstallerServiceInterface
    {
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
            && empty($this->installData->getDbAdminPass())
        ) {
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

        $this->configService->create(
            new Config([
                           'parameter' => 'version',
                           'value' => VersionUtil::getVersionStringNormalized()
                       ])
        );

        $configData->setInstalled(true);

        $this->config->save($configData, false);
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

        if (!str_contains($this->installData->getDbHost(), 'localhost')
            && !str_contains($this->installData->getDbHost(), '127.0.0.1')
        ) {
            // Use real IP address when unitary testing, because no HTTP request is performed
            // FIXME
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
     * @throws FileException
     */
    private function setupConfig(): ConfigDataInterface
    {
        $configData = $this->config->getConfigData()
                                   ->setConfigVersion(VersionUtil::getVersionStringNormalized())
                                   ->setDatabaseVersion(VersionUtil::getVersionStringNormalized())
                                   ->setAppVersion(VersionUtil::getVersionStringNormalized())
                                   ->setUpgradeKey(null)
                                   ->setDbHost($this->installData->getDbHost())
                                   ->setDbSocket($this->installData->getDbSocket())
                                   ->setDbPort($this->installData->getDbPort())
                                   ->setDbName($this->installData->getDbName())
                                   ->setSiteLang($this->installData->getSiteLang());

        $this->config->save($configData, false, false);

        return $configData;
    }

    /**
     * @param ConfigDataInterface $configData
     * @throws FileException
     */
    private function setupDb(ConfigDataInterface $configData): void
    {
        $user = null;

        $this->databaseSetup->connectDatabase();

        if ($this->installData->isHostingMode()) {
            // Save DB connection user and pass
            $configData->setDbUser($this->installData->getDbAdminUser());
            $configData->setDbPass($this->installData->getDbAdminPass());
        } else {
            [$user, $pass] = $this->databaseSetup->setupDbUser();

            $configData->setDbUser($user);
            $configData->setDbPass($pass);
        }

        $this->config->save($configData, false, false);

        $this->databaseSetup->createDatabase($user);
        $this->databaseSetup->createDBStructure();
        $this->databaseSetup->checkConnection();

        $this->databaseConnectionData->refreshFromConfig($configData);
    }

    /**
     * Saves the master password metadata
     *
     * @throws SPException
     */
    private function saveMasterPassword(): void
    {
        try {
            $this->configService->create(
                new Config(
                    [
                        'parameter' => 'masterPwd',
                        'value' => Hash::hashKey($this->installData->getMasterPassword())
                    ]
                )
            );
            $this->configService->create(
                new Config(['parameter' => 'lastupdatempass', 'value' => time()])
            );
        } catch (Exception $e) {
            processException($e);

            $this->databaseSetup->rollback($this->config->getConfigData()->getDbUser());

            throw new SPException($e->getMessage(), SPException::CRITICAL, __u('Warn to developer'), $e->getCode(), $e);
        }
    }

    /**
     * @throws SPException
     */
    private function createAdminAccount(): void
    {
        try {
            $userGroup = new UserGroup(
                [
                    'name' => 'Admins',
                    'description' => 'sysPass Admins'
                ]
            );

            $userProfile = new UserProfile(['name' => 'Admin', 'profile' => new ProfileData()]);

            $userData = new User([
                                     'userGroupId' => $this->userGroupService->create($userGroup),
                                     'userProfileId' => $this->userProfileService->create($userProfile),
                                     'login' => $this->installData->getAdminLogin(),
                                     'name' => 'sysPass Admin',
                                     'isAdminApp' => 1,
                                 ]);

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

            $this->databaseSetup->rollback($this->config->getConfigData()->getDbUser());

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
