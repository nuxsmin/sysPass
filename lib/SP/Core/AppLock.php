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

use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;

use function SP\logger;

/**
 * Class AppLock
 */
final readonly class AppLock implements AppLockHandler
{

    public function __construct(private string $lockFile)
    {
    }

    /**
     * Comprueba si la aplicación está bloqueada
     *
     * @throws SPException
     */
    public function getLock(): bool|int
    {
        try {
            $file = new FileHandler($this->lockFile);

            return (int)Serde::deserializeJson($file->readToString())->userId;
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
