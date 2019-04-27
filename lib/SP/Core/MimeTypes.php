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

namespace SP\Core;

use SP\Storage\File\FileCacheInterface;
use SP\Storage\File\FileException;
use SP\Storage\File\XmlFileStorageInterface;

/**
 * Class Mime
 *
 * @package SP\Core
 */
final class MimeTypes
{
    /**
     * Cache file name
     */
    const MIME_CACHE_FILE = CACHE_PATH . DIRECTORY_SEPARATOR . 'mime.cache';
    /**
     * Cache expire time
     */
    const CACHE_EXPIRE = 86400;
    /**
     * @var int
     */
    protected $lastLoadTime;
    /**
     * @var  array
     */
    protected $mimeTypes;
    /**
     * @var XmlFileStorageInterface
     */
    protected $xmlFileStorage;
    /**
     * @var FileCacheInterface
     */
    private $fileCache;

    /**
     * Mime constructor.
     *
     * @param FileCacheInterface      $fileCache
     * @param XmlFileStorageInterface $xmlFileStorage
     *
     * @throws FileException
     */
    public function __construct(FileCacheInterface $fileCache, XmlFileStorageInterface $xmlFileStorage)
    {
        $this->xmlFileStorage = $xmlFileStorage;
        $this->fileCache = $fileCache;

        $this->loadCache();
    }

    /**
     * Loads MIME types from cache file
     *
     * @return void
     * @throws FileException
     */
    protected function loadCache()
    {
        try {
            if ($this->fileCache->isExpired(self::CACHE_EXPIRE)
                || $this->fileCache->isExpiredDate($this->xmlFileStorage->getFileHandler()->getFileTime())
            ) {
                $this->mapAndSave();
            } else {
                $this->mimeTypes = $this->fileCache->load();

                logger('Loaded MIME types cache', 'INFO');
            }
        } catch (FileException $e) {
            processException($e);

            $this->mapAndSave();
        }
    }

    /**
     * @throws FileException
     */
    protected function mapAndSave()
    {
        logger('MIME TYPES CACHE MISS', 'INFO');

        $this->map();
        $this->saveCache();
    }

    /**
     * Sets an array of mime types
     *
     * @throws FileException
     */
    protected function map()
    {
        $this->mimeTypes = [];

        foreach ($this->load() as $item) {
            $this->mimeTypes[] = $item;
        }
    }

    /**
     * Loads MIME types from XML
     *
     * @return array
     * @throws FileException
     */
    protected function load()
    {
        return $this->xmlFileStorage->load('mimetypes')->getItems();
    }

    /**
     * Saves MIME types into cache file
     */
    protected function saveCache()
    {
        try {
            $this->fileCache->save($this->mimeTypes);

            logger('Saved MIME types cache', 'INFO');
        } catch (FileException $e) {
            processException($e);
        }
    }

    /**
     * @throws FileException
     */
    public function reset()
    {
        @unlink(self::MIME_CACHE_FILE);

        $this->loadCache();
    }

    /**
     * @return array
     */
    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
    }
}