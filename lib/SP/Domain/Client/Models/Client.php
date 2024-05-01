<?php
declare(strict_types=1);
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

namespace SP\Domain\Client\Models;

use SP\Domain\Common\Models\ItemWithIdAndNameModel;
use SP\Domain\Common\Models\Model;

/**
 * Class Client
 */
class Client extends Model implements ItemWithIdAndNameModel
{
    public const TABLE = 'Client';
    protected ?int $isGlobal = null;
    protected ?int    $id          = null;
    protected ?string $name        = null;
    protected ?string $description = null;
    protected ?string $hash        = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }


    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function getIsGlobal(): ?int
    {
        return $this->isGlobal;
    }
}
