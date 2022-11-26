<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Crypt\Services;

use Defuse\Crypto\Key;
use Exception;
use SP\Core\Application;
use SP\Core\Crypt\UUIDCookie;
use SP\Core\Crypt\Vault;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Crypt\Ports\SecureSessionServiceInterface;
use SP\Http\RequestInterface;
use SP\Infrastructure\File\FileCache;
use SP\Infrastructure\File\FileException;
use function SP\logger;
use function SP\processException;

/**
 * Class SecureSessionService
 *
 * @package SP\Domain\Crypt\Services
 */
final class SecureSessionService extends Service implements SecureSessionServiceInterface
{
    private const CACHE_EXPIRE_TIME = 86400;
    private const CACHE_PATH        = CACHE_PATH.DIRECTORY_SEPARATOR.'secure_session';

    private RequestInterface $request;
    private string           $seed;
    private ?UUIDCookie      $cookie   = null;
    private ?string          $filename = null;

    public function __construct(Application $application, RequestInterface $request)
    {
        parent::__construct($application);

        $this->request = $request;
        $this->seed = $this->config->getConfigData()->getPasswordSalt();
    }


    /**
     * Returns the encryption key
     *
     * @param  UUIDCookie  $cookie
     *
     * @return Key|false
     */
    public function getKey(UUIDCookie $cookie): Key|bool
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

            $this->filename = self::CACHE_PATH.DIRECTORY_SEPARATOR.$uuid;
        }

        return $this->filename;
    }

    /**
     * Saves the encryption key
     *
     * @return Key|false
     */
    private function saveKey(): Key|bool
    {
        try {
            $securedKey = Key::createNewRandomKey();

            FileCache::factory($this->getFileNameFromCookie())
                ->save(
                    (new Vault())->saveData(
                        $securedKey->saveToAsciiSafeString(),
                        $this->getCypher()
                    )
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
                $this->request->getHeader('User-Agent').
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
}
