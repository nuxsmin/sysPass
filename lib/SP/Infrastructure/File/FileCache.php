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

use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Storage\Ports\FileCacheService;

use function SP\__u;

/**
 * Class FileCache
 */
class FileCache extends FileCacheBase
{
    /**
     * @throws FileException
     * @throws SPException
     */
    public function load(?string $path = null): mixed
    {
        $this->checkOrInitializePath($path);

        return Serde::deserialize($this->path->checkIsReadable()->readToString());
    }

    /**
     * @throws FileException
     */
    public function save(mixed $data, ?string $path = null): FileCacheService
    {
        $this->checkOrInitializePath($path);
        $this->createPath();

        $this->path->write(Serde::serialize($data));

        return $this;
    }

    /**
     * @inheritDoc
     * @throws InvalidClassException
     */
    public function loadWith(string $class): object
    {
        $data = unserialize($this->path->checkIsReadable()->readToString(), ['allowed_classes' => [$class]]);

        if (!class_exists($class) || !($data instanceof $class)) {
            throw InvalidClassException::error(
                sprintf(__u('Either class does not exist or file data cannot unserialized into: %s'), $class)
            );
        }

        return $data;
    }
}
