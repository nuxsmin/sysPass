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

namespace SP\Domain\CustomField\Models;

use SP\Domain\Common\Models\Model;

/**
 * Class CustomFieldDefinition
 */
class CustomFieldDefinition extends Model
{
    protected ?int    $id          = null;
    protected ?string $name        = null;
    protected ?int    $moduleId    = null;
    protected ?int    $required    = null;
    protected ?string $help        = null;
    protected ?int    $showInList  = null;
    protected ?int    $typeId      = null;
    protected ?int    $isEncrypted = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    public function getRequired(): ?int
    {
        return $this->required;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function getShowInList(): ?int
    {
        return $this->showInList;
    }

    public function getTypeId(): ?int
    {
        return $this->typeId;
    }

    public function getIsEncrypted(): ?int
    {
        return $this->isEncrypted;
    }
}
