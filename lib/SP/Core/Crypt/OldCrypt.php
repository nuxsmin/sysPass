<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Core\Crypt;

use SP\Bootstrap;
use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\Util\Checks;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de realizar el encriptado/desencriptado de claves
 *
 * @deprecated Since 2.1
 */
final class OldCrypt
{
    public static $strInitialVector;

    /**
     * Generar un hash de una clave utilizando un salt.
     *
     * @param string $pwd        con la clave a 'hashear'
     * @param bool   $prefixSalt Añadir el salt al hash
     *
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
     * @param string $salt
     * @param bool   $random
     *
     * @return string con el salt creado
     */
    public static function makeHashSalt($salt = null, $random = true)
    {
        /** @var ConfigData $ConfigData */
        $ConfigData = Bootstrap::getContainer()['configData'];

        if ($random === true) {
            $salt = bin2hex(self::getIV());
        } elseif ($salt !== null && strlen($salt) < 22) {
            $salt .= $ConfigData->getPasswordSalt();
        } elseif ($salt === null) {
            $salt = $ConfigData->getPasswordSalt();
        }

        return '$2y$07$' . substr($salt, 0, 22) . '$';
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
     * Generar la clave maestra encriptada con una clave
     *
     * @param string $customPwd con la clave a encriptar
     * @param string $masterPwd con la clave maestra
     *
     * @return array con la clave encriptada
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
     *
     * @return string con los datos encriptados
     */
    private static function encrypt($strValue, $strPassword, $cryptIV)
    {
        if (empty($strValue)) {
            return '';
        }

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
     * @param string $pwd  La clave de encriptación
     *
     * @return array
     * @throws SPException
     */
    public static function encryptData($data, $pwd = null)
    {
        if (empty($data)) {
            return array('data' => '', 'iv' => '');
        }

        // Comprobar el módulo de encriptación
        if (!OldCrypt::checkCryptModule()) {
            throw new SPException(
                __u('Internal error'),
                SPException::CRITICAL,
                __u('Crypto module cannot be loaded')
            );
        }

        // FIXME
        // Encriptar datos
        $encData['data'] = OldCrypt::mkEncrypt($data, $pwd);

        if (!empty($data) && ($encData['data'] === false || null === $encData['data'])) {
            throw new SPException(
                __u('Internal error'),
                SPException::CRITICAL,
                __u('Error while creating the encrypted data')
            );
        }

        $encData['iv'] = OldCrypt::$strInitialVector;

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
     *
     * @return bool
     */
    public static function mkEncrypt($data, $masterPwd)
    {
        self::$strInitialVector = self::getIV();

        return self::encrypt($data, $masterPwd, self::$strInitialVector);
    }

    /**
     * Desencriptar datos con la clave maestra.
     *
     * @param string $cryptData Los datos a desencriptar
     * @param string $cryptIV   con el IV
     * @param string $password  La clave maestra
     *
     * @return string con los datos desencriptados
     */
    public static function getDecrypt($cryptData, $cryptIV, $password)
    {
        if (empty($cryptData) || empty($cryptIV)) {
            return false;
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
     *
     * @return string
     */
    public static function generateAesKey($string, $salt = null)
    {
        return substr(crypt($string, self::makeHashSalt($salt, false)), 7, 32);
    }
}