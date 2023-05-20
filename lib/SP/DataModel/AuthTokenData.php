<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Common\Adapters\DataModelInterface;
use SP\Domain\Common\Models\Model;

/**
 * Class AuthTokenData
 *
 * @package SP\DataModel
 */
class AuthTokenData extends Model implements DataModelInterface
{
    public ?int       $userId    = null;
    public ?string    $token     = null;
    public ?int       $createdBy = null;
    public ?int       $startDate = null;
    public ?int       $actionId  = null;
    public ?string    $hash      = null;
    protected ?int    $id        = null;
    protected ?string $vault     = null;

    public function getId(): ?int
    {
        return (int)$this->id;
    }

    public function getVault(): ?string
    {
        return $this->vault;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function getStartDate(): ?int
    {
        return $this->startDate;
    }

    public function getName(): ?string
    {
        return null;
    }

    public function getActionId(): ?int
    {
        return $this->actionId;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }
}
