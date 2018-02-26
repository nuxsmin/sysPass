<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Bootstrap;
use SP\Config\ConfigData;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database;
use SP\Storage\DBUtil;
use SP\Storage\DbWrapper;
use SP\Storage\FileHandler;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de realizar la copia de sysPass.
 */
class FileBackupService extends Service
{
    /**
     * @var ConfigData
     */
    protected $configData;

    /**
     * Realizar backup de la BBDD y aplicación.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ServiceException
     */
    public function doBackup()
    {
        $siteName = Util::getAppInfo('appname');

        // Generar hash unico para evitar descargas no permitidas
        $backupUniqueHash = sha1(uniqid('sysPassBackup', true));
        $this->configData->setBackupHash($backupUniqueHash);
        $this->config->saveConfig();

        $bakFileApp = BACKUP_PATH . DIRECTORY_SEPARATOR . $siteName . '-' . $backupUniqueHash . '.tar';
        $bakFileDB = BACKUP_PATH . DIRECTORY_SEPARATOR . $siteName . '_db-' . $backupUniqueHash . '.sql';

        try {
            $this->checkBackupDir();
            $this->deleteOldBackups();

            $this->eventDispatcher->notifyEvent('run.backup.start',
                new Event($this,
                    EventMessage::factory()->addDescription(__u('Realizar Backup'))));

            $this->backupTables('*', new FileHandler($bakFileDB));
            $this->backupApp($bakFileApp);
        } catch (ServiceException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServiceException(
                __u('Error al realizar el backup'),
                SPException::ERROR,
                __u('Revise el registro de eventos para más detalles'),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Comprobar y crear el directorio de backups.
     *
     * @throws ServiceException
     * @return bool
     */
    private function checkBackupDir()
    {
        if (@mkdir(BACKUP_PATH, 0750) === false && is_dir(BACKUP_PATH) === false) {
            throw new ServiceException(
                sprintf(__('No es posible crear el directorio de backups ("%s")'), BACKUP_PATH));
        }

        if (!is_writable(BACKUP_PATH)) {
            throw new ServiceException(
                __u('Compruebe los permisos del directorio de backups'));
        }

        return true;
    }

    /**
     * Eliminar las copias de seguridad anteriores
     */
    private function deleteOldBackups()
    {
        array_map('unlink', glob(BACKUP_PATH . DIRECTORY_SEPARATOR . '*.tar.gz'));
        array_map('unlink', glob(BACKUP_PATH . DIRECTORY_SEPARATOR . '*.sql'));
    }

    /**
     * Backup de las tablas de la BBDD.
     * Utilizar '*' para toda la BBDD o 'table1 table2 table3...'
     *
     * @param string|array $tables
     * @param FileHandler $fileHandler
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SPException
     * @throws \SP\Core\Dic\ContainerException
     * @throws \SP\Storage\FileException
     */
    private function backupTables($tables = '*', FileHandler $fileHandler)
    {
        $this->eventDispatcher->notifyEvent('run.backup.process',
            new Event($this,
                EventMessage::factory()->addDescription(__u('Copiando base de datos')))
        );

        $fileHandler->open('w');

        $db = $this->dic->get(Database::class);

        $queryData = new QueryData();

        if ($tables === '*') {
            $resTables = DBUtil::$tables;
        } else {
            $resTables = is_array($tables) ? $tables : explode(',', $tables);
        }

        $lineSeparator = PHP_EOL . PHP_EOL;

        $dbname = $this->configData->getDbName();

        $sqlOut = '--' . PHP_EOL;
        $sqlOut .= '-- sysPass DB dump generated on ' . time() . ' (START)' . PHP_EOL;
        $sqlOut .= '--' . PHP_EOL;
        $sqlOut .= '-- Please, do not alter this file, it could break your DB' . PHP_EOL;
        $sqlOut .= '--' . PHP_EOL . PHP_EOL;
        $sqlOut .= 'CREATE DATABASE IF NOT EXISTS `' . $dbname . '`;' . PHP_EOL . PHP_EOL;
        $sqlOut .= 'USE `' . $dbname . '`;' . PHP_EOL . PHP_EOL;

        $fileHandler->write($sqlOut);

        $sqlOutViews = '';
        // Recorrer las tablas y almacenar los datos
        foreach ($resTables as $table) {
            $tableName = is_object($table) ? $table->{'Tables_in_' . $dbname} : $table;

            $queryData->setQuery('SHOW CREATE TABLE ' . $tableName);

            // Consulta para crear la tabla
            $txtCreate = DbWrapper::getResults($queryData, $db);

            if (isset($txtCreate->{'Create Table'})) {
                $sqlOut = '-- ' . PHP_EOL;
                $sqlOut .= '-- Table ' . strtoupper($tableName) . PHP_EOL;
                $sqlOut .= '-- ' . PHP_EOL;
                $sqlOut .= 'DROP TABLE IF EXISTS `' . $tableName . '`;' . PHP_EOL . PHP_EOL;
                $sqlOut .= $txtCreate->{'Create Table'} . ';' . PHP_EOL . PHP_EOL;

                $fileHandler->write($sqlOut);
            } elseif ($txtCreate->{'Create View'}) {
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
            $queryRes = DbWrapper::getResultsRaw($queryData, $db);

            $numColumns = $queryRes->columnCount();

            while ($row = $queryRes->fetch(\PDO::FETCH_NUM)) {
                $fileHandler->write('INSERT INTO `' . $tableName . '` VALUES(');

                $field = 1;
                foreach ($row as $value) {
                    if (is_numeric($value)) {
                        $fileHandler->write($value);
                    } else {
                        $fileHandler->write(DBUtil::escape($value, $db->getDbHandler()));
                    }

                    if ($field < $numColumns) {
                        $fileHandler->write(',');
                    }

                    $field++;
                }

                $fileHandler->write(');' . PHP_EOL);
            }
        }

        $sqlOut = '--' . PHP_EOL;
        $sqlOut .= '-- sysPass DB dump generated on ' . time() . ' (END)' . PHP_EOL;
        $sqlOut .= '--' . PHP_EOL;
        $sqlOut .= '-- Please, do not alter this file, it could break your DB' . PHP_EOL;
        $sqlOut .= '--' . PHP_EOL . PHP_EOL;

        $fileHandler->write($sqlOut);
        $fileHandler->close();
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @param string $backupFile nombre del archivo de backup
     * @return bool
     * @throws ServiceException
     */
    private function backupApp($backupFile)
    {
        $this->eventDispatcher->notifyEvent('run.backup.process',
            new Event($this,
                EventMessage::factory()->addDescription(__u('Copiando aplicación')))
        );

        if (!class_exists(\PharData::class)) {
            if (Checks::checkIsWindows()) {
                throw new ServiceException(
                    __u('Esta operación sólo es posible en entornos Linux'), ServiceException::INFO);
            }

            if (!$this->backupAppLegacyLinux($backupFile)) {
                throw new ServiceException(
                    __u('Error al realizar backup en modo compatibilidad'));
            }
        }

        $compressedFile = $backupFile . '.gz';

        if (file_exists($compressedFile)) {
            unlink($compressedFile);
        }

        $archive = new \PharData($backupFile);
        $archive->buildFromDirectory(Bootstrap::$SERVERROOT, '/^(?!backup).*$/');
        $archive->compress(\Phar::GZ);

        unlink($backupFile);

        return file_exists($backupFile);
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo usando aplicaciones del SO Linux.
     *
     * @param string $backupFile nombre del archivo de backup
     * @return int Con el código de salida del comando ejecutado
     */
    private function backupAppLegacyLinux($backupFile)
    {
        $compressedFile = $backupFile . '.gz';

        $command = 'tar czf ' . $compressedFile . ' ' . BASE_PATH . ' --exclude "' . BACKUP_PATH . '" 2>&1';
        exec($command, $resOut, $resBakApp);

        return $resBakApp;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->configData = $this->config->getConfigData();
    }
}