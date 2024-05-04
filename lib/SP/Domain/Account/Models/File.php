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

namespace SP\Domain\Account\Models;

use SP\Domain\Common\Models\ItemWithIdAndNameModel;
use SP\Domain\Common\Models\Model;

/**
 * Class File
 */
class File extends Model implements ItemWithIdAndNameModel
{
    public const TABLE = 'AccountFile';

    protected ?int    $id        = null;
    protected ?int    $accountId = null;
    protected ?string $name      = null;
    protected ?string $type      = null;
    protected ?string $content   = null;
    protected ?string $extension = null;
    protected ?string $thumb     = null;
    protected ?int    $size      = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function getThumb(): ?string
    {
        return $this->thumb;
    }

    /**
     * @param string $thumb
     */
    public function setThumb(string $thumb): void
    {
        $this->thumb = $thumb;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getRoundSize(): float
    {
        if (null === $this->size) {
            return 0.0;
        }

        return round($this->size / 1000, 2);
    }
}
