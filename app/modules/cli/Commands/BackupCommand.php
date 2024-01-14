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
use RuntimeException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Export\Ports\FileBackupServiceInterface;
use SP\Domain\Export\Services\FileBackupService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class BackupCommand
 *
 * @package SP\Modules\Cli\Commands
 */
final class BackupCommand extends CommandBase
{
    /**
     * @var string[]
     */
    public static array $envVarsMapping = [
        'path' => 'BACKUP_PATH'
    ];
    /**
     * @var string
     */
    protected static $defaultName = 'sp:backup';
    private FileBackupService $fileBackupService;

    public function __construct(
        FileBackupServiceInterface $fileBackupService,
        LoggerInterface   $logger,
        ConfigFileService $config
    )
    {
        $this->fileBackupService = $fileBackupService;

        parent::__construct($logger, $config);
    }

    protected function configure(): void
    {
        $this->setDescription(__('Backup actions'))
            ->setHelp(__('This command performs a file based backup from sysPass database and application'))
            ->addOption('path',
                null,
                InputOption::VALUE_OPTIONAL,
                __('Path where to store the backup files'),
                BACKUP_PATH);
    }

    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        $style = new SymfonyStyle($input, $output);

        try {
            $this->checkInstalled();

            $path = $this->getPath($input, $style);

            $this->logger->info(sprintf(__u('Backup path set to: %s'), $path));

            $this->fileBackupService->doBackup($path);

            $this->logger->info(__u('Application and database backup completed successfully'));

            $style->success(__('Application and database backup completed successfully'));

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->logger->error($e->getTraceAsString());
            $this->logger->error($e->getMessage());

            $style->error(__($e->getMessage()));
        }

        return self::FAILURE;
    }

    private function checkInstalled(): void
    {
        if (!defined('TEST_ROOT')
            && !$this->configData->isInstalled()) {
            throw new RuntimeException(__u('sysPass is not installed'));
        }
    }

    private function getPath(
        InputInterface $input,
        StyleInterface $style
    ): string
    {
        $path = self::getEnvVarOrOption('path', $input);

        if (empty($path)) {
            $this->logger->debug(__u('Ask for path'));

            return $style->ask(__('Please enter the path where to store the backup files'), BACKUP_PATH);
        }

        return $path;
    }
}
