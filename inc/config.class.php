<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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

/*
 * <?php
 * $CONFIG = array(
 *     "database" => "mysql",
 *     "firstrun" => false,
 *     "pi" => 3.14
 * );
 * ?>
 *
 */

/**
 * Esta clase es responsable de leer y escribir la configuración del archivo config.php
 * y en la base de datos
 */
class SP_Config
{
    // Array asociativo clave => valor
    static $arrConfigValue;

    private static $cache = array(); // Configuracion actual en array
    private static $init = false; // La caché está llena??

    /**
     * @brief Obtiene un valor desde la configuración en la BBDD
     * @param string $param con el parámetro de configuración
     * @return string con el valor
     *
     * Obtener el valor de un parámetro almacenado en la BBDD
     */
    public static function getConfigValue($param)
    {
        $query = "SELECT config_value "
            . "FROM config "
            . "WHERE config_parameter = '$param'";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->config_value;
    }

    /**
     * @brief Obtener array con la configuración
     *
     * Obtener un array con la configuración almacenada en la BBDD
     */
    public static function getConfig()
    {
        $query = "SELECT config_parameter,"
            . "config_value "
            . "FROM config";
        $queryRes = DB::getResults($query, __FUNCTION__, true);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $config) {
            $strKey = $config->config_parameter;
            $strValue = $config->config_value;
            self::$arrConfigValue[$strKey] = $strValue;

        }
    }

    /**
     * @brief Guardar la configuración
     * @param bool $mkInsert realizar un 'insert'?
     * @return bool
     *
     * Guardar la configuración en la BBDD
     */
    public static function writeConfig($mkInsert = false)
    {
        foreach (self::$arrConfigValue as $key => $value) {
            $key = DB::escape($key);
            $value = DB::escape($value);

            if ($mkInsert) {
                $query = "INSERT INTO config "
                    . "VALUES ('$key','$value') "
                    . "ON DUPLICATE KEY UPDATE config_value = '$value' ";
            } else {
                $query = "UPDATE config SET "
                    . "config_value = '$value' "
                    . "WHERE config_parameter = '$key'";
            }

            if (DB::doQuery($query, __FUNCTION__) === false) {
                return false;
            }
        }

        $message['action'] = _('Configuración');
        $message['text'][] = _('Modificar configuración');

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * @brief Guardar un parámetro de configuración
     * @param string $param con el parámetro a guardar
     * @param string $value con el calor a guardar
     * @return bool
     */
    public static function setConfigValue($param, $value)
    {
        $query = "INSERT INTO config "
            . "SET config_parameter = '" . DB::escape($param) . "',"
            . "config_value = '" . DB::escape($value) . "'"
            . "ON DUPLICATE KEY UPDATE config_value = '" . DB::escape($value) . "' ";

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }

        $message['action'] = _('Configuración');
        $message['text'][] = _('Modificar configuración');
        $message['text'][] = _('Parámetro') . ': ' . $param;
        $message['text'][] = _('Valor') . ': ' . $value;

        SP_Log::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * @brief Cargar la configuración desde la BBDD
     * @param bool $force reescribir la variable global $CFG?
     * @return bool
     *
     * Cargar la configuración desde la BBDD y guardarla en una variable global $CFG
     */
    public static function getDBConfig($force = false)
    {
        global $CFG;

        if (isset ($CFG) && !$force) {
            return true;
        }

        $query = "SELECT config_parameter,"
            . "config_value "
            . "FROM config";
        $queryRes = DB::getResults($query, __FUNCTION__, true);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $config) {
            $cfgParam = $config->config_parameter;
            $cfgValue = $config->config_value;

            if (strstr($cfgValue, "||")) {
                $cfgValue = explode("||", $cfgValue);
            }

            $CFG["$cfgParam"] = $cfgValue;
        }

        return true;
    }

    /**
     * @brief Realizar backup de la BBDD y aplicación
     * @return array resultado
     *
     * Realizar un backup completo de la BBDD y de la aplicación.
     * Sólo es posible en entornos Linux
     */
    public static function makeBackup()
    {

        if (SP_Util::runningOnWindows()) {
            $arrOut['error'] = _('Esta operación sólo es posible en entornos Linux');
            return $arrOut;
        }

        $arrOut = array();
        $error = 0;
        $siteName = SP_Html::getAppInfo('appname');
        $backupDir = SP_Init::$SERVERROOT;

        $bakDstDir = $backupDir . '/backup';
        $bakFile = $backupDir . '/backup/' . $siteName . '.tgz';
        $bakFileDB = $backupDir . '/backup/' . $siteName . '_db.sql';

        if (!is_dir($bakDstDir)) {
            if (!@mkdir($bakDstDir, 0550)) {
                $arrOut['error'] = _('No es posible crear el directorio de backups') . ' (' . $bakDstDir . ')';

                $message['action'] = _('Copia BBDD');
                $message['text'][] = _('No es posible crear el directorio de backups');

                SP_Log::wrLogInfo($message);
                $error = 1;
            }
        }

        if (!is_writable($bakDstDir)) {
            $arrOut['error'] = _('Compruebe los permisos del directorio de backups');
            $error = 1;
        }

        if ($error == 0) {
            $message['action'] = _('Copia BBDD');

            SP_Log::wrLogInfo($message);
            SP_Common::sendEmail($message);

            $dbhost = SP_Config::getValue("dbhost");
            $dbuser = SP_Config::getValue("dbuser");
            $dbpass = SP_Config::getValue("dbpass");
            $dbname = SP_Config::getValue("dbname");

            // Backup de la BBDD
            $command = 'mysqldump -h ' . $dbhost . ' -u ' . $dbuser . ' -p' . $dbpass . ' -r "' . $bakFileDB . '" ' . $dbname . ' 2>&1';
            exec($command, $resOut, $resBakDB);

            // Backup de la Aplicación
            $command = 'tar czf ' . $bakFile . ' ' . $backupDir . ' --exclude "' . $bakDstDir . '" 2>&1';
            exec($command, $resOut, $resBakApp);

            if ($resBakApp != 0 || $resBakDB != 0) {
                $arrOut['error'] = implode('<br>', $resOut);
            }
        }

        return $arrOut;
    }

    /**
     * @brief Obtiene un valor desde config.php
     * @param string $key clave
     * @param string $default = null valor por defecto
     * @return string el valor o $default
     *
     * Esta función obtiene un valor desde config.php. Si no existe,
     * $default será defuelto.
     */
    public static function getValue($key, $default = null)
    {
        self::readData();

        if (array_key_exists($key, self::$cache)) return self::$cache[$key];

        return $default;
    }

    /**
     * @brief Carga el archivo de configuración
     * @return bool
     *
     * Lee el archivo de configuración y lo guarda en caché
     */
    private static function readData()
    {
        if (self::$init) {
            return true;
        }

        $configFile = SP_Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'config'. DIRECTORY_SEPARATOR . 'config.php';

        if (!file_exists($configFile)) {
            return false;
        }

        // Include the file, save the data from $CONFIG
        include $configFile;

        if (isset($CONFIG) && is_array($CONFIG)) {
            self::$cache = $CONFIG;
        }

        // We cached everything
        self::$init = true;

        return true;
    }

    /**
     * @brief Lista todas las claves de configuración
     * @param bool $full obtener todas las claves y sus valores
     * @return array con nombres de claves
     *
     * Esta función devuelve todas las claves guardadas en config.php.
     */
    public static function getKeys($full = false)
    {
        self::readData();

        if ($full) {
            return self::$cache;
        }

        return array_keys(self::$cache);
    }

    /**
     * @brief Elimina una clave de la configuración
     * @param string $key clave
     * @return bool
     *
     * Esta función elimina una clave de config.php. Si no tiene permiso
     * de escritura en config.php, devolverá false.
     */
    public static function deleteKey($key)
    {
        self::readData();

        if (array_key_exists($key, self::$cache)) {
            // Delete key from cache
            unset(self::$cache[$key]);

            // Write changes
            self::writeData();
        }

        return true;
    }

    /**
     * @brief Escribe en archivo de configuración
     * @return bool
     */
    public static function writeData()
    {
        ksort(self::$cache);

        $content = "<?php\n";
        $content .= "// Generated on " . time() . "\n";
        $content .= "// This file is generated automatically on installation process\n// Please, modify with caution, it could break the application\n";
        $content .= "\$CONFIG = ";
        $content .= trim(var_export(self::$cache, true), ',');
        $content .= ";\n";

        $filename = SP_Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

        // Write the file
        $result = @file_put_contents($filename, $content);

        if (!$result) {
            $errors[] = array(
                'type' => 'critical',
                'description' => _('No es posible escribir el archivo de configuración'),
                'hint' => _('Compruebe los permisos del directorio "config"'));

            SP_Html::render('error', $errors);
            exit();
        }

        // Prevent others not to read the config
        @chmod($filename, 0640);

        return true;
    }

    /**
     * @brief Establece los valores de configuración por defecto en config.php
     * @return none
     */
    public static function setDefaultValues()
    {
        self::setValue('log_enabled', 1);
        self::setValue('debug', 0);
        self::setValue('ldap_enabled', 0);
        self::setValue('mail_enabled', 0);
        self::setValue('wiki_enabled', 0);
        self::setValue('demo_enabled', 0);
        self::setValue('files_enabled', 1);
        self::setValue('checkupdates', 1);

        self::setValue('files_allowed_exts', 'PDF,JPG,GIF,PNG,ODT,ODS,DOC,DOCX,XLS,XSL,VSD,TXT,CSV,BAK');
        self::setValue('files_allowed_size', 1024);
        self::setValue('wiki_searchurl', '');
        self::setValue('wiki_pageurl', '');
        self::setValue('wiki_filter', '');
        self::setValue('ldap_server', '');
        self::setValue('ldap_base', '');
        self::setValue('ldap_group', '');
        self::setValue('ldap_userattr', '');
        self::setValue('mail_server', '');
        self::setValue('mail_from', '');
        self::setValue('wiki_filter', '');
        self::setValue('site_lang', str_replace('.utf8','',SP_Init::$LANG));
        self::setValue('session_timeout', '300');
        self::setValue('account_link', 1);
        self::setValue('account_count', 12);
    }

    /**
     * @brief Establece un valor
     * @param string $key clave
     * @param string $value valor
     * @return bool
     *
     * Esta función establece el valor y reescribe config.php. Si el archivo
     * no se puede escribir, devolverá false.
     */
    public static function setValue($key, $value)
    {
        self::readData();

        // Add change
        self::$cache[$key] = $value;

        // Write changes
        self::writeData();
        return true;
    }
}
