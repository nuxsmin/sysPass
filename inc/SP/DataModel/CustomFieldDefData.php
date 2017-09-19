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

use SP\Mgmt\CustomFields\CustomFieldTypes;

/**
 * Class CustomFieldDefData
 *
 * @package SP\DataModel
 */
class CustomFieldDefData extends CustomFieldBaseData implements DataModelInterface
{
    /**
     * @var int
     */
    public $customfielddef_module = 0;
    /**
     * @var string
     */
    public $typeName = '';
    /**
     * @var string
     */
    public $moduleName = '';
    /**
     * @var bool
     */
    public $required = false;
    /**
     * @var string
     */
    public $help = '';
    /**
     * @var bool
     */
    public $showInItemsList = false;

    /**
     * @return int
     */
    public function getCustomfielddefModule()
    {
        return $this->customfielddef_module;
    }

    /**
     * @param int $customfielddef_module
     */
    public function setCustomfielddefModule($customfielddef_module)
    {
        $this->customfielddef_module = $customfielddef_module;
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
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * @param string $moduleName
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    /**
     * @return string
     */
    public function getFormId()
    {
        return 'cf_' . strtolower(preg_replace('/\W*/', '', $this->name));
    }

    /**
     * @return boolean
     */
    public function isShowInItemsList()
    {
        return $this->showInItemsList;
    }

    /**
     * @param boolean $showInItemsList
     */
    public function setShowInItemsList($showInItemsList)
    {
        $this->showInItemsList = $showInItemsList;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
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
     * unserialize() checks for the presence of a function with the magic name __wakeup.
     * If present, this function can reconstruct any resources that the object may have.
     * The intended use of __wakeup is to reestablish any database connections that may have been lost during
     * serialization and perform other reinitialization tasks.
     *
     * @return void
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
     */
    public function __wakeup()
    {
        parent::__wakeup();

        $this->moduleName = CustomFieldTypes::getFieldsModules($this->getModule());
        $this->typeName = CustomFieldTypes::getFieldsTypes($this->getType(), true);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->customfielddef_id;
    }
}