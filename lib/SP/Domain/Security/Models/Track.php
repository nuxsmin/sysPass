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

namespace SP\Domain\Security\Models;

use SP\Domain\Common\Models\Model;

/**
 * Class Track
 */
class Track extends Model
{
    protected ?int    $id         = null;
    protected ?int    $userId     = null;
    protected ?string $source     = null;
    protected ?int    $time       = null;
    protected ?int    $timeUnlock = null;
    protected ?string $ipv4       = null;
    protected ?string $ipv6       = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function getIpv4(): ?string
    {
        return $this->ipv4;
    }

    public function getIpv6(): ?string
    {
        return $this->ipv6;
    }

    public function getTimeUnlock(): ?int
    {
        return $this->timeUnlock;
    }
}
