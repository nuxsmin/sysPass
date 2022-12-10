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

use SP\Domain\Common\Adapters\DataModelInterface;
use SP\Domain\Common\Models\Model;

defined('APP_ROOT') || die();

/**
 * Class ClientData
 *
 * @package SP\DataModel
 */
class ClientData extends Model implements DataModelInterface
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $description = null;
    public ?string $hash = null;
    public ?int $isGlobal = null;

    public function __construct(
        ?int    $id = null,
        ?string $name = null,
        ?string $description = null
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function getIsGlobal(): ?int
    {
        return $this->isGlobal;
    }

    public function setIsGlobal(?int $isGlobal): void
    {
        $this->isGlobal = $isGlobal;
    }
}
