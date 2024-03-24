<?php
/*
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

namespace SP\Domain\Crypt\Dtos;

use SP\Core\Crypt\Hash;
use SP\Domain\Task\Ports\TaskInterface;

/**
 * Class UpdateMasterPassRequest
 */
final class UpdateMasterPassRequest
{
    private string $hash;

    public function __construct(
        private readonly string         $currentMasterPass,
        private readonly string         $newMasterPass,
        private readonly string         $currentHash,
        private readonly ?TaskInterface $task = null
    ) {
        $this->hash = Hash::hashKey($newMasterPass);
    }

    public function getCurrentMasterPass(): string
    {
        return $this->currentMasterPass;
    }

    public function getNewMasterPass(): string
    {
        return $this->newMasterPass;
    }

    public function getTask(): ?TaskInterface
    {
        return $this->task;
    }

    public function useTask(): bool
    {
        return $this->task !== null;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getCurrentHash(): string
    {
        return $this->currentHash;
    }
}
