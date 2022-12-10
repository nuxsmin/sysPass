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

namespace SP\Infrastructure\Plugin\Repositories;

use SP\Domain\Common\Adapters\DataModelInterface;
use SP\Domain\Common\Models\Model;

/**
 * Class PluginData
 *
 * @package SP\DataModel
 */
class PluginModel extends Model implements DataModelInterface
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $data;
    /**
     * @var int
     */
    protected $enabled = 0;
    /**
     * @var int
     */
    protected $available = 1;
    /**
     * @var string
     */
    protected $versionLevel;

    public function getId(): ?int
    {
        return (int)$this->id;
    }

    /**
     * @param  int  $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param  string  $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param  string  $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getEnabled()
    {
        return (int)$this->enabled;
    }

    /**
     * @param  int  $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (int)$enabled;
    }

    /**
     * @return int
     */
    public function getAvailable()
    {
        return (int)$this->available;
    }

    /**
     * @param  int  $available
     */
    public function setAvailable($available)
    {
        $this->available = (int)$available;
    }

    /**
     * @return string
     */
    public function getVersionLevel()
    {
        return $this->versionLevel;
    }

    /**
     * @param  string  $versionLevel
     */
    public function setVersionLevel(string $versionLevel)
    {
        $this->versionLevel = $versionLevel;
    }
}
