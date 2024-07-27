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

namespace SP\Core;

use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\FileSystem;

use function SP\logger;

/**
 * Class AppLock
 */
final readonly class AppLock
{

    private string $lockFile;

    public function __construct(PathsContext $pathsContext)
    {
        $this->lockFile = FileSystem::buildPath($pathsContext[Path::CONFIG], '.lock');
    }

    /**
     * Comprueba si la aplicación está bloqueada
     *
     * @return bool|string
     * @throws SPException
     */
    public function getLock(): bool|string
    {
        try {
            $file = new FileHandler($this->lockFile);

            return Serde::deserializeJson($file->readToString())->userId;
        } catch (FileException) {
            return false;
        }
    }

    /**
     * @throws FileException
     * @throws SPException
     */
    public function lock(int $userId, string $subject): void
    {
        $data = ['time' => time(), 'userId' => $userId, 'subject' => $subject];

        $file = new FileHandler($this->lockFile);
        $file->save(Serde::serializeJson($data));

        logger('Application locked out');
    }

    public function unlock(): void
    {
        @unlink($this->lockFile);
    }
}
