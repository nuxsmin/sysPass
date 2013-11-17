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

/**
 * Clase con utilizades para la aplicación
 */
class SP_Util {   
    /**
     * @brief Comprobar si la función de números aleatorios está disponible
     * @return bool
     */
    public static function secureRNG_available() {
        // Check openssl_random_pseudo_bytes
        if(function_exists('openssl_random_pseudo_bytes')) {
                openssl_random_pseudo_bytes(1, $strong);
                if($strong == true) {
                        return true;
                }
        }

        // Check /dev/urandom
        $fp = @file_get_contents('/dev/urandom', false, null, 0, 1);
        if ($fp !== false) {
                return true;
        }

        return false;
    }
    
    /**
     * @brief Comprobar si sysPass se ejecuta en W$indows
     * @return bool
     */
    public static function runningOnWindows() {
        return (substr(PHP_OS, 0, 3) === "WIN");
    }
    
    /**
     * @brief Generar una cadena aleatoria usuando criptografía
     * @param int $length opcional, con la longitud de la cadena
     * @return string
     */
    public static function generate_random_bytes($length = 30) {

        // Try to use openssl_random_pseudo_bytes
        if(function_exists('openssl_random_pseudo_bytes')) {
            $pseudo_byte = bin2hex(openssl_random_pseudo_bytes($length, $strong));
            if($strong == true) {
                return substr($pseudo_byte, 0, $length); // Truncate it to match the length
            }
        }

        // Try to use /dev/urandom
        $fp = @file_get_contents('/dev/urandom', false, null, 0, $length);
        if ($fp !== false) {
            $string = substr(bin2hex($fp), 0, $length);
            return $string;
        }

        // Fallback to mt_rand()
        $characters = '0123456789';
        $characters .= 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters)-1;
        $pseudo_byte = "";

        // Select some random characters
        for ($i = 0; $i < $length; $i++) {
            $pseudo_byte .= $characters[mt_rand(0, $charactersLength)];
        }
        return $pseudo_byte;
    }
    
    /**
     * @brief Comprobar la versión de PHP
     * @return bool
     */
    public static function checkPhpVersion(){
        preg_match("/(^\d\.\d)\..*/",PHP_VERSION, $version);

        if ( $version[1] >= 5.1 ){
            $this->printMsg(_('Versión PHP')." '".$version[0]."'");
            return TRUE;
        } else {
            $this->printMsg(_('Versión PHP')." '".$version[0]."'", 1);
            return FALSE;
        }    
    }

    /**
     * @brief Comprobar los módulos necesarios
     * @return array con los módulos no disponibles
     */
    public static function checkModules(){
        $modsAvail = get_loaded_extensions();
        $modsNeed = array("mysql","ldap","mcrypt","curl","SimpleXML");
        $modsErr = array();

        foreach($modsNeed as $module){
            if ( ! in_array($module,  $modsAvail) ){
                $error = array(
                        'type' => 'warning',
                        'description' => _('Módulo no disponible')." ($module)",
                        'hint' => _('Sin este módulo la aplicación puede no funcionar correctamente.')
                        );
                $modsErr[] = $error;
            }
        }
        
        return $modsErr;
    }
    
    /**
     * @brief Devuelve el valor de la variable enviada por un formulario
     * @param string $s con el nombre de la variable
     * @param string $d con el valor por defecto
     * @return string con el valor de la variable
     */
    public static function init_var($s, $d="") {
        $r = $d;
        if(isset($_REQUEST[$s]) && !empty($_REQUEST[$s])) {
            $r = SP_Html::sanitize($_REQUEST[$s]);
        }

        return $r;
    }
    
    /**
     * @brief Comprobar si el módulo de LDAP está instalado
     * @return bool
     */
    public static function ldapIsAvailable(){
        return in_array("ldap", get_loaded_extensions());
    }

    /**
     * @brief Comprobar si el módulo CURL está instalado
     * @return bool
     */
    public static function curlIsAvailable(){
        return ( function_exists(curl_init) );
    }
    
    /**
     * @brief Devuelve la versión de sysPass
     * @return array con el número de versión
     */
    public static function getVersion() {
        return array(1, 00, 05);
    }
    
    /**
     * @brief Devuelve la versión de sysPass
     * @return string con la versión
     */
    public static function getVersionString() {
        return '1.0-5';
    }
    
    /**
     * @brief Comprobar si hay actualizaciones de sysPass disponibles desde internet (sourceforge.net)
     * @return array|bool
     * 
     * Esta función comprueba el feed RSS de sourceforge.net y lo parsea para verificar si la aplicación está actualizada
     */    
    public static function checkUpdates(){
        //if ( ! self::curlIsAvailable() || ! SP_Config::getValue('checkupdates') ){
        if ( ! SP_Config::getValue('checkupdates') ){
            return FALSE;
        }
        
//        $ch = curl_init("http://sourceforge.net/api/file/index/project-id/775555/mtime/desc/limit/1/rss");
//        
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_HEADER, 0);
//        
//        if ( ! $data = curl_exec($ch) ) return FALSE;
//        
//        curl_close($ch);
        
        $feedUrl = 'https://sourceforge.net/api/file/index/project-id/1257402/mtime/desc/limit/20/rss';
        $feed =  file_get_contents($feedUrl);
        
        if ( $feed ){
            $xmlUpd = new SimpleXMLElement($feed, LIBXML_NOCDATA);
        } else{
            return FALSE;
        }

	if ( $xmlUpd->channel->item->title ){

            $pubVer = '';
            
            foreach ( $xmlUpd->channel->item as $item ){
                $url = (string)$item->link;
                $title = (string)$item->title;
                $description = (string)$item->description;

                if ( preg_match("/.*\/sysPass_(\d)\.(\d{1,})\.(\d{1,})(\-[a-z0-9]+)?\.(tar\.gz|zip)$/", $title, $pubVer) ){
                    break;
                }
            }
            
            
            
                    
            if ( is_array($pubVer) && SP_Init::isLoggedIn() ){
                $appVersion = implode('',self::getVersion());
                $pubVersion = $pubVer[1].$pubVer[2].$pubVer[3];
                
                if ( $pubVersion > $appVersion ){
                    $version = $pubVer[1].'.'.$pubVer[2].'.'.$pubVer[3];
                    return array('version' => $version,'url' => $url);
                } else {
                    return TRUE;
                }
            } else{
                return FALSE;
            }
        }
    }
    
    /**
     * @brief Comprobar el método utilizado para enviar un formulario
     * @return none
     */  
    public static function checkReferer($method){
        if ( $_SERVER['REQUEST_METHOD'] !== $method 
                || ! isset($_SERVER['HTTP_REFERER']) 
                || ! preg_match('#'.SP_Init::$WEBROOT.'/.*$#', $_SERVER['HTTP_REFERER'])){
            SP_Init::initError(_('No es posible acceder directamente a este archivo'));
            exit();
        }
    }

    /**
     * @brief Realiza el proceso de logout
     * @return none
     */ 
    public static function logout(){
        echo '<script>doLogout();</script>';
        exit();
    }
}