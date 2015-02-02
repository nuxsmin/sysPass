<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar la copia y restauración de sysPass.
 */
class SP_Backup
{
    /**
     * Realizar backup de la BBDD y aplicación.
     *
     * @return bool
     */
    public static function doBackup()
    {
        $siteName = SP_Html::getAppInfo('appname');
        $backupDir = SP_Init::$SERVERROOT;
        $backupDstDir = $backupDir . DIRECTORY_SEPARATOR . 'backup';
        $bakFileApp = $backupDstDir . DIRECTORY_SEPARATOR . $siteName . '.tar';
        $bakFileDB = $backupDstDir . DIRECTORY_SEPARATOR . $siteName . '_db.sql';

        try {
            self::checkBackupDir($backupDstDir);
            self::backupTables('*', $bakFileDB);
            self::backupApp($bakFileApp);
        } catch (Exception $e) {

            $message['action'] = __FUNCTION__;
            $message['text'][] = $e->getMessage();

            SP_Log::wrLogInfo($message);
            SP_Common::sendEmail($message);

            return false;
        }

        return true;
    }

    /**
     * Backup de las tablas de la BBDD.
     * Utilizar '*' para toda la BBDD o 'table1 table2 table3...'
     *
     * @param string $tables
     * @param string $backupFile
     * @throws Exception
     * @return bool
     */
    private static function backupTables($tables = '*', $backupFile)
    {
        $dbname = SP_Config::getValue("dbname");

        try {
            $handle = fopen($backupFile, 'w');

            if ($tables == '*') {
                $resTables = DB::getResults('SHOW TABLES', __FUNCTION__);
            } else {
                $resTables = is_array($tables) ? $tables : explode(',', $tables);
            }

            $sqlOut = '--- sysPass DB dump generated on ' . time() . ' (start)' . PHP_EOL;
            $sqlOut .= '---' . PHP_EOL;
            $sqlOut .= '--- Please, do not alter this file, it could break your DB' . PHP_EOL;
            $sqlOut .= '---' . PHP_EOL . PHP_EOL;
            $sqlOut .= 'CREATE DATABASE IF NOT EXISTS `' . $dbname . '`;' . PHP_EOL . PHP_EOL;
            $sqlOut .= 'USE `' . $dbname . '`;' . PHP_EOL . PHP_EOL;
            fwrite($handle, $sqlOut);

            // Recorrer las tablas y almacenar los datos
            foreach ($resTables as $table) {
                $sqlOut = '';
                $tableName = $table->{'Tables_in_' . $dbname};

                // Consulta para crear la tabla
                $sqlOut .= 'DROP TABLE IF EXISTS `' . $tableName . '`;' . PHP_EOL . PHP_EOL;
                $txtCreate = DB::getResults('SHOW CREATE TABLE ' . $tableName, __FUNCTION__);
                $sqlOut .= $txtCreate->{'Create Table'} . ';' . PHP_EOL . PHP_EOL;
                fwrite($handle, $sqlOut);

                // Consulta para obtener los registros de la tabla
                $queryRes = DB::getResults('SELECT * FROM ' . $tableName, __FUNCTION__, false, true);

                while ($row = $queryRes->fetch_row()) {
                    fwrite($handle, 'INSERT INTO `' . $tableName . '` VALUES(');

                    $field = 1;
                    foreach ($row as $value) {
                        if (is_numeric($value)) {
                            fwrite($handle, $value);
                        } else {
                            fwrite($handle, '"' . DB::escape($value) . '"');
                        }

                        if ($field < $queryRes->field_count) {
                            fwrite($handle, ',');
                        }

                        $field++;
                    }
                    fwrite($handle, ');' . PHP_EOL);
                }
                fwrite($handle, PHP_EOL . PHP_EOL);
            }

            $sqlOut = '--- sysPass DB dump generated on ' . time() . ' (end)' . PHP_EOL;
            $sqlOut .= '---' . PHP_EOL;
            $sqlOut .= '--- Please, do not alter this file, it could break your DB' . PHP_EOL;
            $sqlOut .= '---' . PHP_EOL . PHP_EOL;
            fwrite($handle, $sqlOut);

            fclose($handle);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Realizar un backup de la aplicación y comprimirlo.
     *
     * @param string $backupFile nombre del archivo de backup
     * @throws Exception
     * @return bool
     */
    private static function backupApp($backupFile)
    {
        if (!class_exists('PharData')) {
            if(SP_Util::runningOnWindows()){
                throw new Exception(_('Esta operación sólo es posible en entornos Linux'));
            }

            if(!self::backupAppLegacyLinux($backupFile)){
                throw new Exception(_('Error al realizar backup en modo compatibilidad'));
            }

            return true;
        }

        $compressedFile = $backupFile . '.gz';

        try {
            if (file_exists($compressedFile)){
                unlink($compressedFile);
            }

            $archive = new PharData($backupFile);
            $archive->buildFromDirectory(SP_Init::$SERVERROOT);
            $archive->compress(Phar::GZ);

            unlink($backupFile);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
        $backupDir = SP_Init::$SERVERROOT;
        $bakDstDir = $backupDir . '/backup';

        $command = 'tar czf ' . $compressedFile . ' ' . $backupDir . ' --exclude "' . $bakDstDir . '" 2>&1';
        exec($command, $resOut, $resBakApp);

        return $resBakApp;
    }

    /**
     * Comprobar y crear el directorio de backups.
     *
     * @param string $backupDir ruta del directorio de backup
     * @throws Exception
     * @return bool
     */
    private static function checkBackupDir($backupDir)
    {
        if (!is_dir($backupDir)) {
            if (!@mkdir($backupDir, 0550)) {
                throw new Exception(('No es posible crear el directorio de backups') . ' (' . $backupDir . ')');
            }
        }

        if (!is_writable($backupDir)) {
            throw new Exception(_('Compruebe los permisos del directorio de backups'));
        }

        return true;
    }
} 