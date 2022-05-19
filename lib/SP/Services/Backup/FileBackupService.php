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

namespace SP\Services\Backup;

use Exception;
use PDO;
use SP\Config\Config;
use SP\Config\ConfigDataInterface;
use SP\Core\AppInfoInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\CheckException;
use SP\Core\Exceptions\SPException;
use SP\Services\ServiceException;
use SP\Storage\Database\Database;
use SP\Storage\Database\DatabaseUtil;
use SP\Storage\Database\QueryData;
use SP\Storage\File\ArchiveHandler;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;
use SP\Util\Checks;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de realizar la copia de sysPass.
 */
final class FileBackupService
{
    public const BACKUP_INCLUDE_REGEX = /** @lang RegExp */
        '#^(?:[A-Z]:)?(?:/(?!(\.git|backup|cache|temp|vendor|tests))[^/]+)+/[^/]+\.\w+$#Di';

    private Database            $database;
    private DatabaseUtil        $databaseUtil;
    private EventDispatcher     $eventDispatcher;
    private Config              $config;
    private ConfigDataInterface $configData;
    private BackupFiles         $backupFiles;
    private ?string             $backupPath = null;

    public function __construct(
        Application $application,
        Database $database,
        DatabaseUtil $databaseUtil,
        BackupFiles $backupFiles
    ) {
        $this->config = $application->getConfig();
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->database = $database;
        $this->databaseUtil = $databaseUtil;
        $this->backupFiles = $backupFiles;

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

            $this->eventDispatcher->notifyEvent(
                'run.backup.start',
                new Event(
                    $this,
                    EventMessage::factory()->addDescription(__u('Make Backup'))
                )
            );

            $this->backupTables($this->backupFiles->getDbBackupFileHandler());

            if (!$this->backupApp($applicationPath)
                && !$this->backupAppLegacyLinux($applicationPath)
            ) {
                throw new ServiceException(__u('Error while doing the backup in compatibility mode'));
            }

            $this->configData->setBackupHash($this->backupFiles->getHash());
            $this->config->saveConfig($this->configData);
        } catch (ServiceException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

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
        $path = $this->backupPath.DIRECTORY_SEPARATOR.AppInfoInterface::APP_NAME;

        array_map(
            static function ($file) {
                return @unlink($file);
            },
            array_merge(
                glob($path.'_db-*'),
                glob($path.'_app-*'),
                glob($path.'*.sql')
            )
        );
    }

