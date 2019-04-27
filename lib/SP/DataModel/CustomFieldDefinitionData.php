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

/**
 * Class CustomFieldDefData
 *
 * @package SP\DataModel
 */
class CustomFieldDefinitionData
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var int
     */
    public $moduleId;
    /**
     * @var string
     */
    public $field;
    /**
     * @var int
     */
    public $required;
    /**
     * @var string
     */
    public $help;
    /**
     * @var int
     */
    public $showInList;
    /**
     * @var int
     */
    public $typeId;
    /**
     * @var int
     */
    public $isEncrypted = 1;

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
     * @return int
     */
    public function getModuleId()
    {
        return (int)$this->moduleId;
    }

    /**
     * @param int $moduleId
     */
    public function setModuleId($moduleId)
    {
        $this->moduleId = (int)$moduleId;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return int
     */
    public function getRequired()
    {
        return (int)$this->required;
    }

    /**
     * @param int $required
     */
    public function setRequired($required)
    {
        $this->required = (int)$required;
    }

    /**
     * @return string
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * @param string $help
     */
    public function setHelp($help)
    {
        $this->help = $help;
    }

    /**
     * @return int
     */
    public function getShowInList()
    {
        return (int)$this->showInList;
    }

    /**
     * @param int $showInList
     */
    public function setShowInList($showInList)
    {
        $this->showInList = (int)$showInList;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        return (int)$this->typeId;
    }

    /**
     * @param int $typeId
     */
    public function setTypeId($typeId)
    {
        $this->typeId = (int)$typeId;
    }

    /**
     * @return int
     */
    public function getisEncrypted(): int
    {
        return (int)$this->isEncrypted;
    }

    /**
     * @param int $isEncrypted
     */
    public function setIsEncrypted(int $isEncrypted)
    {
        $this->isEncrypted = $isEncrypted;
    }
}