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

namespace SP\Core\Bootstrap;

use ArrayAccess;
use SplObjectStorage;
use ValueError;

/**
 * Class PathsContext
 *
 * @template-implements ArrayAccess<Path, string>
 */
final readonly class PathsContext implements ArrayAccess
{
    /**
     * @var SplObjectStorage<Path, string>
     */
    private SplObjectStorage $paths;

    public function __construct()
    {
        $this->paths = new SplObjectStorage();
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->paths->contains($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->paths->offsetGet($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->addPath($offset, $value);
    }

    /**
     * @param Path $path
     * @param string $value
     * @return void
     */
    public function addPath(Path $path, string $value): void
    {
        if ($this->paths->contains($path)) {
            throw new ValueError('Duplicated path found: ' . $path->name);
        }

        $this->paths->attach($path, $value);
    }

    /**
     * @param array $paths
     * @return void
     */
    public function addPaths(array $paths): void
    {
        foreach ($paths as $pathSpec) {
            if (!is_array($pathSpec)) {
                throw new ValueError('Path spec must be an array');
            }

            $this->addPath(...$pathSpec);
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->paths->offsetUnset($offset);
    }
}
