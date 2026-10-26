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

namespace SP\Modules\Cli\Commands;

use Exception;
use Psr\Log\LoggerInterface;
use SP\Core\Language;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Exceptions\InstallError;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Install\Adapters\InstallData;
use SP\Domain\Install\Ports\InstallerService;
use SP\Domain\Install\Services\Installer;
use SP\Util\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class InstallCommand
 *
 * @package SP\Modules\Cli\Commands
 */
final class InstallCommand extends CommandBase
{
    /**
     * @var string[]
     */
    public static array $envVarsMapping = [
        'adminLogin'       => 'ADMIN_LOGIN',
        'adminPassword'    => 'ADMIN_PASSWORD',
        'databaseHost'     => 'DATABASE_HOST',
        'databaseName'     => 'DATABASE_NAME',
        'databaseUser'     => 'DATABASE_USER',
        'databasePassword' => 'DATABASE_PASSWORD',
        'masterPassword'   => 'MASTER_PASSWORD',
        'hostingMode'      => 'HOSTING_MODE',
        'language'         => 'LANGUAGE',
        'forceInstall'     => 'FORCE_INSTALL',
        'install'          => 'INSTALL',
    ];
    /**
     * @var string
     */
    protected static  $defaultName = 'sp:install';
    private Installer $installer;

    public function __construct(
        LoggerInterface   $logger,
        ConfigFileService $config,
        InstallerService $installer
    ) {
        parent::__construct($logger, $config);

        $this->installer = $installer;
    }

