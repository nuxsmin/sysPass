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
 * Class CustomFieldData
 *
 * @package SP\DataModel
 */
class CustomFieldBaseData extends DataModelBase
{
    /**
     * @var int
     */
    public $customfielddata_id = 0;
    /**
     * @var int
     */
    public $customfielddef_id = 0;
    /**
     * @var string
     */
    public $customfielddata_data = '';
    /**
     * @var string
     */
    public $customfielddata_key = '';
    /**
     * @var string
     */
    public $customfielddef_field = '';
    /**
     * @var string
     */
    public $name = '';
    /**
     * @var int
     */
    public $type = 0;
    /**
     * @var int
     */
    public $module = 0;
    /**
     * @var int
     */
    public $id = 0;

    /**
     * CustomFieldBaseData constructor.
     *
     * @param int $module
     * @param int $id
     */
    public function __construct($module = 0, $id = 0)
    {
        $this->module = $module;
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getCustomfielddataId()
    {
        return $this->customfielddata_id;
    }

    /**
     * @param int $customfielddata_id
     */
    public function setCustomfielddataId($customfielddata_id)
    {
        $this->customfielddata_id = $customfielddata_id;
    }

    /**
     * @return int
     */
    public function getCustomfielddefId()
    {
        return $this->customfielddef_id;
    }

    /**
     * @param int $customfielddef_id
     */
    public function setCustomfielddefId($customfielddef_id)
    {
        $this->customfielddef_id = $customfielddef_id;
    }

    /**
     * @return string
     */
    public function getCustomfielddataData()
    {
        return $this->customfielddata_data;
    }

    /**
     * @param string $customfielddata_data
     */
    public function setCustomfielddataData($customfielddata_data)
    {
        $this->customfielddata_data = $customfielddata_data;
    }

    /**
     * @return string
     */
    public function getCustomfielddataKey()
    {
        return $this->customfielddata_key;
    }

    /**
     * @param string $customfielddata_key
     */
    public function setCustomfielddataKey($customfielddata_key)
    {
        $this->customfielddata_key = $customfielddata_key;
    }

    /**
     * @return string
     */
    public function getCustomfielddefField()
    {
        return $this->customfielddef_field;
    }

    /**
     * @param string $customfielddef_field
     */
    public function setCustomfielddefField($customfielddef_field)
    {
        $this->customfielddef_field = $customfielddef_field;
    }

    /**
     * @return int
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param int $module
     */
    public function setModule($module)
    {
        $this->module = $module;
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
        // Para realizar la conversión de nombre de propiedades que empiezan por _
        foreach (get_object_vars($this) as $name => $value) {
            if ($name[0] === '_') {
                $newName = substr($name, 1);
                $this->$newName = $value;
            }
        }
    }


}