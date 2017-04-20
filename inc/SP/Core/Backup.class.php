<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core;

use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\DBUtil;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de realizar la copia y restauración de sysPass.
 */
class Backup
{
    /**
     * Realizar backup de la BBDD y aplicación.
     *
     * @return bool
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function doBackup()
    {
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Realizar Backup', false));

        $siteName = Util::getAppInfo('appname');
        $backupDir = Init::$SERVERROOT;

        // Generar hash unico para evitar descargas no permitidas
        $backupUniqueHash = sha1(uniqid('sysPassBackup', true));
        Config::getConfig()->setBackupHash($backupUniqueHash);
        Config::saveConfig();

        $backupDstDir = $backupDir . DIRECTORY_SEPARATOR . 'backup';
        $bakFileApp = $backupDstDir . DIRECTORY_SEPARATOR . $siteName . '-' . $backupUniqueHash . '.tar';
        $bakFileDB = $backupDstDir . DIRECTORY_SEPARATOR . $siteName . '_db-' . $backupUniqueHash . '.sql';

        try {
            self::checkBackupDir($backupDstDir);
            self::deleteOldBackups($backupDstDir);
            self::backupTables('*', $bakFileDB);
            self::backupApp($bakFileApp);
        } catch (\Exception $e) {
            $LogMessage->addDescription(__('Error al realizar el backup', false));
            $LogMessage->addDetails($e->getCode(), $e->getMessage());
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();

            Email::sendEmail($LogMessage);
            return false;
        }

        $LogMessage->addDescription(__('Copia de la aplicación y base de datos realizada correctamente', false));
        $Log->writeLog();

        Email::sendEmail($LogMessage);

        return true;
    }

    /**
     * Comprobar y crear el directorio de backups.
     *
     * @param string $backupDir ruta del directorio de backup
     * @throws SPException
     * @return bool
     */
    private static function checkBackupDir($backupDir)
    {
        if (@mkdir($backupDir, 0750) === false && is_dir($backupDir) === false) {
            throw new SPException(SPException::SP_CRITICAL, sprintf(__('No es posible crear el directorio de backups ("%s")'), $backupDir));
        }

        if (!is_writable($backupDir)) {
            throw new SPException(SPException::SP_CRITICAL, __('Compruebe los permisos del directorio de backups', false));
        }

        return true;
    }

    /**
     * Eliminar las copias de seguridad anteriores
     *
     * @param string $backupDir El directorio de backups
     */
    private static function deleteOldBackups($backupDir)
    {
        array_map('unlink', glob($backupDir . DIRECTORY_SEPARATOR . '*.tar.gz'));
        array_map('unlink', glob($backupDir . DIRECTORY_SEPARATOR . '*.sql'));
    }

