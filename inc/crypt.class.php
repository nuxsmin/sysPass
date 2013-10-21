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
 * Esta clase es la encargada de realizar el encriptad/desencriptado de claves
 */
class SP_Crypt {

    public $pwdCrypt;
    public $strInitialVector;

    /**
     * @brief Crear el vector de inicialización
     * @return string con el IV
     */ 
    private static function createIV() {
        $resEncDes = mcrypt_module_open('rijndael-256', '', 'cbc', '');
        $strInitialVector = mcrypt_create_iv(mcrypt_enc_get_iv_size($resEncDes), MCRYPT_DEV_URANDOM);
        mcrypt_module_close($resEncDes);

        return $strInitialVector;
    }

    /**
     * @brief Comprobar si el vector de inicialización tiene la longitud correcta
     * @param string $strInitialVector con el IV
     * @return bool
     */ 
    private static function checkIV($strInitialVector){
        $strEscapeInitialVector = DB::escape($strInitialVector);

        if (strlen($strEscapeInitialVector) != 32 ) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * @brief Encriptar datos con la clave maestra
     * @param string $strValue con los datos a encriptar
     * @param string $strPassword con la clave maestra
     * @param string $strInitialVector con el IV
     * @return string con los datos encriptados
     */ 
    private function encrypt($strValue, $strPassword, $strInitialVector){
        $resEncDes = mcrypt_module_open('rijndael-256', '', 'cbc', '');
        mcrypt_generic_init($resEncDes, $strPassword, $strInitialVector);
        $strEncrypted = mcrypt_generic($resEncDes, $strValue);
        mcrypt_generic_deinit($resEncDes);

        return $strEncrypted;
    }

    /**
     * @brief Desencriptar datos con la clave maestra
     * @param string $strEncrypted con los datos a desencriptar
     * @param string $strPassword con la clave maestra
     * @param string $strInitialVector con el IV
     * @return string con los datos desencriptados
     */ 
    public function decrypt($strEncrypted, $strPassword, $strInitialVector){
        $resEncDes = mcrypt_module_open('rijndael-256', '', 'cbc', '');
        mcrypt_generic_init($resEncDes, $strPassword, $strInitialVector);
        $strDecrypted = trim(mdecrypt_generic($resEncDes, $strEncrypted));
        
        mcrypt_generic_deinit($resEncDes);
        mcrypt_module_close($resEncDes);

        return $strDecrypted;
    }

    /**
     * @brief Comprobar si el módulo de encriptación está disponible
     * @param string $strEncrypted con los datos a desencriptar
     * @param string $strPassword con la clave maestra
     * @param string $strInitialVector con el IV
     * @return string con los datos desencriptados
     */ 
    public static function checkCryptModule(){
        $resEncDes = mcrypt_module_open('rijndael-256', '', 'cbc', '');

        if ($resEncDes == FALSE )  {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * @brief Comprobar datos encriptados
     * @param string $strEncryptedPass con los datos encriptados
     * @return bool
     * 
     * Esta función comprueba la longitud de los datos encriptados despues de 
     * escaparlos con mysqli
     */ 
    private function checkEncryptedPass($strEncryptedPass){
        $strEscapedEncryptedPass = DB::escape($strEncryptedPass);
        
        if (strlen($strEscapedEncryptedPass) != strlen($strEncryptedPass) ) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * @brief Generar una clave encriptada
     * @param string $pwd con la clave a encriptar
     * @param string $masterPwd con la clave maestra
     * @return bool
     * 
     * Esta función llama a los métodos privados para encriptar datos.
     */ 
    public function mkPassEncrypt($pwd,$masterPwd = ""){
        $masterPwd = ( ! $masterPwd )  ? $_SESSION["mPass"] : $masterPwd;
        
        do {
            do {
                $strInitialVector = SP_Crypt::createIV();
                $blnCheckIv = SP_Crypt::checkIV($strInitialVector);
            } while ($blnCheckIv == FALSE);
            
            $pwdCrypt = $this->encrypt($pwd, $masterPwd, $strInitialVector);
            $blnCheckEncrypted = $this->checkEncryptedPass($pwdCrypt);
        } while ($blnCheckEncrypted == FALSE );
        
        $this->pwdCrypt = $pwdCrypt;
        $this->strInitialVector = $strInitialVector;
        return TRUE;
    }
    
    /**
     * @brief Generar la clave maestra encriptada con la clave del usuario
     * @param string $customPwd con la clave a encriptar
     * @param string $masterPwd con la clave maestra
     * @return string con la clave encriptada
     * 
     * Esta función llama a los métodos privados para encriptar datos.
     */ 
    public function mkCustomMPassEncrypt($customPwd,$masterPwd){       
        do {
            do {
                $strInitialVector = SP_Crypt::createIV();
                $blnCheckIv = SP_Crypt::CheckIV($strInitialVector);
            } while ($blnCheckIv == FALSE);
            
            $pwdCrypt = $this->encrypt($masterPwd, $customPwd, $strInitialVector);
            $blnCheckEncrypted = $this->checkEncryptedPass($pwdCrypt);
        } while ($blnCheckEncrypted == FALSE );
        
        $dataCrypt = array($pwdCrypt, $strInitialVector);
        
        return $dataCrypt;
    }

    /**
     * @brief Generar un hash de una clave utilizando un salt
     * @param string $pwd con la clave a 'hashear'
     * @return string con el hash de la clave
     */ 
    public static function mkHashPassword($pwd){
        $salt = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM)); // Obtenemos 256 bits aleatorios en hexadecimal
        $hash = hash("sha256", $salt.$pwd); // Añadimos el salt a la clave y rehacemos el hash
        $hashPwd = $salt.$hash;
        return $hashPwd;
    }

    /**
     * @brief Comprobar el hash de una clave
     * @param string $pwd con la clave a comprobar
     * @param string $correctHash con el hash a comprobar
     * @return bool
     */ 
    public static function checkHashPass($pwd, $correctHash){
        // Obtenemos el salt de la clave
        $salt = substr($correctHash, 0, 64); 
        // Obtenemos el hash SHA256
        $validHash = substr($correctHash, 64, 64);

        // Re-hash de la clave a comprobar
        $testHash = hash("sha256", $salt . $pwd); 

        // Si los hashes son idénticos, la clave es válida
        if ( $testHash === $validHash ){
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * @brief Crear un salt
     * @return string con el salt creado
     */ 
    public static function makeHashSalt(){
        do {
            $strInitialVector = self::createIV();
            $blnCheckIv = self::checkIV($strInitialVector);
        } while ($blnCheckIv == FALSE);
        
        return $strInitialVector;
    }
}