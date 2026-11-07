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

namespace SP\Domain\CustomField\Models;

use SP\Domain\Common\Models\Model;

/**
 * Class CustomFieldData
 */
class CustomFieldData extends Model
{
    public const TABLE = 'CustomFieldData';

    protected ?int    $moduleId     = null;
    protected ?int    $itemId       = null;
    protected ?int    $definitionId = null;
    protected ?string $data         = null;
    protected ?string $key          = null;

    public function getModuleId(): ?int
    {
        return $this->moduleId;
    }

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function getDefinitionId(): ?int
    {
        return $this->definitionId;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}
