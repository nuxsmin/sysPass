<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\DataModel;

defined('APP_ROOT') || die();

/**
 * Class ClientData
 *
 * @package SP\DataModel
 */
class ClientData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int
     */
    public $id = 0;
    /**
     * @var string
     */
    public $name = '';
    /**
     * @var string
     */
    public $description = '';
    /**
     * @var string
     */
    public $hash = '';
    /**
     * @var int
     */
    public $isGlobal = 0;

    /**
     * CustomerData constructor.
     *
     * @param int    $id
     * @param string $name
     * @param string $description
     */
    public function __construct($id = null, $name = null, $description = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return int
     */
    public function getIsGlobal()
    {
        return (int)$this->isGlobal;
    }

    /**
     * @param int $isGlobal
     */
    public function setIsGlobal($isGlobal)
    {
        $this->isGlobal = (int)$isGlobal;
    }
}