    /**
     * Backup de las tablas de la BBDD.
     * Utilizar '*' para toda la BBDD o 'table1 table2 table3...'
     *
     * @throws \SP\Core\Exceptions\CheckException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Storage\Database\DatabaseException
     * @throws \SP\Storage\File\FileException
     */
    private function backupTables(
        FileHandler $fileHandler,
    ): void {
        $this->eventDispatcher->notifyEvent(
            'run.backup.process',
            new Event(
                $this,
                EventMessage::factory()->addDescription(__u('Copying database'))
            )
        );

        $fileHandler->open('w');

        $queryData = new QueryData();

        $tables = DatabaseUtil::TABLES;

        $lineSeparator = PHP_EOL.PHP_EOL;

        $dbname = $this->database->getDbHandler()->getDatabaseName();

        $sqlOut = '-- '.PHP_EOL;
        $sqlOut .= '-- sysPass DB dump generated on '.time().' (START)'.PHP_EOL;
        $sqlOut .= '-- '.PHP_EOL;
        $sqlOut .= '-- Please, do not alter this file, it could break your DB'.PHP_EOL;
        $sqlOut .= '-- '.PHP_EOL;
        $sqlOut .= 'SET AUTOCOMMIT = 0;'.PHP_EOL;
        $sqlOut .= 'SET FOREIGN_KEY_CHECKS = 0;'.PHP_EOL;
        $sqlOut .= 'SET UNIQUE_CHECKS = 0;'.PHP_EOL;
        $sqlOut .= '-- '.PHP_EOL;
        $sqlOut .= 'CREATE DATABASE IF NOT EXISTS `'.$dbname.'`;'.PHP_EOL.PHP_EOL;
        $sqlOut .= 'USE `'.$dbname.'`;'.PHP_EOL.PHP_EOL;

        $fileHandler->write($sqlOut);

        $sqlOutViews = '';
        // Recorrer las tablas y almacenar los datos
        foreach ($tables as $table) {
            $tableName = is_object($table) ? $table->{'Tables_in_'.$dbname} : $table;

            $queryData->setQuery('SHOW CREATE TABLE '.$tableName);

            // Consulta para crear la tabla
            $txtCreate = $this->database->doQuery($queryData)->getData();

            if (isset($txtCreate->{'Create Table'})) {
                $sqlOut = '-- '.PHP_EOL;
                $sqlOut .= '-- Table '.strtoupper($tableName).PHP_EOL;
                $sqlOut .= '-- '.PHP_EOL;
                $sqlOut .= 'DROP TABLE IF EXISTS `'.$tableName.'`;'.PHP_EOL.PHP_EOL;
                $sqlOut .= $txtCreate->{'Create Table'}.';'.PHP_EOL.PHP_EOL;

                $fileHandler->write($sqlOut);
            } elseif (isset($txtCreate->{'Create View'})) {
                $sqlOutViews .= '-- '.PHP_EOL;
                $sqlOutViews .= '-- View '.strtoupper($tableName).PHP_EOL;
                $sqlOutViews .= '-- '.PHP_EOL;
                $sqlOutViews .= 'DROP TABLE IF EXISTS `'.$tableName.'`;'.PHP_EOL.PHP_EOL;
                $sqlOutViews .= $txtCreate->{'Create View'}.';'.PHP_EOL.PHP_EOL;
            }

            $fileHandler->write($lineSeparator);
        }

        // Guardar las vistas
        $fileHandler->write($sqlOutViews);

        // Guardar los datos
        foreach ($tables as $tableName) {
            // No guardar las vistas!
            if (strrpos($tableName, '_v') !== false) {
                continue;
            }

            $queryData->setQuery('SELECT * FROM `'.$tableName.'`');

            // Consulta para obtener los registros de la tabla
            $queryRes = $this->database->doQueryRaw($queryData, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL], false);

            $numColumns = $queryRes->columnCount();

            while ($row = $queryRes->fetch(PDO::FETCH_NUM)) {
                $fileHandler->write('INSERT INTO `'.$tableName.'` VALUES(');

                $field = 1;
                foreach ($row as $value) {
                    if (is_numeric($value)) {
                        $fileHandler->write($value);
                    } elseif ($value) {
                        $fileHandler->write($this->databaseUtil->escape($value));
                    } else {
                        $fileHandler->write(null);
                    }

                    if ($field < $numColumns) {
                        $fileHandler->write(',');
                    }

                    $field++;
                }

                $fileHandler->write(');'.PHP_EOL);
            }
        }

        $sqlOut = '-- '.PHP_EOL;
        $sqlOut .= 'SET AUTOCOMMIT = 1;'.PHP_EOL;
        $sqlOut .= 'SET FOREIGN_KEY_CHECKS = 1;'.PHP_EOL;
        $sqlOut .= 'SET UNIQUE_CHECKS = 1;'.PHP_EOL;
        $sqlOut .= '-- '.PHP_EOL;
        $sqlOut .= '-- sysPass DB dump generated on '.time().' (END)'.PHP_EOL;
        $sqlOut .= '-- '.PHP_EOL;
        $sqlOut .= '-- Please, do not alter this file, it could break your DB'.PHP_EOL;
        $sqlOut .= '-- '.PHP_EOL.PHP_EOL;

        $fileHandler->write($sqlOut);
        $fileHandler->close();

        $this->backupFiles->getDbBackupArchiveHandler()->compressFile($fileHandler->getFile());

        $fileHandler->delete();
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @throws CheckException
     * @throws FileException
     */
    private function backupApp(string $directory): bool
    {
        $this->eventDispatcher->notifyEvent(
            'run.backup.process',
            new Event(
                $this, EventMessage::factory()
                ->addDescription(__u('Copying application'))
            )
        );

        $this->backupFiles->getAppBackupArchiveHandler()->compressDirectory($directory, self::BACKUP_INCLUDE_REGEX);

        return true;
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo usando aplicaciones del SO Linux.
     *
     * @throws ServiceException
     */
    private function backupAppLegacyLinux(string $directory): int
    {
        if (Checks::checkIsWindows()) {
            throw new ServiceException(
                __u('This operation is only available on Linux environments'),
                SPException::INFO
            );
        }

        $this->eventDispatcher->notifyEvent(
            'run.backup.process',
            new Event(
                $this, EventMessage::factory()
                ->addDescription(__u('Copying application'))
            )
        );

        $command = sprintf(
            'tar czf %s%s %s --exclude "%s" 2>&1',
            $this->backupFiles->getAppBackupFileHandler()->getFile(),
            ArchiveHandler::COMPRESS_EXTENSION,
            $directory,
            $this->backupPath
        );
        exec($command, $resOut, $resBakApp);

        return $resBakApp;
    }

    public function getHash(): string
    {
        return $this->backupFiles->getHash();
    }
}