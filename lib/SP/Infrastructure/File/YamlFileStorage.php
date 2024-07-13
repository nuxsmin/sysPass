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

namespace SP\Infrastructure\File;

use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Storage\Ports\YamlFileStorageService;
use Symfony\Component\Yaml\Yaml;

/**
 * Class YamlFileStorage
 */
final readonly class YamlFileStorage implements YamlFileStorageService
{
    public function __construct(private FileHandlerInterface $fileHandler)
    {
    }

    /**
     * @inheritDoc
     */
    public function load(): array
    {
        return Yaml::parseFile($this->fileHandler->getFile());
    }

    /**
     * @inheritDoc
     */
    public function save(array $data): YamlFileStorageService
    {
        $this->fileHandler->save(Yaml::dump($data, 3, 2, Yaml::DUMP_OBJECT_AS_MAP));

        return $this;
    }

    /**
     * @throws FileException
     */
    public function getFileTime(): int
    {
        return $this->fileHandler->getFileTime();
    }
}
