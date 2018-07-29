<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

defined('APP_ROOT') || die();

use phpseclib\Crypt\RSA;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SPException;

/**
 * Class CryptPKI para el manejo de las funciones para PKI
 *
 * @package SP
 */
final class CryptPKI
{
    /**
     * @var RSA
     */
    protected $rsa;

    /**
     * @param RSA $rsa
     *
     * @throws SPException
     */
    public function __construct(RSA $rsa)
    {
        $this->rsa = $rsa;

        if (!$this->checkKeys()) {
            $this->createKeys();
        }
    }

    /**
     * Check if private and public keys exist
     *
     * @return bool
     */
    public function checkKeys()
    {
        return file_exists($this->getPublicKeyFile()) && file_exists($this->getPrivateKeyFile());
    }

    /**
     * Devuelve la ruta al archivo de la clave pública
     *
     * @return string
     */
    public function getPublicKeyFile()
    {
        return CONFIG_PATH . DIRECTORY_SEPARATOR . 'pubkey.pem';
    }

    /**
     * Devuelve la ruta al archivo de la clave privada
     *
     * @return string
     */
    public function getPrivateKeyFile()
    {
        return CONFIG_PATH . DIRECTORY_SEPARATOR . 'key.pem';
    }

    /**
     * Crea el par de claves pública y privada
     *
     * @throws SPException
     */
    public function createKeys()
    {
        $keys = $this->rsa->createKey(1024);

        $priv = file_put_contents($this->getPrivateKeyFile(), $keys['privatekey']);
        $pub = file_put_contents($this->getPublicKeyFile(), $keys['publickey']);

        if (!$priv || !$pub) {
            throw new SPException(__u('No es posible generar las claves RSA'), SPException::CRITICAL);
        }

        chmod($this->getPrivateKeyFile(), 0600);
    }

    /**
     * Encriptar datos con la clave pública
     *
     * @param string $data los datos a encriptar
     *
     * @return string
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    public function encryptRSA($data)
    {
        $this->rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $this->rsa->loadKey($this->getPublicKey());

        return $this->rsa->encrypt($data);
    }

    /**
     * Devuelve la clave pública desde el archivo
     *
     * @return string
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    public function getPublicKey()
    {
        $file = $this->getPublicKeyFile();

        if (!file_exists($file)) {
            throw new FileNotFoundException(__u('El archivo de clave no existe'));
        }

        return file_get_contents($file);
    }

    /**
     * Desencriptar datos cifrados con la clave pública
     *
     * @param string $data los datos a desencriptar
     *
     * @return string
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    public function decryptRSA($data)
    {
        $this->rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $this->rsa->loadKey($this->getPrivateKey());

        return @$this->rsa->decrypt($data);
    }

    /**
     * Devuelve la clave privada desde el archivo
     *
     * @return string
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    public function getPrivateKey()
    {
        $file = $this->getPrivateKeyFile();

        if (!file_exists($file)) {
            throw new FileNotFoundException(__u('El archivo de clave no existe'));
        }

        return file_get_contents($file);
    }
}