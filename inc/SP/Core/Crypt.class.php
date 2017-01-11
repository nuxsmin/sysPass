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

namespace SP\Core;

use SP\Account\AccountHistory;
use SP\Config\Config;
use SP\Config\ConfigDB;
use SP\Core\Exceptions\SPException;
use SP\Log\Log;
use SP\Util\Checks;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar el encriptado/desencriptado de claves
 */
class Crypt
{
    public static $strInitialVector;

    /**
     * Generar un hash de una clave utilizando un salt.
     *
     * @param string $pwd con la clave a 'hashear'
     * @param bool   $prefixSalt Añadir el salt al hash
     * @return string con el hash de la clave
     */
    public static function mkHashPassword($pwd, $prefixSalt = true)
    {
        $salt = self::makeHashSalt();
        $hash = crypt($pwd, $salt);

        return ($prefixSalt === true) ? $salt . $hash : $hash;
    }

    /**
     * Crear un salt utilizando mcrypt.
     *
     * @return string con el salt creado
     */
    public static function makeHashSalt()
    {
        return '$2y$07$' . bin2hex(self::getIV()) . '$';
    }

    /**
     * Crear el vector de inicialización.
     *
     * @return string con el IV
     */
    public static function getIV()
    {
        $source = MCRYPT_DEV_URANDOM;
        $mcryptRes = self::getMcryptResource();

        if (Checks::checkIsWindows() && (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)) {
            $source = MCRYPT_RAND;
        }

        // Crear el IV y asegurar que tiene una longitud de 32 bytes
        do {
            $cryptIV = mcrypt_create_iv(mcrypt_enc_get_iv_size($mcryptRes), $source);
        } while ($cryptIV === false || strlen($cryptIV) < 32);

        mcrypt_module_close($mcryptRes);

        return $cryptIV;
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

    /**
     * Comprobar el hash de una clave.
     *
     * @param string $pwd          con la clave a comprobar
     * @param string $checkedHash con el hash a comprobar
     * @param bool   $isMPass      si es la clave maestra
     * @return bool
     */
    public static function checkHashPass($pwd, $checkedHash, $isMPass = false)
    {
        // Obtenemos el salt de la clave
        $salt = substr($checkedHash, 0, 72);
        // Obtenemos el hash SHA256
        $validHash = substr($checkedHash, 72);
        // Re-hash de la clave a comprobar
        $testHash = crypt($pwd, $salt);

        // Comprobar si el hash está en formato anterior a 12002
        if ($isMPass && strlen($checkedHash) === 128) {
            $check = (hash('sha256', substr($checkedHash, 0, 64) . $pwd) === substr($checkedHash, 64, 64));

            if ($check) {
                $newHash = self::mkHashPassword($pwd);

                AccountHistory::updateAccountsMPassHash($newHash);
                ConfigDB::setValue('masterPwd', $newHash);
                Log::writeNewLog(_('Aviso'), _('Se ha regenerado el HASH de clave maestra. No es necesaria ninguna acción.'), Log::NOTICE);
            }

            return $check;
        }

        // Si los hashes son idénticos, la clave es válida
        return $testHash === $validHash;
    }

    /**
     * Generar la clave maestra encriptada con una clave
     *
     * @param string $customPwd con la clave a encriptar
     * @param string $masterPwd con la clave maestra
     * @return string con la clave encriptada
     */
    public static function mkCustomMPassEncrypt($customPwd, $masterPwd)
    {
        $cryptIV = self::getIV();
        $cryptValue = self::encrypt($masterPwd, $customPwd, $cryptIV);

        return [$cryptValue, $cryptIV];
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
     * Encriptar datos. Devuelve un array con los datos encriptados y el IV.
     *
     * @param mixed  $data string Los datos a encriptar
     * @param string $pwd La clave de encriptación
     * @return array
     * @throws SPException
     */
    public static function encryptData($data, $pwd = null)
    {
        if (empty($data)) {
            return array('data' => '', 'iv' => '');
        }

        // Comprobar el módulo de encriptación
        if (!Crypt::checkCryptModule()) {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno'),
                _('No se puede usar el módulo de encriptación')
            );
        }

        // Encriptar datos
        $encData['data'] = Crypt::mkEncrypt($data, $pwd);

        if (!empty($data) && ($encData['data'] === false || is_null($encData['data']))) {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno'),
                _('Error al generar datos cifrados')
            );
        }

        $encData['iv'] = Crypt::$strInitialVector;

        return $encData;
    }

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
     * Generar datos encriptados.
     * Esta función llama a los métodos privados para encriptar datos.
     *
     * @param string $data      con los datos a encriptar
     * @param string $masterPwd con la clave maestra
     * @return bool
     */
    public static function mkEncrypt($data, $masterPwd = null)
    {
        $masterPwd = null === $masterPwd ? SessionUtil::getSessionMPass() : $masterPwd;

        self::$strInitialVector = self::getIV();
        $cryptValue = self::encrypt($data, $masterPwd, self::$strInitialVector);

        return $cryptValue;
    }

    /**
     * Desencriptar datos con la clave maestra.
     *
     * @param string $cryptData Los datos a desencriptar
     * @param string $cryptIV      con el IV
     * @param string $password  La clave maestra
     * @return string con los datos desencriptados
     */
    public static function getDecrypt($cryptData, $cryptIV, $password = null)
    {
        if (empty($cryptData) || empty($cryptIV)){
            return false;
        } elseif (null === $password) {
            $password = SessionUtil::getSessionMPass();
        }

        $mcryptRes = self::getMcryptResource();
        @mcrypt_generic_init($mcryptRes, $password, $cryptIV);
        $strDecrypted = trim(mdecrypt_generic($mcryptRes, $cryptData));

        mcrypt_generic_deinit($mcryptRes);
        mcrypt_module_close($mcryptRes);

        return $strDecrypted;
    }

    /**
     * Generar una key para su uso con el algoritmo AES
     *
     * @param string $string La cadena de la que deriva la key
     * @param null   $salt   El salt utilizado
     * @return string
     */
    public static function generateAesKey($string, $salt = null)
    {
        if (null === $salt) {
            $salt = Config::getConfig()->getPasswordSalt();
        }

        $salt = '$2y$07$' . $salt . '$';

        return substr(crypt($string, $salt), 7, 32);
    }

    public static function checkPassword($pwd, $salt)
    {
        $testHash = crypt($pwd, $salt);
    }
}