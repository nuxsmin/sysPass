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

namespace SP\Services\Crypt;

use Defuse\Crypto\Key;
use Exception;
use SP\Core\Crypt\UUIDCookie;
use SP\Core\Crypt\Vault;
use SP\Http\Request;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\File\FileCache;
use SP\Storage\File\FileException;

/**
 * Class SecureSessionService
 *
 * @package SP\Services\Crypt
 */
final class SecureSessionService extends Service
{
    private const CACHE_EXPIRE_TIME = 86400;
    private const CACHE_PATH = CACHE_PATH . DIRECTORY_SEPARATOR . 'secure_session';

    protected ?string $seed = null;
    protected ?Request $request = null;
    protected ?UUIDCookie $cookie = null;
    private ?string $filename = null;

    /**
     * Returns the encryption key
     *
     * @param UUIDCookie $cookie
     *
     * @return Key|false
     */
    public function getKey(UUIDCookie $cookie)
    {
        $this->cookie = $cookie;

        try {
            $cache = FileCache::factory($this->getFileNameFromCookie());

            if ($cache->isExpired(self::CACHE_EXPIRE_TIME)) {
                logger('Session key expired or does not exist', 'ERROR');

                return $this->saveKey();
            }

            if (($vault = $cache->load()) instanceof Vault) {
                return Key::loadFromAsciiSafeString($vault->getData($this->getCypher()));
            }
        } catch (FileException $e) {
            return $this->saveKey();
        } catch (Exception $e) {
            processException($e);
        }

        return false;
    }

    /**
     * Returns an unique filename from a browser cookie
     *
     * @throws ServiceException
     */
    private function getFileNameFromCookie(): string
    {
        if (empty($this->filename)) {
            if (($uuid = $this->cookie->loadCookie($this->seed)) === false
                && ($uuid = $this->cookie->createCookie($this->seed)) === false
            ) {
                throw new ServiceException('Unable to get UUID for filename');
            }

            $this->filename = self::CACHE_PATH . DIRECTORY_SEPARATOR . $uuid;
        }

        return $this->filename;
    }

    /**
     * Saves the encryption key
     *
     * @return Key|false
     */
    private function saveKey()
    {
        try {
            $securedKey = Key::createNewRandomKey();

            FileCache::factory($this->getFileNameFromCookie())
                ->save(
                    (new Vault())->saveData($securedKey->saveToAsciiSafeString(),
                        $this->getCypher())
                );

            logger('Saved session key');

            return $securedKey;
        } catch (Exception $e) {
            processException($e);
        }

        return false;
    }

    /**
     * Returns the key to be used for encrypting the session data
     */
    private function getCypher(): string
    {
        return hash_pbkdf2(
            'sha1',
            sha1(
                $this->request->getHeader('User-Agent') .
                $this->request->getClientAddress()
            ),
            $this->seed,
            500,
            32
        );
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    protected function initialize(): void
    {
        $this->request = $this->dic->get(Request::class);
        $this->seed = $this->config->getConfigData()->getPasswordSalt();
    }
}