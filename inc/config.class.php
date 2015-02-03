<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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
     * Obtiene un valor desde la configuración en la BBDD.
     *
     * @param string $param con el parámetro de configuración
     * @return false|string con el valor
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
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return bool
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
     * Guardar la configuración en la BBDD.
     *
     * @param bool $mkInsert realizar un 'insert'?
     * @return bool
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
     * Guardar un parámetro de configuración en la BBDD.
     *
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
     * Cargar la configuración desde la BBDD a variable global $CFG.
     *
     * @param bool $force reescribir la variable global $CFG?
     * @return bool
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
     * Obtiene un valor de configuración desde el archivo config.php
     *
     * @param string $key clave
     * @param string $default = null valor por defecto
     * @return string el valor o $default
     */
    public static function getValue($key, $default = null)
    {
        self::readData();

        if (array_key_exists($key, self::$cache)) return self::$cache[$key];

        return $default;
    }

    /**
     * Carga el archivo de configuración y lo guarda en caché.
     *
     * @return bool
     */
    private static function readData()
    {
        if (self::$init) {
            return true;
        }

        $configFile = SP_Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

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
     * Lista todas las claves de configuración guardadas en config.php.
     *
     * @param bool $full obtener todas las claves y sus valores
     * @return array con nombres de claves
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
     * Elimina una clave de la configuración.
     * Esta función elimina una clave de config.php. Si no tiene permiso
     * de escritura en config.php, devolverá false.
     *
     * @param string $key clave
     * @return bool
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
     * Escribe en archivo de configuración.
     *
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
     * Establece los valores de configuración por defecto en config.php
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
        self::setValue('site_lang', str_replace('.utf8', '', SP_Init::$LANG));
        self::setValue('session_timeout', '300');
        self::setValue('account_link', 1);
        self::setValue('account_count', 12);
    }

    /**
     * Establece un valor en el archivo de configuración.
     * Esta función establece el valor y reescribe config.php. Si el archivo
     * no se puede escribir, devolverá false.
     *
     * @param string $key   clave
     * @param string $value valor
     * @return bool
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