    protected function configure(): void
    {
        $this->setDescription(__('Install sysPass'))
            ->setHelp(__('This command installs sysPass'))
            ->addArgument(
                'adminLogin',
                InputArgument::OPTIONAL,
                __('Admin user to log into the application')
            )
            ->addArgument(
                'databaseHost',
                InputArgument::OPTIONAL,
                __('Server name to install sysPass database')
            )
            ->addArgument(
                'databaseName',
                InputArgument::OPTIONAL,
                __('Application database name. eg. syspass')
            )
            ->addArgument(
                'databaseUser',
                InputArgument::OPTIONAL,
                __('An user with database administrative rights')
            )
            ->addOption(
                'databasePassword',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Database administrator\'s password')
            )
            ->addOption(
                'adminPassword',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Application administrator\'s password')
            )
            ->addOption(
                'masterPassword',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Master password to encrypt the data')
            )
            ->addOption(
                'hostingMode',
                null,
                InputOption::VALUE_NONE,
                __('It does not create or verify the user\'s permissions on the DB')
            )
            ->addOption(
                'language',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Sets the global app language. You can set a per user language on preferences')
            )
            ->addOption(
                'forceInstall',
                null,
                InputOption::VALUE_NONE,
                __('Force sysPass installation')
            )
            ->addOption(
                'install',
                null,
                InputOption::VALUE_NONE,
                __('Skip asking to confirm the installation')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        try {
            $installData = $this->getInstallData($input, $style);

            $forceInstall = $this->getForceInstall($input);

            if (!$forceInstall || !$this->getInstall($input, $style)) {
                $this->logger->debug(__u('Installation aborted'));
                $style->info(__('Installation aborted'));

                return self::FAILURE;
            }

            $this->installer->run($installData);

            $this->logger->info(__('Installation finished'));

            $style->success(__('Installation finished'));

            return self::SUCCESS;
        } catch (InstallError $e) {
            $this->logger->error($e->getMessage());

            $style->error(__($e->getMessage()));
        } catch (InvalidArgumentException $e) {
            $this->logger->warning($e->getMessage());

            $style->warning(__($e->getMessage()));
        } catch (Exception $e) {
            $this->logger->error($e->getTraceAsString());
            $this->logger->error($e->getMessage());

            $style->error(__($e->getMessage()));
        }

        return self::FAILURE;
    }

    /**
     * @throws InstallError
     */
    private function getInstallData(
        InputInterface $input,
        StyleInterface $style
    ): InstallData {
        $adminPassword = $this->getAdminPassword($input, $style);
        $masterPassword = $this->getMasterPassword($input, $style);
        $databasePassword = $this->getDatabasePassword($input, $style);
        $language = $this->getLanguage($input, $style);
        $hostingMode = $this->isHostingMode($input);
        $adminLogin = self::getEnvVarOrArgument('adminLogin', $input);
        $databaseUser = self::getEnvVarOrArgument('databaseUser', $input);
        $databaseName = self::getEnvVarOrArgument('databaseName', $input);
        $databaseHost = self::getEnvVarOrArgument('databaseHost', $input);

        $installData = new InstallData();
        $installData->setSiteLang($language);
        $installData->setAdminLogin($adminLogin);
        $installData->setAdminPass($adminPassword);
        $installData->setMasterPassword($masterPassword);
        $installData->setDbAdminUser($databaseUser);
        $installData->setDbAdminPass($databasePassword);
        $installData->setDbName($databaseName);
        $installData->setDbHost($databaseHost);
        $installData->setHostingMode($hostingMode);

        return $installData;
    }

    /**
     * @return array|false|mixed|string
     * @throws InstallError
     */
    private function getAdminPassword(
        InputInterface $input,
        StyleInterface $style
    ) {
        $option = 'adminPassword';

        $password =
            self::getEnvVarForOption($option)
                ?: $input->getOption($option);

        if (empty($password)) {
            $this->logger->debug(__u('Ask for admin password'));

            $password = $style->askHidden(
                __('Please provide sysPass admin\'s password'),
                fn($value) => Validators::valueNotEmpty(
                    $value,
                    sprintf(__u('%s cannot be blank'), 'Admin password')
                )
            );

            $passwordRepeat = $style->askHidden(
                __('Please provide sysPass admin\'s password again'),
                fn($value) => Validators::valueNotEmpty(
                    $value,
                    sprintf(__u('%s cannot be blank'), 'Admin password')
                )
            );

            if ($password !== $passwordRepeat) {
                throw new InstallError(__u('Passwords do not match'));
            }

            if (null === $password || null === $passwordRepeat) {
                throw new InstallError(sprintf(__u('%s cannot be blank'), 'Admin password'));
            }
        }

        return $password;
    }


    /**
     * @return array|false|mixed|string
     * @throws InstallError
     */
    private function getMasterPassword(
        InputInterface $input,
        StyleInterface $style
    ) {
        $password = self::getEnvVarOrOption('masterPassword', $input);

        if (empty($password)) {
            $this->logger->debug(__u('Ask for master password'));

            $password = $style->askHidden(
                __('Please provide sysPass master password'),
                fn($value) => Validators::valueNotEmpty(
                    $value,
                    sprintf(__u('%s cannot be blank'), 'Master password')
                )
            );
            $passwordRepeat = $style->askHidden(
                __('Please provide sysPass master password again'),
                fn($value) => Validators::valueNotEmpty(
                    $value,
                    sprintf(__u('%s cannot be blank'), 'Master password')
                )
            );

            if ($password !== $passwordRepeat) {
                throw new InstallError(__u('Passwords do not match'));
            }

            if (null === $password || null === $passwordRepeat) {
                throw new InstallError(sprintf(__u('%s cannot be blank'), 'Master password'));
            }
        }

        return $password;
    }

    /**
     * @return array|false|mixed|string
     */
    private function getDatabasePassword(
        InputInterface $input,
        StyleInterface $style
    ) {
        $password = self::getEnvVarOrOption('databasePassword', $input);

        if (empty($password)) {
            $this->logger->debug(__u('Ask for database password'));

            $password = $style->askHidden(__('Please provide database admin password'));
        }

        return $password;
    }

    /**
     * @return array|false|mixed|string
     */
    private function getLanguage(
        InputInterface $input,
        StyleInterface $style
    ) {
        $language = self::getEnvVarOrOption('language', $input);

        if (empty($language)) {
            $this->logger->debug(__u('Ask for language'));

            $language = $style->choice(
                __('Language'),
                array_keys(Language::getAvailableLanguages()),
                'en_US'
            );
        }

        return $language;
    }

    private function isHostingMode(InputInterface $input): bool
    {
        $option = 'hostingMode';

        $envHostingMode = self::getEnvVarForOption($option);

        return $envHostingMode !== false
            ? Util::boolval($envHostingMode)
            : $input->getOption($option);
    }

    /**
     * @throws InstallError
     */
    private function getForceInstall(InputInterface $input): bool
    {
        $option = 'forceInstall';

        $envForceInstall = self::getEnvVarForOption($option);

        $force = $envForceInstall !== false
            ? Util::boolval($envForceInstall)
            : $input->getOption($option);

        if ($force === false && $this->configData->isInstalled()) {
            throw new InstallError(__u('sysPass is already installed. Use \'--forceInstall\' to install it again.'));
        }

        return $force;
    }

    private function getInstall(
        InputInterface $input,
        StyleInterface $style
    ): bool {
        $option = 'install';

        $envInstall = self::getEnvVarForOption($option);

        $install = $envInstall !== false
            ? Util::boolval($envInstall)
            : $input->getOption($option);

        if ($install === false) {
            return $style->confirm(__('Install sysPass?'), false);
        }

        return true;
    }
}
