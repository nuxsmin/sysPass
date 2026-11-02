<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use LengthException;
use OutOfRangeException;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use RuntimeException;
use SP\Domain\Core\Crypt\CryptPKIHandler;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Core\Exceptions\FileException;

use function SP\processException;

/**
 * Class CryptPKI para el manejo de las funciones para PKI
 */
final class CryptPKI implements CryptPKIHandler
{
    public const KEY_SIZE         = 1024;
    public const PUBLIC_KEY_FILE  = 'pubkey.pem';
    public const PRIVATE_KEY_FILE = 'key.pem';

    /**
     * @throws SPException
     */
    public function __construct(
        private readonly FileHandlerInterface $publicKeyFile,
        private readonly FileHandlerInterface $privateKeyFile
    ) {
        $this->setUp();
    }

    /**
     * Check if private and public keys exist
     *
     * @throws SPException
     */
    private function setUp(): void
    {
        try {
            $this->publicKeyFile->getFileSize(true);
            $this->privateKeyFile->getFileSize(true);
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
        $privateKey = RSA::createKey(self::KEY_SIZE);

        $this->publicKeyFile->save($privateKey->getPublicKey()->toString('PKCS8'));
        $this->privateKeyFile->save($privateKey->toString('PKCS1'))->chmod(0600);
    }

    public static function getMaxDataSize(): int
    {
        return (self::KEY_SIZE / 8) - 11;
    }

    /**
     * Devuelve la clave pública desde el archivo
     *
     * @throws FileException
     */
    public function getPublicKey(): string
    {
        return $this->publicKeyFile->checkFileExists()->readToString();
    }

    /**
     * Desencriptar datos cifrados con la clave pública
     *
     * @throws FileException
     */
    public function decryptRSA(string $data): ?string
    {
        try {
            $privateKey = PublicKeyLoader::loadPrivateKey($this->getPrivateKey());

            if (!$privateKey instanceof RSA\PrivateKey) {
                return null;
            }

            return $privateKey
                       ->withPadding(RSA::ENCRYPTION_PKCS1)
                       ->decrypt($data) ?: null;
        } catch (RuntimeException | LengthException | OutOfRangeException $e) {
            return null;
        }
    }

    /**
     * @throws FileException
     */
    private function getPrivateKey(): string
    {
        return $this->privateKeyFile->checkFileExists()->readToString();
    }
}
