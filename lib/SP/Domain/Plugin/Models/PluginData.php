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

namespace SP\Domain\Plugin\Models;

use SP\Domain\Common\Attributes\Encryptable;
use SP\Domain\Common\Attributes\Hydratable;
use SP\Domain\Common\Models\HydratableModel;
use SP\Domain\Common\Models\Model;
use SP\Domain\Common\Models\SerializedModel;
use SP\Domain\Core\Models\EncryptedModel;
use SP\Domain\Plugin\Ports\PluginDataStorage;

/**
 * Class PluginDataModel
 */
#[Encryptable('data', 'key')]
#[Hydratable('data', [PluginDataStorage::class])]
final class PluginData extends Model implements HydratableModel
{
    use SerializedModel;
    use EncryptedModel;

    protected ?string $name   = null;
    protected ?int    $itemId = null;
    protected ?string $data   = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    public function getData(): ?string
    {
        return $this->data;
    }
}
