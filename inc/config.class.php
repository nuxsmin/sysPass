<?php
/** 
* sysPass
* 
* @author nuxsmin
* @link http://syspass.org
* @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
class SP_Config{
    // Array asociativo clave => valor
    private static $cache = array();
    // La caché está llena??
    private static $init = false;
    // Configuracion actual en array
    static $arrConfigValue;

    /**
     * @brief Obtiene un valor desde la configuración en la BBDD
     * @param string $param con el parámetro de configuración
     * @return string con el valor
     *
     * Obtener el valor de un parámetro almacenado en la BBDD
     */
    public static function getConfigValue($param){
        $query = "SELECT config_value FROM config WHERE config_parameter = '$param'";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ){
            return FALSE;
        }
        
        return $queryRes[0]->config_value;
    }
    
    /**
     * @brief Obtener array con la configuración
     *
     * Obtener un array con la configuración almacenada en la BBDD
     */
    public static function getConfig(){
        $query = "SELECT config_parameter, config_value FROM config";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ){
            return FALSE;
        }
        
        foreach ( $queryRes as $config ){
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
    public static function writeConfig($mkInsert = FALSE){
        foreach (self::$arrConfigValue as $key => $value) {
            $key = DB::escape($key);
            $value = DB::escape($value);
            
            if ( $mkInsert ){
                $query = "INSERT INTO config "
                        . "VALUES ('$key','$value') "
                        . "ON DUPLICATE KEY UPDATE config_value = '$value' ";
            } else {
                $query = "UPDATE config SET "
                        . "config_value = '$value' "
                        . "WHERE config_parameter = '$key'";
            }
            
            if ( DB::doQuery($query, __FUNCTION__) === FALSE ){
                return FALSE;
            }
        }
        
        $message['action'] = _('Configuración');
        $message['text'][] = _('Modificar configuración');

        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);
        
        return TRUE;
    }   

    /**
     * @brief Guardar un parámetro de configuración
     * @param string $param con el parámetro a guardar
     * @param string $value con el calor a guardar
     * @return bool
     */
    public static function setConfigValue($param, $value) {
        $query = "INSERT INTO config "
                . "SET config_parameter = '" . DB::escape($param) . "',"
                . "config_value = '" . DB::escape($value) . "'"
                . "ON DUPLICATE KEY UPDATE config_value = '" . DB::escape($value) . "' ";

        if (DB::doQuery($query, __FUNCTION__) === FALSE) {
            return FALSE;
        }

        $message['action'] = _('Configuración');
        $message['text'][] = _('Modificar configuración');
        $message['text'][] = _('Parámetro') . ': ' . $param;
        $message['text'][] = _('Valor') . ': ' . $value;

        SP_Common::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return TRUE;
    }

    /**
     * @brief Cargar la configuración desde la BBDD
     * @param bool $force reescribir la variable global $CFG?
     * @return bool
     *
     * Cargar la configuración desde la BBDD y guardarla en una variable global $CFG
     */
    public static function getDBConfig($force = FALSE){
        global $CFG;

        if ( isset ($CFG) && ! $force ) return TRUE;

        $query = "SELECT config_parameter, config_value FROM config";
        $queryRes = DB::getResults($query, __FUNCTION__);

        if ( $queryRes === FALSE || ! is_array($queryRes) ){
            return FALSE;
        }
        
        foreach ( $queryRes as $config ){
            $cfgParam = $config->config_parameter;
            $cfgValue = $config->config_value;
            
            if ( strstr($cfgValue, "||") ){
                $cfgValue = explode ("||",$cfgValue);
            }

            $CFG["$cfgParam"] = $cfgValue;
        }
        
        return TRUE;
    }     
      
    /**
     * @brief Realizar backup de la BBDD y aplicación
     * @return array resultado
     *
     * Realizar un backup completo de la BBDD y de la aplicación.
     * Sólo es posible en entornos Linux
     */
    public static function makeBackup(){
        
        if ( SP_Util::runningOnWindows() ){
            $arrOut['error'] = _('Esta operación sólo es posible en entornos Linux');
            return $arrOut;
        }
        
        $arrOut = array();
        $error = 0;
        $siteName = SP_Html::getAppInfo('appname');
        $backupDir = SP_Init::$SERVERROOT;
        
        $bakDstDir = $backupDir.'/backup';
        $bakFile = $backupDir.'/backup/'.$siteName.'.tgz';
        $bakFileDB = $backupDir.'/backup/'.$siteName.'_db.sql';

        if ( ! is_dir($bakDstDir) ){
            if ( ! @mkdir($bakDstDir, 0550) ){
                $arrOut['error'] = '<span class="altTxtError">'._('No es posible crear el directorio de backups').' ('.$bakDstDir.')</span>';
                
                $message['action'] = _('Copia BBDD');
                $message['text'][] = _('No es posible crear el directorio de backups');
                $message['text'][] = "IP:".$_SERVER['REMOTE_ADDR'];
                
                SP_Common::wrLogInfo($message);
                $error = 1;
            }
        }

        if ( ! is_writable($bakDstDir) ){
            $arrOut['error'] = '<span class="altTxtError">'._('Compruebe los permisos del directorio de backups').'</span>';
            $error = 1;
        }

        if ( $error == 0 ){
            $message['action'] = _('Copia BBDD');
            $message['text'][] = "IP:".$_SERVER['REMOTE_ADDR'];

            SP_Common::wrLogInfo($message);
            SP_Common::sendEmail($message);
            
            $dbhost = SP_Config::getValue("dbhost");
            $dbuser = SP_Config::getValue("dbuser");
            $dbpass = SP_Config::getValue("dbpass");
            $dbname = SP_Config::getValue("dbname");
            
            // Backup de la BBDD
            $command = 'mysqldump -h '.$dbhost.' -u '.$dbuser.' -p'.$dbpass.' -r "'.$bakFileDB.'" '.$dbname.' 2>&1'; 
            exec($command, $resOut, $resBakDB);
            
            // Backup de la Aplicación
            $command = 'tar czf '.$bakFile.' '.$backupDir.' --exclude "'.$bakDstDir.'" 2>&1';
            exec($command, $resOut, $resBakApp);
            
            if ( $resBakApp != 0 || $resBakDB != 0 ){
                $arrOut['error'] = implode('<br>', $resOut);
            }
        }
        
        return $arrOut;
    }
     
    /**
     * @brief Lista todas las claves de configuración
     * @return array con nombres de claves
     *
     * Esta función devuelve todas las claves guardadas en config.php.
     */
    public static function getKeys($full = FALSE){
        self::readData();

        if ( $full ){
            return self::$cache;
        }
        
        return array_keys( self::$cache );
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
    public static function getValue( $key, $default = null ) {
        self::readData();

        if( array_key_exists( $key, self::$cache )) return self::$cache[$key];

        return $default;
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
    public static function setValue( $key, $value ) {
        self::readData();

        // Add change
        self::$cache[$key] = $value;

        // Write changes
        self::writeData();
        return true;
    }

    /**
     * @brief Elimina una clave de la configuración
     * @param string $key clave
     * @return bool
     *
     * Esta función elimina una clave de config.php. Si no tiene permiso
     * de escritura en config.php, devolverá false.
     */
    public static function deleteKey( $key ) {
        self::readData();

        if( array_key_exists( $key, self::$cache )) {
            // Delete key from cache
            unset( self::$cache[$key] );

            // Write changes
            self::writeData();
        }

        return true;
    }

    /**
     * @brief Carga el archivo de configuración
     * @return bool
     *
     * Lee el archivo de configuración y lo guarda en caché
     */
    private static function readData() {
        if( self::$init ) {
            return true;
        }

        if( !file_exists( SP_Init::$SERVERROOT."/config/config.php" )) return false;

        // Include the file, save the data from $CONFIG
        include SP_Init::$SERVERROOT."/config/config.php";
        if( isset($CONFIG) && is_array($CONFIG) ) self::$cache = $CONFIG;

        // We cached everything
        self::$init = true;

        return true;
    }

    /**
     * @brief Escribe en archivo de configuración
     * @return bool
     */
    public static function writeData() {	
        $content = "<?php\n\$CONFIG = ";
        $content .= trim(var_export(self::$cache, true),',');
        $content .= ";\n";

        $filename = SP_Init::$SERVERROOT."/config/config.php";
        
        // Write the file
        $result=@file_put_contents( $filename, $content );
        
        if( ! $result ) {
            $errors[] = array(
                            'type' => 'critical',
                            'description' => _('No es posible escribir el archivo de configuración'),
                            'hint' => 'Compruebe los permisos del directorio "config"');

            SP_Html::render('error',$errors);
            exit();
        }
        
        // Prevent others not to read the config
        @chmod($filename, 0640);
				
        return TRUE;
    }
    
    /**
     * @brief Establece los valores de configuración por defecto en config.php
     * @return none
     */       
    public static function setDefaultValues(){
        self::setValue('logenabled', 1);
        self::setValue('debug', 0);
        self::setValue('ldapenabled', 0);
        self::setValue('mailenabled', 0);
        self::setValue('wikienabled', 0);
        self::setValue('demoenabled', 0);
        
        self::setValue('allowed_exts', 'PDF,JPG,GIF,PNG,ODT,ODS,DOC,DOCX,XLS,XSL,VSD,TXT,CSV,BAK');
        self::setValue('allowed_size', 1024);
        self::setValue('wikisearchurl', '');
        self::setValue('wikipageurl', '');
        self::setValue('wikifilter', '');
        self::setValue('ldapserver', '');
        self::setValue('ldapbase', '');
        self::setValue('ldapgroup', '');
        self::setValue('ldapuserattr', '');
        self::setValue('mailserver', '');
        self::setValue('mailfrom', '');
        self::setValue('wikifilter', '');
        self::setValue('sitelang', 'es_ES');
        self::setValue('session_timeout', '300');
        self::setValue('account_link', 1);
        self::setValue('account_count', 10);
    }
}
