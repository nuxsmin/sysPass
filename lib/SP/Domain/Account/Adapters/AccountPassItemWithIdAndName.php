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

namespace SP\Domain\Account\Adapters;

use SP\Domain\Common\Models\ItemWithIdAndNameModel;
use SP\Domain\Common\Models\Model;

/**
 * Class AccountPassItemWithIdAndName
 */
class AccountPassItemWithIdAndName extends Model implements ItemWithIdAndNameModel
{
    protected ?int    $id        = null;
    protected ?string $name      = null;
    protected ?string $login     = null;
    protected ?string $pass      = null;
    protected ?string $key       = null;
    protected ?int    $parentId  = null;
    protected ?string $mPassHash = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getMPassHash(): ?string
    {
        return $this->mPassHash;
    }
}
