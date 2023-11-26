<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Core\Exceptions\InvalidClassException;

use function SP\__u;

/**
 * Class FileCache
 *
 * @package SP\Infrastructure\File;
 */
class FileCache extends FileCacheBase
{
    /**
     * @throws FileException
     * @throws InvalidClassException
     */
    public function load(?string $path = null, ?string $class = null): mixed
    {
        $this->checkOrInitializePath($path);

        /** @noinspection UnserializeExploitsInspection */
        $data = unserialize($this->path->checkIsReadable()->readToString());

        if ($class && (!class_exists($class) || !($data instanceof $class))) {
            throw new InvalidClassException(
                sprintf(__u('Either class does not exist or file data cannot unserialized into: %s'), $class)
            );
        }

        return $data;
    }

    /**
     * @throws FileException
     */
    public function save(mixed $data, ?string $path = null): FileCacheInterface
    {
        $this->checkOrInitializePath($path);
        $this->createPath();

        $this->path->checkIsWritable()->open('wb', true);
        $this->path->write(serialize($data))->close();

        return $this;
    }
}
