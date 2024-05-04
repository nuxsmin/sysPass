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

use Defuse\Crypto\Key;
use SessionHandler;
use SessionHandlerInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;

use function SP\logger;

/**
 * Class CryptSessionHandler
 */
final class CryptSessionHandler implements SessionHandlerInterface
{
    public static bool $isSecured = false;

    public function __construct(
        private readonly Key            $key,
        private readonly CryptInterface $crypt,
        private readonly SessionHandler $sessionHandler
    ) {
    }

    /**
     * @inheritDoc
     */
    public function read(string $id): string
    {
        $data = $this->sessionHandler->read($id);

        if (!$data) {
            return '';
        }

        try {
            self::$isSecured = true;

            return $this->crypt->decrypt($data, $this->key);
        } catch (CryptException $e) {
            self::$isSecured = false;

            logger($e->getMessage());
            logger('Session data not encrypted.');

            return $data;
        }
    }

    /**
     * @inheritDoc
     */
    public function write(string $id, string $data): bool
    {
        try {
            $encryptedData = $this->crypt->encrypt($data, $this->key);

            self::$isSecured = true;
        } catch (CryptException $e) {
            self::$isSecured = false;

            logger('Could not encrypt session data.');
            logger($e->getMessage());
        }

        return $this->sessionHandler->write($id, $encryptedData ?? $data);
    }

    public function close(): bool
    {
        return $this->sessionHandler->close();
    }

    public function destroy(string $id): bool
    {
        return $this->sessionHandler->destroy($id);
    }

    public function gc(int $max_lifetime): int|false
    {
        return $this->sessionHandler->gc($max_lifetime);
    }

    public function open(string $path, string $name): bool
    {
        return $this->sessionHandler->open($path, $name);
    }
}
