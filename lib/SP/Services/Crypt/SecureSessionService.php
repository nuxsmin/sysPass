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

namespace SP\Services\Crypt;

use Defuse\Crypto\Key;
use SP\Core\Crypt\UUIDCookie;
use SP\Core\Crypt\Vault;
use SP\Http\Request;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\FileCache;
use SP\Storage\FileException;
use SP\Util\HttpUtil;

/**
 * Class SecureSessionService
 *
 * @package SP\Services\Crypt
 */
class SecureSessionService extends Service
{
    const CACHE_EXPIRE_TIME = 86400;
    const CACHE_PATH = CACHE_PATH . DIRECTORY_SEPARATOR . 'secure_session';

    /**
     * @var FileCache
     */
    protected $fileCache;
    /**
     * @var string
     */
    protected $seed;

    /**
     * Returns the encryption key
     *
     * @return Key|false
     */
    public function getKey()
    {
        try {
            if ($this->fileCache->isExpired($this->getFileName(), self::CACHE_EXPIRE_TIME)) {
                debugLog('Session key expired or does not exist.');

                return $this->saveKey();
            }

            if (($vault = $this->fileCache->load($this->getFileName())) instanceof Vault) {
                return Key::loadFromAsciiSafeString($vault->getData($this->getCypher()));
            }
        } catch (FileException $e) {
            return $this->saveKey();
        } catch (\Exception $e) {
            processException($e);
        }

        return false;
    }

    /**
     * Returns an unique filename from a browser cookie
     *
     * @return string
     * @throws ServiceException
     */
    private function getFileName()
    {
        if (($uuid = UUIDCookie::loadCookie($this->seed)) === false
            && ($uuid = UUIDCookie::createCookie($this->seed)) === false) {
            throw new ServiceException('Unable to get UUID for filename.');
        }

        return self::CACHE_PATH . DIRECTORY_SEPARATOR . $uuid;
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
            $this->fileCache->save($this->getFileName(), (new Vault())->saveData($securedKey->saveToAsciiSafeString(), $this->getCypher()));

            debugLog('Saved session key.');

            return $securedKey;
        } catch (\Exception $e) {
            processException($e);
        }

        return false;
    }

    /**
     * Returns the key to be used for encrypting the session data
     *
     * @return string
     */
    private function getCypher()
    {
        return hash_pbkdf2('sha1',
            sha1(Request::getRequestHeaders('User-Agent') . HttpUtil::getClientAddress()),
            $this->seed,
            500,
            32
        );
    }

    protected function initialize()
    {
        $this->fileCache = $this->dic->get(FileCache::class);
        $this->seed = $this->config->getConfigData()->getPasswordSalt();
    }
}