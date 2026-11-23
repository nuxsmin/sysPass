<?php

declare(strict_types=1);

/**
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

namespace SP\Domain\Export\Services;

use Exception;
use PDO;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Dtos\DatabaseTables;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Domain\Database\Ports\QueryFactoryInterface;
use SP\Domain\Export\Dtos\BackupFiles;
use SP\Domain\Export\Ports\BackupFileService;
use SP\Domain\File\Ports\ArchiveHandlerInterface;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Core\Exceptions\FileException;

use function SP\__u;

/**
 * Class BackupFile
 */
final class BackupFile extends Service implements BackupFileService
{
    public const         BACKUP_INCLUDE_REGEX = /** @lang RegExp */
        '#^(?:[A-Z]:)?(?:/(?!(\.git|backup|cache|temp|vendor|tests))[^/]+)+/[^/]+\.\w+$#Di';

    public function __construct(
        ConfigFileService $config,
        EventDispatcherInterface $eventDispatcher,
        Context $context,
        private readonly DatabaseInterface       $database,
        private readonly DatabaseUtilInterface   $databaseUtil,
        private readonly FileHandlerInterface    $dbBackupFile,
        private readonly ArchiveHandlerInterface $dbArchiveHandler,
        private readonly ArchiveHandlerInterface $appArchiveHandler,
        private readonly BackupFiles             $backupFiles,
        private readonly QueryFactoryInterface   $queryFactory
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    /**
     * Realizar backup de la BBDD y aplicación.
     *
     * @throws ServiceException
     */
    public function doBackup(string $backupPath, string $applicationPath): void
    {
        set_time_limit(0);

        $backupPath = $this->resolveBackupPath($backupPath);

        try {
            $this->deleteOldBackups($backupPath);

            $this->eventDispatcher->notify(
                'run.backup.start',
                new Event($this, EventMessage::build()->addDescription(__u('Make Backup')))
            );

            $configData = $this->config->getConfigData();

            $this->backupTables($configData->getDbName());
            $this->backupApp($applicationPath);

            $this->config->save($configData->setBackupHash($this->backupFiles->getHash()));
        } catch (Exception $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            throw ServiceException::error(
                __u('Error while doing the backup'),
                __u('Please check out the event log for more details'),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Canonicalize the backup root so every later operation uses the resolved
     * directory instead of a possibly relative or symlinked path.
     *
     * @throws ServiceException
     */
    private function resolveBackupPath(string $backupPath): string
    {
        if ($backupPath === '') {
            throw ServiceException::error('Unable to create the backups directory');
        }

        if (!is_dir($backupPath) && !mkdir($backupPath, 0755, true) && !is_dir($backupPath)) {
            throw ServiceException::error('Unable to create the backups directory');
        }

        $canonicalPath = realpath($backupPath);

        if ($canonicalPath === false || !is_dir($canonicalPath)) {
            throw ServiceException::error('Unable to create the backups directory');
        }

        return rtrim($canonicalPath, DIRECTORY_SEPARATOR) ?: DIRECTORY_SEPARATOR;
    }

    /**
     * Eliminar las copias de seguridad anteriores
     */
    private function deleteOldBackups(string $backupPath): void
    {
        $currentFiles = [
            basename((string)$this->backupFiles->getAppBackupFile()),
            basename((string)$this->backupFiles->getDbBackupFile()),
            basename((string)$this->backupFiles->getAppBackupFileCompressed()),
            basename((string)$this->backupFiles->getDbBackupFileCompressed()),
        ];

        $patterns = [
            AppInfoInterface::APP_NAME . '_db-*',
            AppInfoInterface::APP_NAME . '_app-*',
            AppInfoInterface::APP_NAME . '*.sql',
        ];

        foreach ($patterns as $pattern) {
            foreach (glob(implode(DIRECTORY_SEPARATOR, [$backupPath, $pattern])) ?: [] as $file) {
                if (!$this->isContainedIn($file, $backupPath)) {
                    continue;
                }

                if (!in_array(basename($file), $currentFiles, true)) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Check that a path resolves to a location under the canonical backup root.
     */
    private function isContainedIn(string $path, string $backupPath): bool
    {
        $canonicalPath = realpath($path);

        if ($canonicalPath === false) {
            return false;
        }

        return str_starts_with(
            $canonicalPath,
            rtrim($backupPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        );
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function backupTables(string $dbName): void
    {
        $this->eventDispatcher->notify(
            'run.backup.process',
            new Event(
                $this,
                EventMessage::build()->addDescription(__u('Copying database'))
            )
        );

        $sqlOut = [
            '-- ',
            sprintf('-- sysPass DB dump generated on %s (START)', time()),
            '-- ',
            '-- Please, do not alter this file, it could break your DB',
            '-- ',
            'SET AUTOCOMMIT = 0;',
            'SET FOREIGN_KEY_CHECKS = 0;',
            'SET UNIQUE_CHECKS = 0;',
            '-- ',
            sprintf('CREATE DATABASE IF NOT EXISTS `%s`;', $dbName),
            '',
            sprintf('USE `%s`;', $dbName),
            ''
        ];

        $this->dbBackupFile->write(implode(PHP_EOL, $sqlOut));

        $tables = $this->getTables();
        $views = $this->getViews();

        foreach ($tables as $table) {
            $sqlOut = [
                '-- ',
                sprintf('-- Table %s', strtoupper($table)),
                '-- ',
                sprintf('DROP TABLE IF EXISTS `%s`;', $table),
                sprintf('%s;', $this->getSchemaDefinition($table, 'Create Table')),
                ''
            ];

            $this->dbBackupFile->write(implode(PHP_EOL, $sqlOut));
        }

        foreach ($views as $view) {
            $sqlOut = [
                '-- ',
                sprintf('-- View %s', strtoupper($view)),
                '-- ',
                sprintf('DROP VIEW IF EXISTS `%s`;', $view),
                sprintf('%s;', $this->getSchemaDefinition($view, 'Create View')),
                ''
            ];

            $this->dbBackupFile->write(implode(PHP_EOL, $sqlOut));
        }

        // Save tables' values
        foreach ($tables as $table) {
            $query = $this->queryFactory->buildQuery(sprintf('SELECT * FROM `%s`', $table), []);

            // Get table records
            $rows = $this->database->doFetchWithOptions(
                $this->queryFactory->buildQueryData($query),
                [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL],
                PDO::FETCH_NUM,
                false
            );

            foreach ($rows as $row) {
                $values = array_map(
                    function (mixed $value) {
                        if (is_numeric($value)) {
                            return $value;
                        } elseif ($value) {
                            return $this->databaseUtil->escape((string)$value);
                        }

                        return null;
                    },
                    $row
                );

                $this->dbBackupFile->write(
                    sprintf('INSERT INTO `%s` VALUES(%s);' . PHP_EOL, $table, implode(',', $values))
                );
            }
        }

        $sqlOut = [
            '-- ',
            'SET AUTOCOMMIT = 1;',
            'SET FOREIGN_KEY_CHECKS = 1;',
            'SET UNIQUE_CHECKS = 1;',
            '-- ',
            sprintf('-- sysPass DB dump generated on %s (END)', time()),
            '-- ',
            '-- Please, do not alter this file, it could break your DB',
            '-- '
        ];

        $this->dbBackupFile->write(implode(PHP_EOL, $sqlOut));

        $this->dbArchiveHandler->compressFile($this->dbBackupFile->getFile());
        $this->dbBackupFile->delete();
    }

    /**
     * @return array|string[]
     */
    private function getTables(): array
    {
        return array_filter(DatabaseTables::TABLES, static fn(string $t) => strrpos($t, '_v') === false);
    }

    /**
     * @return array|string[]
     */
    private function getViews(): array
    {
        return array_filter(DatabaseTables::TABLES, static fn(string $t) => strrpos($t, '_v') !== false);
    }

    /**
     * Fetch the CREATE statement for a table or view.
     *
     * SHOW CREATE TABLE is not a SELECT, so it must be fetched through
     * doFetchWithOptions() instead of runQuery().
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    private function getSchemaDefinition(string $object, string $column): string
    {
        $query = $this->queryFactory->buildQuery(sprintf('SHOW CREATE TABLE `%s`', $object), []);

        foreach ($this->database->doFetchWithOptions(
            $this->queryFactory->buildQueryData($query),
            [],
            PDO::FETCH_ASSOC
        ) as $row) {
            if (isset($row[$column])) {
                return (string)$row[$column];
            }

            break;
        }

        throw ServiceException::error(sprintf(__u('Unable to get the schema of %s'), $object));
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @param string $directory
     * @throws FileException
     */
    private function backupApp(string $directory): void
    {
        $this->eventDispatcher->notify(
            'run.backup.process',
            new Event($this, EventMessage::build()->addDescription(__u('Copying application')))
        );

        $this->appArchiveHandler->compressDirectory($directory, self::BACKUP_INCLUDE_REGEX);
    }
}
