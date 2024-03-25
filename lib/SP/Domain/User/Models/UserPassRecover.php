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

namespace SP\Domain\User\Models;

use SP\Domain\Common\Models\Model;

/**
 * Class UserPassRecover
 */
class UserPassRecover extends Model
{
    public const TABLE = 'UserPassRecover';

    public ?int    $userId = null;
    public ?string $hash   = null;
    public ?int    $date   = null;
    public ?bool   $used   = null;

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function isUsed(): ?bool
    {
        return $this->used;
    }
}
