<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core;

defined('APP_ROOT') || die();

use phpseclib\Crypt\RSA;
use phpseclib\Exception\FileNotFoundException;
use SP\Core\Exceptions\SPException;
use SP\Log\Log;

/**
 * Class CryptPKI para el manejo de las funciones para PKI
 *
 * @package SP
 */
class CryptPKI
{
    /**
     * @throws SPException
     */
    public function __construct()
    {
        if (!file_exists($this->getPublicKeyFile()) || !file_exists($this->getPrivateKeyFile())) {
            if (!$this->createKeys()) {
                throw new SPException(SPException::SP_CRITICAL, __('No es posible generar las claves RSA', false));
            }
        }
    }

    /**
     * Devuelve la ruta al archivo de la clave pública
     *
     * @return string
     */
    private function getPublicKeyFile()
    {
        return Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'pubkey.pem';
    }

    /**
     * Devuelve la ruta al archivo de la clave privada
     *
     * @return string
     */
    private function getPrivateKeyFile()
    {
        return Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'key.pem';
    }

    /**
     * Crea el par de claves pública y privada
     */
    public function createKeys()
    {
        $Rsa = new RSA();
        $keys = $Rsa->createKey(1024);

        $priv = file_put_contents($this->getPrivateKeyFile(), $keys['privatekey']);
        $pub = file_put_contents($this->getPublicKeyFile(), $keys['publickey']);

        chmod($this->getPrivateKeyFile(), 0600);

        return ($priv && $pub);
    }

    /**
     * Encriptar datos con la clave pública
     *
     * @param string $data los datos a encriptar
     * @return string
     * @throws \phpseclib\Exception\FileNotFoundException
     */
    public function encryptRSA($data)
    {
        $Rsa = new RSA();
        $Rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $Rsa->loadKey($this->getPublicKey());

        return $Rsa->encrypt($data);
    }

    /**
     * Devuelve la clave pública desde el archivo
     *
     * @return string
     * @throws \phpseclib\Exception\FileNotFoundException
     */
    public function getPublicKey()
    {
        $file = $this->getPublicKeyFile();

        if (!file_exists($file)) {
            Log::writeNewLog(__FUNCTION__, __('El archivo de clave no existe', false), Log::NOTICE);

            throw new FileNotFoundException(__('El archivo de clave no existe'));
        }

        return file_get_contents($file);
    }

    /**
     * Desencriptar datos cifrados con la clave pública
     *
     * @param string $data los datos a desencriptar
     * @return string
     * @throws \phpseclib\Exception\FileNotFoundException
     */
    public function decryptRSA($data)
    {
        $Rsa = new RSA();
        $Rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $Rsa->loadKey($this->getPrivateKey());

        return $Rsa->decrypt($data);
    }

    /**
     * Devuelve la clave privada desde el archivo
     *
     * @return string
     * @throws \phpseclib\Exception\FileNotFoundException
     */
    private function getPrivateKey()
    {
        $file = $this->getPrivateKeyFile();

        if (!file_exists($file)) {
            Log::writeNewLog(__FUNCTION__, __('El archivo de clave no existe', false), Log::NOTICE);

            throw new FileNotFoundException(__('El archivo de clave no existe'));
        }

        return file_get_contents($file);
    }
}