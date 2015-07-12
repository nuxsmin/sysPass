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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es responsable de leer y escribir la configuración del archivo config.php
 * y en la base de datos
 */
class Config
{
    /**
     * @var array
     */
    private static $_config;
    /**
     * @var array
     */
    private static $_cache = array();
    /**
     * @var bool
     */
    private static $_init = false;

    /**
     * @param null $key La clave a obtener
     * @return mixed
     */
    public static function getArrConfigValue($key = null)
    {
        if (!is_null($key) && isset(self::$_config[$key])) {
            return self::$_config[$key];
        }

        return self::$_config;
    }

    /**
     * @param $key   string La clave a actualizar
     * @param $value mixed El valor a actualizar
     */
    public static function setArrConfigValue($key, $value)
    {
//        if (isset(self::$_config[$key])) {
        self::$_config[$key] = $value;
//        }
    }

    /**
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return bool
     */
    public static function getConfigDb()
    {
        $query = 'SELECT config_parameter, config_value FROM config';

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $config) {
            self::$_config[$config->config_parameter] = $config->config_value;
        }
    }

    /**
     * Guardar la configuración en la BBDD.
     *
     * @param bool $mkInsert realizar un 'insert'?
     * @return bool
     */
    public static function writeConfigDb($mkInsert = false)
    {
        foreach (self::$_config as $param => $value) {
            if ($mkInsert) {
                $query = 'INSERT INTO config VALUES (:param,:value) ON DUPLICATE KEY UPDATE config_value = :valuedup';

                $data['valuedup'] = $value;
            } else {
                $query = 'UPDATE config SET config_value = :value WHERE config_parameter = :param';
            }

            $data['param'] = $param;
            $data['value'] = $value;

            if (DB::getQuery($query, __FUNCTION__, $data) === false) {
                return false;
            }
        }

        Log::writeNewLogAndEmail(_('Configuración'), _('Modificar configuración'));

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

        $query = 'SELECT config_parameter, config_value FROM config';

        $queryRes = DB::getResults($query, __FUNCTION__);

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
     * @param string $key     clave
     * @param string $default = null valor por defecto
     * @return string el valor o $default
     */
    public static function getValue($key, $default = null)
    {
        $param = Cache::getSessionCacheConfigValue($key);

        return (!is_null($param)) ? $param : $default;
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
            return self::$_cache;
        }

        return array_keys(self::$_cache);
    }

    /**
     * Carga el archivo de configuración y lo guarda en caché.
     *
     * @return bool
     */
    private static function readData()
    {
        if (self::$_init) {
            return true;
        }

        $configFile = self::getConfigFile();;

        if (!file_exists($configFile)) {
            return false;
        }

        // Include the file, save the data from $CONFIG
        include_once $configFile;

        if (isset($CONFIG) && is_array($CONFIG)) {
            self::$_cache = $CONFIG;
        }

        // We cached everything
        self::$_init = true;

        return true;
    }

    /**
     * Devolver la ruta al archivo de configuración
     *
     * @return string Con la ruta
     */
    private static function getConfigFile()
    {
        return Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
    }

    /**
     * Elimina una clave de la configuración.
     * Esta función elimina una clave de configmgmt.php. Si no tiene permiso
     * de escritura en configmgmt.php, devolverá false.
     *
     * @param string $key clave
     * @return bool
     */
    public static function deleteKey($key)
    {
        self::readData();

        if (isset(self::$_cache[$key])) {
            // Eliminar la clave de la caché
            unset(self::$_cache[$key]);

            // Guardar los cambios en la configuración
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
        // Ordenar las claves de la configuración
        ksort(self::$_cache);

        $content = "<?php\n";
        $content .= "// Generated on " . time() . "\n";
        $content .= "// This file is generated automatically on installation process\n// Please, modify with caution, it could break the application\n";
        $content .= "\$CONFIG = ";
        $content .= trim(var_export(self::$_cache, true), ',');
        $content .= ";\n";

        $configFile = self::getConfigFile();

        // Escribir el archivo de configuración
        $result = @file_put_contents($configFile, $content);

        if (!$result) {
            Init::initError(_('No es posible escribir el archivo de configuración'), _('Compruebe los permisos del directorio "config"'));
        }

        // Establecer los permisos del archivo de configuración
        @chmod($configFile, 0640);

        // Actualizar la caché de configuración de la sesión
        Cache::setSessionCacheConfig();

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
        self::setValue('site_lang', str_replace('.utf8', '', Init::$LANG));
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

        // Añadir/Modificar el parámetro
        self::$_cache[$key] = $value;
        // Generar el hash de la configuración
        self::$_cache['config_hash'] = md5(implode(self::$_cache));

        // Guardar los cambios
        self::writeData();

        return true;
    }

    /**
     * Crea una clave temporal para encriptar la clave maestra y guardarla.
     *
     * @return bool|string
     */
    public static function setTempMasterPass($maxTime = 14400)
    {
        // Encriptar la clave maestra con hash aleatorio generado
        $randomHash = Util::generate_random_bytes(32);
        $pass = Crypt::mkCustomMPassEncrypt($randomHash, Crypt::getSessionMasterPass());

        if (!is_array($pass)) {
            return false;
        }

        self::setConfigDbValue('tempmaster_pass', bin2hex($pass[0]), false);
        self::setConfigDbValue('tempmaster_passiv', bin2hex($pass[1]), false);
        self::setConfigDbValue('tempmaster_passhash', sha1($randomHash), false);
        self::setConfigDbValue('tempmaster_passtime', time(), false);
        self::setConfigDbValue('tempmaster_maxtime', time() + $maxTime, false);
        self::setConfigDbValue('tempmaster_attempts', 0, false);

        return $randomHash;
    }

    /**
     * Guardar un parámetro de configuración en la BBDD.
     *
     * @param string $param con el parámetro a guardar
     * @param string $value con el calor a guardar
     * @param bool   $email enviar email?
     * @return bool
     */
    public static function setConfigDbValue($param, $value, $email = true)
    {
        $query = "INSERT INTO config "
            . "SET config_parameter = :param,"
            . "config_value = :value "
            . "ON DUPLICATE KEY UPDATE config_value = :valuedup";

        $data['param'] = $param;
        $data['value'] = $value;
        $data['valuedup'] = $value;

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $log = new Log(_('Configuración'));
        $log->addDescription(_('Modificar configuración'));
        $log->addDescription(_('Parámetro') . ': ' . $param);
        $log->addDescription(_('Valor') . ': ' . $value);
        $log->writeLog();

        if ($email === true) {
            Email::sendEmail($log);
        }

        return true;
    }

    /**
     * Comprueba si la clave temporal es válida
     *
     * @param string $pass clave a comprobar
     * @return bool
     */
    public static function checkTempMasterPass($pass)
    {
        $passTime = self::getConfigDbValue('tempmaster_passtime');
        $passMaxTime = self::getConfigDbValue('tempmaster_maxtime');
        $attempts = self::getConfigDbValue('tempmaster_attempts');

        // Comprobar si el tiempo de validez se ha superado
        if ($passTime !== false && time() - $passTime > $passMaxTime || $attempts >= 5) {
            self::setConfigDbValue('tempmaster_pass', '', false);
            self::setConfigDbValue('tempmaster_passiv', '', false);
            self::setConfigDbValue('tempmaster_passhash', '', false);

            return false;
        }

        $isValid = (self::getConfigDbValue('tempmaster_passhash') == sha1($pass));

        if (!$isValid) {
            self::setConfigDbValue('tempmaster_attempts', $attempts + 1, false);
        }

        return $isValid;
    }

    /**
     * Obtiene un valor desde la configuración en la BBDD.
     *
     * @param string $param con el parámetro de configuración
     * @return false|string con el valor
     */
    public static function getConfigDbValue($param)
    {
        $query = 'SELECT config_value FROM config WHERE config_parameter = :parameter LIMIT 1';

        $data['parameter'] = $param;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->config_value;
    }

    /**
     * Devuelve la clave maestra que ha sido encriptada con la clave temporal
     *
     * @param $pass string con la clave utilizada para encriptar
     * @return string con la clave maestra desencriptada
     */
    public static function getTempMasterPass($pass)
    {
        $passLogin = hex2bin(self::getConfigDbValue('tempmaster_pass'));
        $passLoginIV = hex2bin(self::getConfigDbValue('tempmaster_passiv'));

        return Crypt::getDecrypt($passLogin, $pass, $passLoginIV);
    }

    /**
     * Obtener la configuración de sysPass
     *
     * @return array|bool
     */
    public static function getConfig()
    {
        if (self::readData()) {
            return self::$_cache;
        }

        return false;
    }
}