    /**
     * Backup de las tablas de la BBDD.
     * Utilizar '*' para toda la BBDD o 'table1 table2 table3...'
     *
     * @param string|array $tables
     * @param string       $backupFile
     * @throws SPException
     * @return bool
     */
    private static function backupTables($tables = '*', $backupFile)
    {
        $dbname = Config::getConfig()->getDbName();

        try {
            $handle = fopen($backupFile, 'w');

            $Data = new QueryData();

            if ($tables === '*') {
                $resTables = DBUtil::$tables;
            } else {
                $resTables = is_array($tables) ? $tables : explode(',', $tables);
            }

            $sqlOut = '--' . PHP_EOL;
            $sqlOut .= '-- sysPass DB dump generated on ' . time() . ' (START)' . PHP_EOL;
            $sqlOut .= '--' . PHP_EOL;
            $sqlOut .= '-- Please, do not alter this file, it could break your DB' . PHP_EOL;
            $sqlOut .= '--' . PHP_EOL . PHP_EOL;
            $sqlOut .= 'CREATE DATABASE IF NOT EXISTS `' . $dbname . '`;' . PHP_EOL . PHP_EOL;
            $sqlOut .= 'USE `' . $dbname . '`;' . PHP_EOL . PHP_EOL;
            fwrite($handle, $sqlOut);

            $sqlOutViews = '';
            // Recorrer las tablas y almacenar los datos
            foreach ($resTables as $table) {
                $tableName = is_object($table) ? $table->{'Tables_in_' . $dbname} : $table;

                $Data->setQuery('SHOW CREATE TABLE ' . $tableName);

                // Consulta para crear la tabla
                $txtCreate = DB::getResults($Data);

                if (isset($txtCreate->{'Create Table'})) {
                    $sqlOut = '-- ' . PHP_EOL;
                    $sqlOut .= '-- Table ' . strtoupper($tableName) . PHP_EOL;
                    $sqlOut .= '-- ' . PHP_EOL;
                    $sqlOut .= 'DROP TABLE IF EXISTS `' . $tableName . '`;' . PHP_EOL . PHP_EOL;
                    $sqlOut .= $txtCreate->{'Create Table'} . ';' . PHP_EOL . PHP_EOL;
                    fwrite($handle, $sqlOut);
                } elseif ($txtCreate->{'Create View'}) {
                    $sqlOutViews .= '-- ' . PHP_EOL;
                    $sqlOutViews .= '-- View ' . strtoupper($tableName) . PHP_EOL;
                    $sqlOutViews .= '-- ' . PHP_EOL;
                    $sqlOutViews .= 'DROP TABLE IF EXISTS `' . $tableName . '`;' . PHP_EOL . PHP_EOL;
                    $sqlOutViews .= $txtCreate->{'Create View'} . ';' . PHP_EOL . PHP_EOL;
                }

                fwrite($handle, PHP_EOL . PHP_EOL);
            }

            // Guardar las vistas
            fwrite($handle, $sqlOutViews);

            // Guardar los datos
            foreach ($resTables as $tableName) {
                // No guardar las vistas!
                if (strrpos($tableName, '_v') !== false) {
                    continue;
                }

                $Data->setQuery('SELECT * FROM `' . $tableName . '`');

                // Consulta para obtener los registros de la tabla
                $queryRes = DB::getResultsRaw($Data);

                $numColumns = $queryRes->columnCount();

                while ($row = $queryRes->fetch(\PDO::FETCH_NUM)) {
                    fwrite($handle, 'INSERT INTO `' . $tableName . '` VALUES(');

                    $field = 1;
                    foreach ($row as $value) {
                        if (is_numeric($value)) {
                            fwrite($handle, $value);
                        } else {
                            fwrite($handle, DBUtil::escape($value));
                        }

                        if ($field < $numColumns) {
                            fwrite($handle, ',');
                        }

                        $field++;
                    }

                    fwrite($handle, ');' . PHP_EOL);
                }
            }

            $sqlOut = '--' . PHP_EOL;
            $sqlOut .= '-- sysPass DB dump generated on ' . time() . ' (END)' . PHP_EOL;
            $sqlOut .= '--' . PHP_EOL;
            $sqlOut .= '-- Please, do not alter this file, it could break your DB' . PHP_EOL;
            $sqlOut .= '--' . PHP_EOL . PHP_EOL;
            fwrite($handle, $sqlOut);

            fclose($handle);
        } catch (\Exception $e) {
            throw new SPException(SPException::SP_CRITICAL, $e->getMessage());
        }

        return true;
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @param string $backupFile nombre del archivo de backup
     * @throws SPException
     * @return bool
     */
    private static function backupApp($backupFile)
    {
        if (!class_exists(\PharData::class)) {
            if (Checks::checkIsWindows()) {
                throw new SPException(SPException::SP_CRITICAL, __('Esta operación sólo es posible en entornos Linux', false));
            }

            if (!self::backupAppLegacyLinux($backupFile)) {
                throw new SPException(SPException::SP_CRITICAL, __('Error al realizar backup en modo compatibilidad', false));
            }

            return true;
        }

        $compressedFile = $backupFile . '.gz';

        try {
            if (file_exists($compressedFile)) {
                unlink($compressedFile);
            }

            $archive = new \PharData($backupFile);
            $archive->buildFromDirectory(Init::$SERVERROOT);
            $archive->compress(\Phar::GZ);

            unlink($backupFile);
        } catch (\Exception $e) {
            throw new SPException(SPException::SP_CRITICAL, $e->getMessage());
        }

        return file_exists($backupFile);
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo usando aplicaciones del SO Linux.
     *
     * @param string $backupFile nombre del archivo de backup
     * @return int Con el código de salida del comando ejecutado
     */
    private static function backupAppLegacyLinux($backupFile)
    {
        $compressedFile = $backupFile . '.gz';
        $backupDir = Init::$SERVERROOT;
        $bakDstDir = $backupDir . '/backup';

        $command = 'tar czf ' . $compressedFile . ' ' . $backupDir . ' --exclude "' . $bakDstDir . '" 2>&1';
        exec($command, $resOut, $resBakApp);

        return $resBakApp;
    }
}