<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Html\Html;

/**
 * Class CustomFieldData
 *
 * @package SP\DataModel
 */
class CustomFieldData extends CustomFieldBaseData
{
    /**
     * @var int
     */
    public $customfielddata_itemId = 0;
    /**
     * @var int
     */
    public $customfielddata_moduleId = 0;
    /**
     * @var int
     */
    public $customfielddata_defId = 0;
    /**
     * @var string
     */
    public $typeName = '';
    /**
     * @var string
     */
    public $value = '';
    /**
     * @var int
     */
    public $definitionId = 0;
    /**
     * @var CustomFieldDefData
     */
    protected $definition;

    /**
     * @return int
     */
    public function getCustomfielddataItemId()
    {
        return $this->customfielddata_itemId;
    }

    /**
     * @param int $customfielddata_itemId
     */
    public function setCustomfielddataItemId($customfielddata_itemId)
    {
        $this->customfielddata_itemId = $customfielddata_itemId;
    }

    /**
     * @return int
     */
    public function getCustomfielddataModuleId()
    {
        return $this->customfielddata_moduleId;
    }

    /**
     * @param int $customfielddata_moduleId
     */
    public function setCustomfielddataModuleId($customfielddata_moduleId)
    {
        $this->customfielddata_moduleId = $customfielddata_moduleId;
    }

    /**
     * @return int
     */
    public function getCustomfielddataDefId()
    {
        return $this->customfielddata_defId;
    }

    /**
     * @param int $customfielddata_defId
     */
    public function setCustomfielddataDefId($customfielddata_defId)
    {
        $this->customfielddata_defId = $customfielddata_defId;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * @param string $typeName
     */
    public function setTypeName($typeName)
    {
        $this->typeName = $typeName;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getCleanValue()
    {
        return Html::sanitize($this->value);
    }

    /**
     * @return string
     */
    public function getSafeHtmlValue()
    {
        return htmlspecialchars($this->value, ENT_QUOTES);
    }

    /**
     * @return int
     */
    public function getDefinitionId()
    {
        return $this->definitionId;
    }

    /**
     * @param int $definitionId
     */
    public function setDefinitionId($definitionId)
    {
        $this->definitionId = $definitionId;
    }

    /**
     * @return CustomFieldDefData
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param CustomFieldDefData $definition
     */
    public function setDefinition(CustomFieldDefData $definition)
    {
        $this->definition = $definition;
    }

}