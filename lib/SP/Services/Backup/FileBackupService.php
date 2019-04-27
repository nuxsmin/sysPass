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

namespace SP\Services\Backup;

use Exception;
use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Config\ConfigData;
use SP\Core\AppInfoInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\CheckException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Core\PhpExtensionChecker;
use SP\Services\Service;
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
final class FileBackupService extends Service
{
    /**
     * @var ConfigData
     */
    private $configData;
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $backupFileApp;
    /**
     * @var string
     */
    private $backupFileDb;
    /**
     * @var PhpExtensionChecker
     */
    private $extensionChecker;
    /**
     * @var string
     */
    private $hash;

    /**
     * Realizar backup de la BBDD y aplicación.
     *
     * @param string $path
     *
     * @throws ServiceException
     */
    public function doBackup(string $path)
    {
        set_time_limit(0);

        $this->path = $path;

        $this->checkBackupDir();

        // Generar hash unico para evitar descargas no permitidas
        $this->hash = sha1(uniqid('sysPassBackup', true));

        $this->backupFileApp = self::getAppBackupFilename($path, $this->hash);
        $this->backupFileDb = self::getDbBackupFilename($path, $this->hash);

        try {
            $this->deleteOldBackups();

            $this->eventDispatcher->notifyEvent('run.backup.start',
                new Event($this,
                    EventMessage::factory()->addDescription(__u('Make Backup'))));

            $this->backupTables(new FileHandler($this->backupFileDb), '*');

            if (!$this->backupApp()
                && !$this->backupAppLegacyLinux()
            ) {
                throw new ServiceException(__u('Error while doing the backup in compatibility mode'));
            }

            $this->configData->setBackupHash($this->hash);
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
     * Comprobar y crear el directorio de backups.
     *
     * @return bool
     * @throws ServiceException
     */
    private function checkBackupDir()
    {
        if (is_dir($this->path) === false
            && @mkdir($this->path, 0750) === false
        ) {
            throw new ServiceException(
                sprintf(__('Unable to create the backups directory ("%s")'), $this->path));
        }

        if (!is_writable($this->path)) {
            throw new ServiceException(
                __u('Please, check the backup directory permissions'));
        }

        return true;
    }

    /**
     * @param string $path
     * @param string $hash
     * @param bool   $compressed
     *
     * @return string
     */
    public static function getAppBackupFilename(string $path, string $hash, bool $compressed = false)
    {
        $file = $path . DIRECTORY_SEPARATOR . AppInfoInterface::APP_NAME . '_app-' . $hash;

        if ($compressed) {
            return $file . ArchiveHandler::COMPRESS_EXTENSION;
        }

        return $file;
    }

    /**
     * @param string $path
     * @param string $hash
     * @param bool   $compressed
     *
     * @return string
     */
    public static function getDbBackupFilename(string $path, string $hash, bool $compressed = false)
    {
        $file = $path . DIRECTORY_SEPARATOR . AppInfoInterface::APP_NAME . '_db-' . $hash;

        if ($compressed) {
            return $file . ArchiveHandler::COMPRESS_EXTENSION;
        }

        return $file . '.sql';
    }

    /**
     * Eliminar las copias de seguridad anteriores
     */
    private function deleteOldBackups()
    {
        $path = $this->path . DIRECTORY_SEPARATOR . AppInfoInterface::APP_NAME;

        array_map(function ($file) {
            return @unlink($file);
        }, array_merge(
            glob($path . '_db-*'),
            glob($path . '_app-*'),
            glob($path . '*.sql')
        ));
    }

    /**
     * Backup de las tablas de la BBDD.
     * Utilizar '*' para toda la BBDD o 'table1 table2 table3...'
     *
     * @param FileHandler  $fileHandler
     * @param string|array $tables
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws FileException
     * @throws CheckException
     */
    private function backupTables(FileHandler $fileHandler, $tables = '*')
    {
        $this->eventDispatcher->notifyEvent('run.backup.process',
            new Event($this,
                EventMessage::factory()->addDescription(__u('Copying database')))
        );

        $fileHandler->open('w');

        $db = $this->dic->get(Database::class);
        $databaseUtil = $this->dic->get(DatabaseUtil::class);

        $queryData = new QueryData();

        if ($tables === '*') {
            $resTables = DatabaseUtil::$tables;
        } else {
            $resTables = is_array($tables) ? $tables : explode(',', $tables);
        }

        $lineSeparator = PHP_EOL . PHP_EOL;

        $dbname = $db->getDbHandler()->getDatabaseName();

        $sqlOut = '-- ' . PHP_EOL;
        $sqlOut .= '-- sysPass DB dump generated on ' . time() . ' (START)' . PHP_EOL;
        $sqlOut .= '-- ' . PHP_EOL;
        $sqlOut .= '-- Please, do not alter this file, it could break your DB' . PHP_EOL;
        $sqlOut .= '-- ' . PHP_EOL;
        $sqlOut .= 'SET AUTOCOMMIT = 0;' . PHP_EOL;
        $sqlOut .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;
        $sqlOut .= 'SET UNIQUE_CHECKS = 0;' . PHP_EOL;
        $sqlOut .= '-- ' . PHP_EOL;
        $sqlOut .= 'CREATE DATABASE IF NOT EXISTS `' . $dbname . '`;' . PHP_EOL . PHP_EOL;
        $sqlOut .= 'USE `' . $dbname . '`;' . PHP_EOL . PHP_EOL;

        $fileHandler->write($sqlOut);

        $sqlOutViews = '';
        // Recorrer las tablas y almacenar los datos
        foreach ($resTables as $table) {
            $tableName = is_object($table) ? $table->{'Tables_in_' . $dbname} : $table;

            $queryData->setQuery('SHOW CREATE TABLE ' . $tableName);

            // Consulta para crear la tabla
            $txtCreate = $db->doQuery($queryData)->getData();

            if (isset($txtCreate->{'Create Table'})) {
                $sqlOut = '-- ' . PHP_EOL;
                $sqlOut .= '-- Table ' . strtoupper($tableName) . PHP_EOL;
                $sqlOut .= '-- ' . PHP_EOL;
                $sqlOut .= 'DROP TABLE IF EXISTS `' . $tableName . '`;' . PHP_EOL . PHP_EOL;
                $sqlOut .= $txtCreate->{'Create Table'} . ';' . PHP_EOL . PHP_EOL;

                $fileHandler->write($sqlOut);
            } elseif (isset($txtCreate->{'Create View'})) {
                $sqlOutViews .= '-- ' . PHP_EOL;
                $sqlOutViews .= '-- View ' . strtoupper($tableName) . PHP_EOL;
                $sqlOutViews .= '-- ' . PHP_EOL;
                $sqlOutViews .= 'DROP TABLE IF EXISTS `' . $tableName . '`;' . PHP_EOL . PHP_EOL;
                $sqlOutViews .= $txtCreate->{'Create View'} . ';' . PHP_EOL . PHP_EOL;
            }

            $fileHandler->write($lineSeparator);
        }

        // Guardar las vistas
        $fileHandler->write($sqlOutViews);

        // Guardar los datos
        foreach ($resTables as $tableName) {
            // No guardar las vistas!
            if (strrpos($tableName, '_v') !== false) {
                continue;
            }

            $queryData->setQuery('SELECT * FROM `' . $tableName . '`');

            // Consulta para obtener los registros de la tabla
            $queryRes = $db->doQueryRaw($queryData, [PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL], false);

            $numColumns = $queryRes->columnCount();

            while ($row = $queryRes->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
                $fileHandler->write('INSERT INTO `' . $tableName . '` VALUES(');

                $field = 1;
                foreach ($row as $value) {
                    if (is_numeric($value)) {
                        $fileHandler->write($value);
                    } else {
                        $fileHandler->write($databaseUtil->escape($value));
                    }

                    if ($field < $numColumns) {
                        $fileHandler->write(',');
                    }

                    $field++;
                }

                $fileHandler->write(');' . PHP_EOL);
            }
        }

        $sqlOut = '-- ' . PHP_EOL;
        $sqlOut .= 'SET AUTOCOMMIT = 1;' . PHP_EOL;
        $sqlOut .= 'SET FOREIGN_KEY_CHECKS = 1;' . PHP_EOL;
        $sqlOut .= 'SET UNIQUE_CHECKS = 1;' . PHP_EOL;
        $sqlOut .= '-- ' . PHP_EOL;
        $sqlOut .= '-- sysPass DB dump generated on ' . time() . ' (END)' . PHP_EOL;
        $sqlOut .= '-- ' . PHP_EOL;
        $sqlOut .= '-- Please, do not alter this file, it could break your DB' . PHP_EOL;
        $sqlOut .= '-- ' . PHP_EOL . PHP_EOL;

        $fileHandler->write($sqlOut);
        $fileHandler->close();

        $archive = new ArchiveHandler($fileHandler->getFile(), $this->extensionChecker);
        $archive->compressFile($fileHandler->getFile());

        $fileHandler->delete();
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @return bool
     * @throws CheckException
     * @throws FileException
     */
    private function backupApp()
    {
        $this->eventDispatcher->notifyEvent('run.backup.process',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Copying application')))
        );

        $archive = new ArchiveHandler($this->backupFileApp, $this->extensionChecker);
        $archive->compressDirectory(APP_ROOT, '#^(?!(.*backup))(.*)$#i');

        return true;
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo usando aplicaciones del SO Linux.
     *
     * @return int Con el código de salida del comando ejecutado
     * @throws ServiceException
     */
    private function backupAppLegacyLinux()
    {
        if (Checks::checkIsWindows()) {
            throw new ServiceException(
                __u('This operation is only available on Linux environments'), ServiceException::INFO);
        }

        $this->eventDispatcher->notifyEvent('run.backup.process',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Copying application')))
        );

        $command = 'tar czf ' . $this->backupFileApp . ArchiveHandler::COMPRESS_EXTENSION . ' ' . BASE_PATH . ' --exclude "' . $this->path . '" 2>&1';
        exec($command, $resOut, $resBakApp);

        return $resBakApp;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->configData = $this->config->getConfigData();
        $this->extensionChecker = $this->dic->get(PhpExtensionChecker::class);
    }
}