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
 * Esta clase es la encargada de realizar el encriptad/desencriptado de claves
 */
class Crypt
{
    public static $strInitialVector;

    /**
     * Comprobar si el módulo de encriptación está disponible.
     *
     * @return bool
     */
    public static function checkCryptModule()
    {
        return mcrypt_module_self_test(MCRYPT_RIJNDAEL_256);
    }

    /**
     * Generar un hash de una clave utilizando un salt.
     *
     * @param string $pwd con la clave a 'hashear'
     * @return string con el hash de la clave
     */
    public static function mkHashPassword($pwd)
    {
        $salt = bin2hex(self::getIV()); // Obtenemos 256 bits aleatorios en hexadecimal
        $hash = hash("sha256", $salt . $pwd); // Añadimos el salt a la clave y rehacemos el hash
        $hashPwd = $salt . $hash;
        return $hashPwd;
    }

    /**
     * Comprobar el hash de una clave.
     *
     * @param string $pwd         con la clave a comprobar
     * @param string $correctHash con el hash a comprobar
     * @return bool
     */
    public static function checkHashPass($pwd, $correctHash)
    {
        // Obtenemos el salt de la clave
        $salt = substr($correctHash, 0, 64);
        // Obtenemos el hash SHA256
        $validHash = substr($correctHash, 64, 64);

        // Re-hash de la clave a comprobar
        $testHash = hash("sha256", $salt . $pwd);

        // Si los hashes son idénticos, la clave es válida
        if ($testHash === $validHash) {
            return true;
        }

        return false;
    }

    /**
     * Crear un salt utilizando mcrypt.
     *
     * @return string con el salt creado
     */
    public static function makeHashSalt()
    {
        return self::getIV();
    }

    /**
     * Generar una clave encriptada.
     * Esta función llama a los métodos privados para encriptar datos.
     *
     * @param string $pwd       con la clave a encriptar
     * @param string $masterPwd con la clave maestra
     * @return bool
     */
    public static function mkEncrypt($pwd, $masterPwd = "")
    {
        $masterPwd = (!$masterPwd) ? self::getSessionMasterPass() : $masterPwd;

        self::$strInitialVector = self::getIV();
        $cryptValue = self::encrypt($pwd, $masterPwd, self::$strInitialVector);

        return $cryptValue;
    }

    /**
     * Desencriptar la clave maestra de la sesión.
     *
     * @return string con la clave maestra
     */
    public static function getSessionMasterPass()
    {
        return self::getDecrypt($_SESSION["mPass"], $_SESSION['mPassPwd'], $_SESSION['mPassIV']);
    }

    /**
     * Desencriptar datos con la clave maestra.
     *
     * @param string $strEncrypted con los datos a desencriptar
     * @param string $strPassword  con la clave maestra
     * @param string $cryptIV      con el IV
     * @return string con los datos desencriptados
     */
    public static function getDecrypt($strEncrypted, $strPassword, $cryptIV)
    {
        $mcryptRes = self::getMcryptResource();
        mcrypt_generic_init($mcryptRes, $strPassword, $cryptIV);
        $strDecrypted = trim(mdecrypt_generic($mcryptRes, $strEncrypted));

        mcrypt_generic_deinit($mcryptRes);
        mcrypt_module_close($mcryptRes);

        return $strDecrypted;
    }

    /**
     * Crear el vector de inicialización.
     *
     * @return string con el IV
     */
    private static function getIV()
    {
        $source = MCRYPT_DEV_URANDOM;
        $mcryptRes = self::getMcryptResource();

        if (Util::runningOnWindows() && (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)) {
            $source = MCRYPT_RAND;
        }

        // Crear el IV y asegurar que tiene una longitud de 32 bytes
        do {
            $cryptIV = mcrypt_create_iv(mcrypt_enc_get_iv_size($mcryptRes), $source);
        } while (strlen($cryptIV) < 32);

        mcrypt_module_close($mcryptRes);

        return $cryptIV;
    }

    /**
     * Encriptar datos con la clave maestra.
     *
     * @param string $strValue    con los datos a encriptar
     * @param string $strPassword con la clave maestra
     * @param string $cryptIV     con el IV
     * @return string con los datos encriptados
     */
    private static function encrypt($strValue, $strPassword, $cryptIV)
    {
        $mcryptRes = self::getMcryptResource();

        mcrypt_generic_init($mcryptRes, $strPassword, $cryptIV);
        $strEncrypted = mcrypt_generic($mcryptRes, $strValue);
        mcrypt_generic_deinit($mcryptRes);

        return $strEncrypted;
    }

    /**
     * Generar la clave maestra encriptada con la clave del usuario.
     *
     * @param string $customPwd con la clave a encriptar
     * @param string $masterPwd con la clave maestra
     * @return string con la clave encriptada
     */
    public static function mkCustomMPassEncrypt($customPwd, $masterPwd)
    {
        $cryptIV = self::getIV();
        $cryptValue = self::encrypt($masterPwd, $customPwd, $cryptIV);
        $dataCrypt = array($cryptValue, $cryptIV);

        return $dataCrypt;
    }

    /**
     * Método para obtener un recurso del módulo mcrypt.
     * Se utiliza el algoritmo RIJNDAEL_256 en modo CBC
     *
     * @return resource
     */
    private static function getMcryptResource()
    {
        return mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
    }
}