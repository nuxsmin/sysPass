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

namespace SP\Domain\Security\Models;

use SP\Domain\Common\Models\Model;

/**
 * Class Eventlog
 */
class Eventlog extends Model
{
    protected ?int    $id          = null;
    protected ?int    $date        = null;
    protected ?string $login       = null;
    protected ?int    $userId      = null;
    protected ?string $ipAddress   = null;
    protected ?string $action      = null;
    protected ?string $description = null;
    protected ?string $level       = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }
}
