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

namespace SP\Domain\Plugin\Models;

use SP\Domain\Common\Models\ItemWithIdAndNameModel;
use SP\Domain\Common\Models\Model;

/**
 * Class PluginModel
 */
class Plugin extends Model implements ItemWithIdAndNameModel
{
    protected ?int    $id           = null;
    protected ?string $name         = null;
    protected ?string $data         = null;
    protected ?bool   $enabled      = null;
    protected ?string $versionLevel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function getVersionLevel(): ?string
    {
        return $this->versionLevel;
    }
}
