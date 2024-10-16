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

namespace SP\Domain\ItemPreset\Models;

use SP\Domain\Common\Attributes\Hydratable;
use SP\Domain\Common\Models\HydratableModel;
use SP\Domain\Common\Models\Model;
use SP\Domain\Common\Models\SerializedModel;

/**
 * Class ItemPreset
 */
#[Hydratable('data', [
    Password::class,
    AccountPrivate::class,
    AccountPermission::class,
    SessionTimeout::class
])]
class ItemPreset extends Model implements HydratableModel
{
    use SerializedModel;

    public const TABLE = 'ItemPreset';

    protected ?int    $id            = null;
    protected ?string $type          = null;
    protected ?int    $userId        = null;
    protected ?int    $userGroupId   = null;
    protected ?int    $userProfileId = null;
    protected ?int    $fixed         = null;
    protected ?int    $priority      = null;
    protected ?string $data          = null;

    public function getUserGroupId(): ?int
    {
        return $this->userGroupId;
    }

    public function getUserProfileId(): ?int
    {
        return $this->userProfileId;
    }

    public function getFixed(): ?int
    {
        return $this->fixed;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
}
