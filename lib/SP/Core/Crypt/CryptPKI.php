<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Core\Crypt;

defined('APP_ROOT') || die();

use phpseclib\Crypt\RSA;
use SP\Core\Exceptions\SPException;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;

/**
 * Class CryptPKI para el manejo de las funciones para PKI
 *
 * @package SP
 */
class CryptPKI
{
    public const KEY_SIZE = 1024;
    public const PUBLIC_KEY_FILE = CONFIG_PATH . DIRECTORY_SEPARATOR . 'pubkey.pem';
    public const PRIVATE_KEY_FILE = CONFIG_PATH . DIRECTORY_SEPARATOR . 'key.pem';

    protected RSA $rsa;
    private ?FileHandler $publicKeyFile = null;
    private ?FileHandler $privateKeyFile = null;

    /**
     * @throws SPException
     */
    public function __construct(RSA $rsa)
    {
        $this->rsa = $rsa;

        $this->setUp();
    }

    /**
     * Check if private and public keys exist
     *
     * @throws SPException
     */
    private function setUp(): void
    {
        $this->publicKeyFile = new FileHandler(self::PUBLIC_KEY_FILE);
        $this->privateKeyFile = new FileHandler(self::PRIVATE_KEY_FILE);

        try {
            $this->publicKeyFile->checkFileExists();
            $this->privateKeyFile->checkFileExists();
        } catch (FileException $e) {
            processException($e);

            $this->createKeys();
        }
    }

    /**
     * Crea el par de claves pública y privada
     *
     * @throws FileException
     */
    public function createKeys(): void
    {
        $keys = $this->rsa->createKey(self::KEY_SIZE);

        $this->publicKeyFile->save($keys['publickey']);
        $this->privateKeyFile->save($keys['privatekey']);

        chmod(self::PRIVATE_KEY_FILE, 0600);
    }

    public static function getMaxDataSize(): int
    {
        return (self::KEY_SIZE / 8) - 11;
    }

    /**
     * Encriptar datos con la clave pública
     *
     * @throws FileException
     */
    public function encryptRSA(string $data): string
    {
        $this->rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $this->rsa->loadKey($this->getPublicKey(), RSA::PUBLIC_FORMAT_PKCS1);

        return $this->rsa->encrypt($data);
    }

    /**
     * Devuelve la clave pública desde el archivo
     *
     * @throws FileException
     */
    public function getPublicKey(): string
    {
        return $this->publicKeyFile
            ->checkFileExists()
            ->readToString();
    }

    /**
     * Desencriptar datos cifrados con la clave pública
     *
     * @throws FileException
     */
    public function decryptRSA(string $data): ?string
    {
        $this->rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $this->rsa->loadKey($this->getPrivateKey(), RSA::PRIVATE_FORMAT_PKCS1);

        return @$this->rsa->decrypt($data) ?: null;
    }

    /**
     * Devuelve la clave privada desde el archivo
     *
     * @throws FileException
     */
    public function getPrivateKey(): string
    {
        return $this->privateKeyFile
            ->checkFileExists()
            ->readToString();
    }

    /**
     * @throws FileException
     */
    public function getKeySize(): int
    {
        $this->rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        $this->rsa->loadKey($this->getPrivateKey(), RSA::PRIVATE_FORMAT_PKCS1);

        return $this->rsa->getSize();
    }
}