<?php
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

declare(strict_types=1);

namespace SP\Core;

use RuntimeException;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Domain\Core\Exceptions\FileException;
use SP\Domain\File\Ports\FileHandlerFactoryInterface;
use Throwable;

use function SP\__;
use function SP\logger;

/**
 * Class AppLock
 */
final class AppLock implements AppLockHandler
{
    /** @var resource|null */
    private mixed $handle = null;
    private ?string $owner = null;

    public function __construct(
        private string                      $lockFile,
        private FileHandlerFactoryInterface $fileHandlerFactory,
        private int                         $lockTtl = 3600
    ) {
    }

    /**
     * Comprueba si la aplicación está bloqueada
     *
     * @throws SPException
     */
    public function getLock(): bool|int
    {
        try {
            $data = $this->decodeLock(
                $this->fileHandlerFactory->create($this->lockFile)->readToString()
            );
        } catch (FileException|RuntimeException) {
            return false;
        }

        if ($data === null || !isset($data->userId) || $this->isStale($data)) {
            return false;
        }

        return (int)$data->userId;
    }

    /**
     * @throws FileException
     * @throws SPException
     */
    public function lock(int $userId, string $subject): void
    {
        $this->writeLockFile(['time' => time(), 'userId' => $userId, 'subject' => $subject]);

        logger('Application locked out');
    }

    public function tryLock(int $userId, string $subject): bool
    {
        if ($this->owner !== null) {
            return false;
        }

        $handle = @fopen($this->lockFile, 'c+');

        if ($handle === false) {
            throw FileException::error(sprintf(__('Unable to read/write the file (%s)'), $this->lockFile));
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return false;
        }

        $contents = stream_get_contents($handle);
        $existing = $this->decodeLock($contents === false ? '' : $contents);

        if ($existing !== null
            && !$this->hasOwnerToken($existing)
            && !$this->isStale($existing)) {
            flock($handle, LOCK_UN);
            fclose($handle);

            return false;
        }

        $token = bin2hex(random_bytes(16));
        $data = [
            'time' => time(),
            'userId' => $userId,
            'subject' => $subject,
            'token' => $token,
            'expires' => time() + $this->lockTtl
        ];

        try {
            $this->writeHandle($handle, Serde::serializeJson($data));
        } catch (Throwable $e) {
            flock($handle, LOCK_UN);
            fclose($handle);

            throw $e;
        }

        $this->handle = $handle;
        $this->owner = $token;

        logger('Application locked out');

        return true;
    }

    public function unlock(): void
    {
        if ($this->owner !== null) {
            $this->releaseOwned();

            return;
        }

        try {
            $data = $this->decodeLock(
                $this->fileHandlerFactory->create($this->lockFile)->readToString()
            );

            if ($data !== null && isset($data->token) && $data->token !== '') {
                return;
            }

            $this->fileHandlerFactory->create($this->lockFile)->delete();
        } catch (FileException|RuntimeException) {
        }
    }

    private function releaseOwned(): void
    {
        $handle = $this->handle;
        $owner = $this->owner;

        $this->handle = null;
        $this->owner = null;

        if (!is_resource($handle)) {
            return;
        }

        try {
            rewind($handle);
            $contents = stream_get_contents($handle);
            $data = $this->decodeLock($contents === false ? '' : $contents);

            if ($owner !== null
                && $data !== null
                && isset($data->token)
                && is_string($data->token)
                && hash_equals($data->token, $owner)) {
                @unlink($this->lockFile);
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        logger('Application unlocked');
    }

    /**
     * @param array<string, int|string> $data
     */
    private function writeLockFile(array $data): void
    {
        $handle = @fopen($this->lockFile, 'c+');

        if ($handle === false) {
            throw FileException::error(sprintf(__('Unable to read/write the file (%s)'), $this->lockFile));
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw FileException::error(sprintf(__('Unable to obtain a lock (%s)'), $this->lockFile));
            }

            $this->writeHandle($handle, Serde::serializeJson($data));
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     */
    private function writeHandle($handle, string $contents): void
    {
        rewind($handle);

        if (ftruncate($handle, 0) === false
            || fwrite($handle, $contents) === false
            || fflush($handle) === false) {
            throw FileException::error(sprintf(__('Unable to read/write the file (%s)'), $this->lockFile));
        }
    }

    private function decodeLock(string $contents): ?object
    {
        if (trim($contents) === '') {
            return null;
        }

        try {
            return Serde::deserializeJson($contents);
        } catch (Throwable) {
            return null;
        }
    }

    private function hasOwnerToken(object $data): bool
    {
        return isset($data->token) && is_string($data->token) && $data->token !== '';
    }

    private function isStale(object $data): bool
    {
        $expires = isset($data->expires) ? (int)$data->expires : 0;

        if ($expires > 0) {
            return $expires <= time();
        }

        $userId = isset($data->userId) ? (int)$data->userId : 0;
        $time = isset($data->time) ? (int)$data->time : 0;

        return $userId === 0 && $time > 0 && (time() - $time) >= $this->lockTtl;
    }
}
