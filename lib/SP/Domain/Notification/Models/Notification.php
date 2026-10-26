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

namespace SP\Domain\Notification\Models;

use SP\Domain\Common\Models\ItemWithIdAndNameModel;
use SP\Domain\Common\Models\Model;

/**
 * Class Notification
 */
class Notification extends Model implements ItemWithIdAndNameModel
{
    public const TABLE = 'Notification';

    protected ?int    $id          = null;
    protected ?string $type        = null;
    protected ?string $component   = null;
    protected ?string $description = null;
    protected ?int    $date        = null;
    protected ?bool   $checked     = false;
    protected ?int    $userId      = null;
    protected ?bool   $sticky      = false;
    protected ?bool   $onlyAdmin   = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getComponent(): ?string
    {
        return $this->component;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDate(): ?int
    {
        return $this->date;
    }

    public function isChecked(): ?bool
    {
        return $this->checked;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function isSticky(): ?bool
    {
        return $this->sticky;
    }

    public function isOnlyAdmin(): ?bool
    {
        return $this->onlyAdmin;
    }

    public function getName(): ?string
    {
        return $this->component;
    }
}
