<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\DataModel;

use SP\Domain\Common\Adapters\DataModel;

/**
 * Class UserPassData
 *
 * @package SP\DataModel
 */
class UserPassData extends DataModel
{
    protected ?int    $id              = null;
    protected ?string $pass            = null;
    protected ?string $hashSalt        = null;
    protected ?string $mPass           = null;
    protected ?string $mKey            = null;
    protected ?int    $lastUpdateMPass = null;

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function setPass(string $pass)
    {
        $this->pass = $pass;
    }

    public function getHashSalt(): ?string
    {
        return $this->hashSalt;
    }

    public function getMPass(): ?string
    {
        return $this->mPass;
    }

    public function setMPass(string $mPass)
    {
        $this->mPass = $mPass;
    }

    public function getMKey(): ?string
    {
        return $this->mKey;
    }

    public function setMKey(string $mKey)
    {
        $this->mKey = $mKey;
    }

    public function getLastUpdateMPass(): int
    {
        return (int)$this->lastUpdateMPass;
    }

    public function setLastUpdateMPass(int $lastUpdateMPass)
    {
        $this->lastUpdateMPass = (int)$lastUpdateMPass;
    }

    public function getId(): ?int
    {
        return (int)$this->id;
    }

    public function setId(int $id)
    {
        $this->id = (int)$id;
    }
}
