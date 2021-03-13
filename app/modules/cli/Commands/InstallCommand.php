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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Cli\Commands;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SP\Config\Config;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Language;
use SP\Services\Install\InstallData;
use SP\Services\Install\Installer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class InstallCommand
 *
 * @package SP\Modules\Cli\Commands
 */
final class InstallCommand extends CommandBase
{
    protected static $defaultName = 'sp:install';
    /**
     * @var Installer
     */
    private $installer;

    public function __construct(LoggerInterface $logger,
                                SymfonyStyle $io,
                                Config $config,
                                Installer $installer)
    {
        parent::__construct($logger, $io, $config);

        $this->installer = $installer;
    }

    protected function configure()
    {
        $this->setDescription(__('Install sysPass.'))
            ->setHelp(__('This command installs sysPass.'))
            ->addArgument('adminLogin',
                InputArgument::REQUIRED,
                __('Admin user to log into the application'))
            ->addArgument('databaseHost',
                InputArgument::REQUIRED,
                __('Server name to install sysPass database'))
            ->addArgument('databaseName',
                InputArgument::REQUIRED,
                __('Application database name. eg. syspass'))
            ->addArgument('databaseUser',
                InputArgument::REQUIRED,
                __('An user with database administrative rights'))
            ->addOption('databasePassword',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Database administrator\'s password'))
            ->addOption('adminPassword',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Application administrator\'s password'))
            ->addOption('masterPassword',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Master password to encrypt the passwords'))
            ->addOption('hostingMode',
                null,
                InputOption::VALUE_OPTIONAL,
                __('It does not create or verify the user\'s permissions on the DB'),
                false)
            ->addOption('language',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Sets the global app language. You can set a per user language on preferences.'))
            ->addOption('force',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Force sysPass installation.'),
                false);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = (bool)$input->getOption('force');

        if ($this->configData->isInstalled() && $force === false) {
            $this->logger->warning(__u('sysPass is already installed'));

            $this->io->warning(__('sysPass is already installed. Use \'--force\' to install it again.'));

            return self::FAILURE;
        }

        $adminPassword = $input->getOption('adminPassword');

        $passNonEmptyValidator = function ($value) {
            if (empty($value)) {
                throw new RuntimeException(__('Password cannot be blank'));
            }

            return $value;
        };

        if (empty($adminPassword)) {
            $this->logger->debug(__u('Ask for admin password'));

            $adminPassword = $this->io->askHidden(__('Please provide sysPass admin\'s password'), $passNonEmptyValidator);
            $adminPasswordRepeat = $this->io->askHidden(__('Please provide sysPass admin\'s password again'), $passNonEmptyValidator);

            if ($adminPassword !== $adminPasswordRepeat) {
                $this->io->warning(__('Passwords do not match'));

                return self::FAILURE;
            }
        }

        $masterPassword = $input->getOption('masterPassword');

        if (empty($masterPassword)) {
            $this->logger->debug(__u('Ask for master password'));

            $masterPassword = $this->io->askHidden(__('Please provide sysPass master password'), $passNonEmptyValidator);
            $masterPasswordRepeat = $this->io->askHidden(__('Please provide sysPass master password again'), $passNonEmptyValidator);

            if ($masterPassword !== $masterPasswordRepeat) {
                $this->io->warning(__('Passwords do not match'));

                return self::FAILURE;
            }
        }

        $databasePassword = $input->getOption('databasePassword');

        if (empty($databasePassword)) {
            $this->logger->debug(__u('Ask for database password'));

            $databasePassword = $this->io->askHidden(__('Please provide database admin password'));
        }

        $language = $input->getOption('language');

        if (empty($language)) {
            $this->logger->debug(__u('Ask for language'));

            $language = $this->io->choice(__('Language'), array_keys(Language::getAvailableLanguages()), 'en_US');
        }

        $install = $this->io->confirm(__('Install sysPass?'), false);

        if (!$install) {
            $this->logger->debug(__u('Installation aborted'));

            return self::SUCCESS;
        }

        $installData = new InstallData();
        $installData->setSiteLang($language);
        $installData->setAdminLogin($input->getArgument('adminLogin'));
        $installData->setAdminPass($adminPassword);
        $installData->setMasterPassword($masterPassword);
        $installData->setDbAdminUser($input->getArgument('databaseUser'));
        $installData->setDbAdminPass($databasePassword);
        $installData->setDbName($input->getArgument('databaseName'));
        $installData->setDbHost($input->getArgument('databaseHost'));
        $installData->setHostingMode((bool)$input->getOption('hostingMode'));

        try {
            $this->installer->run($installData);

            $this->io->success(__('Installation finished'));

            $this->logger->info(__u('Installation finished'));
            return self::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $this->io->error(__($e->getMessage()));
        } catch (Exception $e) {
            $this->logger->error($e->getTraceAsString());
            $this->logger->error($e->getMessage());
        }

        return self::FAILURE;
    }
}