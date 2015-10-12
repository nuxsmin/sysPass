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

namespace SP\Config;

use SP\Core\Language;
use SP\Core\SPException;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es responsable de leer y escribir la configuración del archivo config.php
 */
class Config implements ConfigInterface
{
    /**
     * @var array
     */
    protected static $_cache;
    /**
     * @var bool
     */
    protected static $_init;

    /**
     * Obtiene un valor de configuración desde el archivo
     *
     * @param string $param   clave
     * @param string $default = null valor por defecto
     * @return mixed el valor o $default
     */
    public static function getValue($param, $default = null)
    {
        $params = Cache::getSessionCacheConfigValue($param);

        return (!is_null($params)) ? $params : $default;
    }

    /**
     * Lista todas las claves de configuración guardadas
     *
     * @param bool $full obtener todas las claves y sus valores
     * @return array con nombres de claves
     */
    public static function getKeys($full = false)
    {
        self::readConfig();

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
    public static function readConfig()
    {
        if (self::$_init) {
            return true;
        }

        if (!file_exists(CONFIG_FILE)) {
            return false;
        }

        // Include the file, save the data from $CONFIG
        include_once CONFIG_FILE;

        if (isset($CONFIG) && is_array($CONFIG)) {
            self::$_cache = $CONFIG;
        }

        // We cached everything
        self::$_init = true;

        return true;
    }

    /**
     * Elimina una clave de la configuración.
     * Esta función elimina una clave de config.php. Si no tiene permiso
     * de escritura en config.php, devolverá false.
     *
     * @param string $param clave
     * @return bool
     */
    public static function deleteParam($param)
    {
        self::readConfig();

        if (isset(self::$_cache[$param])) {
            // Eliminar la clave de la caché
            unset(self::$_cache[$param]);

            // Guardar los cambios en la configuración
            self::writeConfig();
        }

        return true;
    }

    /**
     * Escribe en archivo de configuración.
     *
     * @return bool
     * @throws SPException
     */
    public static function writeConfig()
    {
        // Ordenar las claves de la configuración
        ksort(self::$_cache);

        $content = "<?php\n";
        $content .= "// Generated on " . time() . "\n";
        $content .= "// This file is generated automatically on installation process\n// Please, modify with caution, it could break the application\n";
        $content .= "\$CONFIG = ";
        $content .= trim(var_export(self::$_cache, true), ',');
        $content .= ";\n";

        // Escribir el archivo de configuración
        $result = @file_put_contents(CONFIG_FILE, $content);

        if (!$result) {
            throw new SPException(SPException::SP_CRITICAL, _('No es posible escribir el archivo de configuración'), _('Compruebe los permisos del directorio "config"'));
        }

        // Establecer los permisos del archivo de configuración
        chmod(CONFIG_FILE, 0640);

        // Actualizar la caché de configuración de la sesión
        Cache::setSessionCacheConfig();

        return true;
    }

    /**
     * Establece los valores de configuración por defecto en config.php
     */
    public static function setDefaultValues()
    {
        self::setCacheConfigValue('debug', false);
        self::setCacheConfigValue('log_enabled', true);
        self::setCacheConfigValue('ldap_enabled', false);
        self::setCacheConfigValue('mail_enabled', false);
        self::setCacheConfigValue('wiki_enabled', false);
        self::setCacheConfigValue('demo_enabled', false);
        self::setCacheConfigValue('files_enabled', true);
        self::setCacheConfigValue('proxy_enabled', false);
        self::setCacheConfigValue('checkupdates', true);
        self::setCacheConfigValue('checknotices', true);
        self::setCacheConfigValue('globalsearch', false);
        self::setCacheConfigValue('account_passtoimage', false);
        self::setCacheConfigValue('resultsascards', false);
        self::setCacheConfigValue('files_allowed_exts', 'PDF,JPG,GIF,PNG,ODT,ODS,DOC,DOCX,XLS,XSL,VSD,TXT,CSV,BAK');
        self::setCacheConfigValue('files_allowed_size', 1024);
        self::setCacheConfigValue('wiki_searchurl', '');
        self::setCacheConfigValue('wiki_pageurl', '');
        self::setCacheConfigValue('wiki_filter', '');
        self::setCacheConfigValue('ldap_server', '');
        self::setCacheConfigValue('ldap_base', '');
        self::setCacheConfigValue('ldap_group', '');
        self::setCacheConfigValue('ldap_userattr', '');
        self::setCacheConfigValue('mail_server', '');
        self::setCacheConfigValue('mail_from', '');
        self::setCacheConfigValue('site_lang', str_replace('.utf8', '', Language::$globalLang));
        self::setCacheConfigValue('session_timeout', '300');
        self::setCacheConfigValue('account_link', 1);
        self::setCacheConfigValue('account_count', 12);
        self::setCacheConfigValue('sitetheme', 'material-blue');
        self::setCacheConfigValue('proxy_server', '');
        self::setCacheConfigValue('proxy_port', '');
        self::setCacheConfigValue('proxy_user', '');
        self::setCacheConfigValue('proxy_pass', '');

        self::writeConfig();
    }

    /**
     * Actualizar el array de parámetros de configuración
     *
     * @param $param   string El parámetro a actualizar
     * @param $value   mixed  El valor a actualizar
     */
    public static function setCacheConfigValue($param, $value)
    {
        // Comprobar que la configuración está cargada
        if (count(self::$_cache) === 0){
            self::readConfig();
        }

        self::$_cache[$param] = $value;
    }

    /**
     * Establece un valor en el archivo de configuración.
     * Esta función establece el valor y reescribe config.php. Si el archivo
     * no se puede escribir, devolverá false.
     *
     * @param string $param clave
     * @param string $value valor
     * @return bool
     */
    public static function setValue($param, $value)
    {
        self::readConfig();

        // Añadir/Modificar el parámetro
        self::$_cache[$param] = $value;
        // Generar el hash de la configuración
        self::$_cache['config_hash'] = md5(implode(self::$_cache));

        // Guardar los cambios
        self::writeConfig();

        return true;
    }

    /**
     * Obtener la configuración de sysPass
     *
     * @return array|bool
     */
    public static function getConfig()
    {
        if (self::readConfig()) {
            return self::$_cache;
        }

        return false;
    }

    /**
     * Obtener un parámetro del array de parámetros de configuración
     *
     * @param $param   string El parámetro a obtener
     */
    public static function getCacheConfigValue($param)
    {
        return self::$_cache[$param];
    }
}
