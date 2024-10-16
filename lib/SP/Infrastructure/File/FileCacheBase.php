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

namespace SP\Infrastructure\File;

use SP\Domain\Storage\Ports\FileCacheService;

use function SP\__;

/**
 * Class FileCacheBase
 */
abstract class FileCacheBase implements FileCacheService
{
    protected ?FileHandler $path = null;

    public function __construct(?string $path = null)
    {
        if (null !== $path) {
            $this->path = new FileHandler($path, 'c+b');
        }
    }

    public static function factory(string $path): FileCacheBase
    {
        return new static($path);
    }

    /**
     * Returns if the file is expired adding time to modification date
     *
     * @throws FileException
     */
    public function isExpired(int $time = 86400): bool
    {
        $this->path->checkFileExists();

        return $this->path->getSize() === 0 || time() > $this->path->getFileTime() + $time;
    }

    /**
     * Returns if the file is expired adding time to modification date
     *
     * @throws FileException
     */
    public function isExpiredDate(int $date): bool
    {
        $this->path->checkFileExists();

        return $this->path->getSize() === 0 || $date > $this->path->getFileTime();
    }

    /**
     * @throws FileException
     */
    public function createPath(): void
    {
        $path = dirname($this->path->getFile());

        if (!is_dir($path)
            && !mkdir($path, 0700, true)
            && !is_dir($path)
        ) {
            throw new FileException(sprintf(__('Unable to create the directory (%s)'), $path));
        }
    }

    /**
     * @throws FileException
     */
    public function delete(): FileCacheService
    {
        $this->path->delete();

        return $this;
    }

    public function exists(): bool
    {
        return file_exists($this->path->getFile());
    }

    /**
     * @throws FileException
     */
    protected function checkOrInitializePath(?string $path = null): void
    {
        if (null === $path && null === $this->path) {
            throw new FileException(__('Path is needed'));
        }

        if (null === $this->path) {
            $this->path = new FileHandler($path);
        }
    }
}
