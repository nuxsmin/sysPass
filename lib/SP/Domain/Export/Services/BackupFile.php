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

namespace SP\Domain\Export\Services;

use Exception;
use PDO;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Export\Ports\BackupFileHelperService;
use SP\Domain\Export\Ports\BackupFileService;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Infrastructure\Common\Repositories\Query;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\File\FileException;

use function SP\__u;

/**
 * Class BackupFile
 */
final class BackupFile implements BackupFileService
{
    public const BACKUP_INCLUDE_REGEX = /** @lang RegExp */
        '#^(?:[A-Z]:)?(?:/(?!(\.git|backup|cache|temp|vendor|tests))[^/]+)+/[^/]+\.\w+$#Di';

    private EventDispatcherInterface $eventDispatcher;
    private ConfigFileService $config;
    private ConfigDataInterface      $configData;
    private ?string                  $backupPath = null;

    public function __construct(
        Application                              $application,
        private readonly DatabaseInterface       $database,
        private readonly DatabaseUtil            $databaseUtil,
        private readonly BackupFileHelperService $backupFileHelperService
    ) {
        $this->config = $application->getConfig();
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->configData = $this->config->getConfigData();
    }

    /**
     * Realizar backup de la BBDD y aplicación.
     *
     * @throws ServiceException
     */
    public function doBackup(string $backupPath = BACKUP_PATH, string $applicationPath = APP_ROOT): void
    {
        set_time_limit(0);

        $this->backupPath = $backupPath;

        try {
            $this->deleteOldBackups();

            $this->eventDispatcher->notify(
                'run.backup.start',
                new Event($this, EventMessage::factory()->addDescription(__u('Make Backup')))
            );

            $this->backupTables($this->backupFileHelperService->getDbBackupFileHandler());
            $this->backupApp($applicationPath);

            $this->configData->setBackupHash($this->backupFileHelperService->getHash());
            $this->config->save($this->configData);
        } catch (Exception $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            throw new ServiceException(
                __u('Error while doing the backup'),
                SPException::ERROR,
                __u('Please check out the event log for more details'),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Eliminar las copias de seguridad anteriores
     */
    private function deleteOldBackups(): void
    {
        $path = sprintf("%s%s%s", $this->backupPath, DIRECTORY_SEPARATOR, AppInfoInterface::APP_NAME);

        array_map(
            static function ($file) {
                return @unlink($file);
            },
            array_merge(
                glob($path . '_db-*'),
                glob($path . '_app-*'),
                glob($path . '*.sql')
            )
        );
    }

    /**
     * Backup de las tablas de la BBDD.
     * Utilizar '*' para toda la BBDD o 'table1 table2 table3...'
     *
     * @param FileHandlerInterface $fileHandler
     * @throws CheckException
     * @throws ConstraintException
     * @throws FileException
     * @throws QueryException
     * @throws SPException
     */
    private function backupTables(FileHandlerInterface $fileHandler): void
    {
        $this->eventDispatcher->notify(
            'run.backup.process',
            new Event(
                $this,
                EventMessage::factory()->addDescription(__u('Copying database'))
            )
        );

        $fileHandler->open('w');

        $dbname = $this->configData->getDbName();

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
            sprintf('CREATE DATABASE IF NOT EXISTS `%s`;', $dbname),
            '',
            sprintf('USE `%s`;', $dbname),
            ''
        ];

        $fileHandler->write(implode(PHP_EOL, $sqlOut));

        $tables = $this->getTables();
        $views = $this->getViews();

        foreach ($tables as $table) {
            $query = Query::buildForMySQL(sprintf('SHOW CREATE TABLE %s', $table), []);

            $data = $this->database->runQuery(QueryData::build($query))->getData();

            $sqlOut = [
                '-- ',
                sprintf('-- Table %s', strtoupper($table)),
                '-- ',
                sprintf('DROP TABLE IF EXISTS `%s`;', $table),
                sprintf('%s;', $data->{'Create Table'}),
                ''
            ];

            $fileHandler->write(implode(PHP_EOL, $sqlOut));
        }

        foreach ($views as $view) {
            $query = Query::buildForMySQL(sprintf('SHOW CREATE TABLE %s', $view), []);

            $data = $this->database->runQuery(QueryData::build($query))->getData();

            $sqlOut = [
                '-- ',
                sprintf('-- View %s', strtoupper($view)),
                '-- ',
                sprintf('DROP TABLE IF EXISTS `%s`;', $view),
                sprintf('%s;', $data->{'Create View'}),
                ''
            ];

            $fileHandler->write(implode(PHP_EOL, $sqlOut));
        }

        // Save tables' values
        foreach ($tables as $table) {
            $query = Query::buildForMySQL(sprintf('SELECT * FROM `%s`', $table), []);

            // Get table records
            $rows = $this->database->doFetchWithOptions(
                QueryData::build($query),
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
                            return $this->databaseUtil->escape($value);
                        }

                        return null;
                    },
                    $row
                );

                $fileHandler->write(sprintf('INSERT INTO `%s` VALUES(%s);' . PHP_EOL, $table, implode(',', $values)));
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

        $fileHandler->write(implode(PHP_EOL, $sqlOut));

        $this->backupFileHelperService->getDbBackupArchiveHandler()->compressFile($fileHandler->getFile());

        $fileHandler->delete();
    }

    /**
     * @return array|string[]
     */
    private function getTables(): array
    {
        return array_filter(DatabaseUtil::TABLES, static fn(string $t) => strrpos($t, '_v') === false);
    }

    /**
     * @return array|string[]
     */
    private function getViews(): array
    {
        return array_filter(DatabaseUtil::TABLES, static fn(string $t) => strrpos($t, '_v') !== false);
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @throws CheckException
     * @throws FileException
     */
    private function backupApp(string $directory): void
    {
        $this->eventDispatcher->notify(
            'run.backup.process',
            new Event($this, EventMessage::factory()->addDescription(__u('Copying application')))
        );

        $this->backupFileHelperService->getAppBackupArchiveHandler()->compressDirectory(
            $directory,
            self::BACKUP_INCLUDE_REGEX
        );
    }

    public function getHash(): string
    {
        return $this->backupFileHelperService->getHash();
    }
}
