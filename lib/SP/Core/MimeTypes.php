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

namespace SP\Core;

use SP\Domain\Core\File\MimeType;
use SP\Domain\Core\File\MimeTypesService;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Domain\Storage\Ports\YamlFileStorageService;
use SP\Infrastructure\File\FileException;

use function SP\logger;
use function SP\processException;

/**
 * Class Mime
 *
 * @package SP\Core
 */
final class MimeTypes implements MimeTypesService
{
    /**
     * Cache file name
     */
    public const MIME_CACHE_FILE = CACHE_PATH . DIRECTORY_SEPARATOR . 'mime.cache';
    /**
     * Cache expire time
     */
    public const CACHE_EXPIRE = 86400;

    /**
     * @var MimeType[]
     */
    protected array $mimeTypes = [];

    /**
     * Mime constructor.
     *
     * @throws FileException
     */
    public function __construct(
        private readonly FileCacheService       $fileCache,
        private readonly YamlFileStorageService $yamlFileStorageService
    ) {
        $this->loadCache();
    }

    /**
     * Loads MIME types from cache file
     *
     * @throws FileException
     */
    private function loadCache(): void
    {
        if (!$this->fileCache->exists()
            || $this->fileCache->isExpired(self::CACHE_EXPIRE)
            || $this->fileCache->isExpiredDate($this->yamlFileStorageService->getFileTime())
        ) {
            $this->mapAndSave();
        } else {
            $this->mimeTypes = $this->fileCache->load();

            logger('Loaded MIME types cache', 'INFO');
        }
    }

    /**
     * @return void
     * @throws FileException
     */
    private function mapAndSave(): void
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
    private function map(): void
    {
        $this->mimeTypes = array_map(
            static fn($item) => new MimeType($item['type'], $item['description'], $item['extension']),
            $this->yamlFileStorageService->load()
        );
    }

    /**
     * Saves MIME types into cache file
     */
    private function saveCache(): void
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
    public function reset(): void
    {
        logger('Reset MIME types cache', 'INFO');

        $this->fileCache->delete();

        $this->loadCache();
    }

    /**
     * @return MimeType[]
     */
    public function getMimeTypes(): array
    {
        return $this->mimeTypes;
    }
}
