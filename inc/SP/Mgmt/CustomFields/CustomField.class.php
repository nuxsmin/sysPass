<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Mgmt\CustomFields;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\Crypt;
use SP\DataModel\CustomFieldData;
use SP\DataModel\CustomFieldDefData;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
use SP\Log\Log;
use SP\Core\SPException;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class CustomFields para la gestión de campos personalizados de los módulos
 *
 * @package SP
 */
class CustomField extends CustomFieldBase implements ItemInterface
{
    /**
     * @param CustomFieldData $itemData
     * @param int             $customFieldDefId
     * @throws SPException
     */
    public function __construct(CustomFieldData $itemData, $customFieldDefId = null)
    {
        if (!is_null($customFieldDefId)) {
            $field = CustomFieldDef::getItem()->getById($customFieldDefId)->getItemData();

            $itemData->setDefinitionId($customFieldDefId);
            $itemData->setModule($field->getModule());
            $itemData->setName($field->getName());
            $itemData->setType($field->getType());
        }

        $this->itemData = $itemData;
    }

    /**
     * @return mixed
     */
    public function update()
    {
        $exists = $this->checkIfExists();

        if ($this->itemData->getValue() !== '' && !$exists) {
            return $this->add();
        } elseif ($this->itemData->getValue() === '' && $exists) {
            return $this->delete($this->itemData->getId());
        }

        $cryptData = Crypt::encryptData($this->itemData->getValue());

        $query = /** @lang SQL */
            'UPDATE customFieldsData SET
            customfielddata_data = ?,
            customfielddata_iv = ?
            WHERE customfielddata_moduleId = ?
            AND customfielddata_itemId = ?
            AND customfielddata_defId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($cryptData['data']);
        $Data->addParam($cryptData['iv']);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($this->itemData->getId());
        $Data->addParam($this->itemData->getDefinitionId());

        return DB::getQuery($Data);
    }

    /**
     * Comprueba si el elemento tiene campos personalizados con datos
     *
     * @return bool
     */
    protected function checkIfExists()
    {
        $query = /** @lang SQL */
            'SELECT customfielddata_id
            FROM customFieldsData
            WHERE customfielddata_moduleId = ?
            AND customfielddata_itemId = ?
            AND customfielddata_defId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($this->itemData->getId());
        $Data->addParam($this->itemData->getDefinitionId());

        DB::getQuery($Data);

        return (DB::$lastNumRows >= 1);
    }

    /**
     * @return mixed
     */
    public function add()
    {
        if ($this->itemData->getValue() === '') {
            return true;
        }

        $cryptData = Crypt::encryptData($this->itemData->getValue());

        $query = /** @lang SQL */
            'INSERT INTO customFieldsData SET
            customfielddata_itemId = ?,
            customfielddata_moduleId = ?,
            customfielddata_defId = ?,
            customfielddata_data = ?,
            customfielddata_iv = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getId());
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($this->itemData->getDefinitionId());
        $Data->addParam($cryptData['data']);
        $Data->addParam($cryptData['iv']);

        $queryRes = DB::getQuery($Data);

        return $queryRes;
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM customFieldsData
            WHERE customfielddata_itemId = ?
            AND customfielddata_moduleId = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->addParam($this->itemData->getCustomfielddataModuleId());

        $queryRes = DB::getQuery($Data);

        return $queryRes;
    }

    /**
     * @param $id int
     * @return CustomFieldData[]
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT customfielddata_id,
            customfielddef_id,
            customfielddata_data,
            customfielddata_iv,
            customfielddef_field
            FROM customFieldsData
            JOIN customFieldsDef ON customfielddata_defId = customfielddef_id
            WHERE customfielddef_module = ?
            AND customfielddata_itemId = ?
            UNION
            SELECT
            0 as customfielddata_id,
            customfielddef_id,
            "" as customfielddata_data,
            "" as customfielddata_iv,
            customfielddef_field
            FROM customFieldsDef
            WHERE customfielddef_module = ?
            AND customfielddef_id NOT IN
            (SELECT customfielddef_id
            FROM customFieldsData
            JOIN customFieldsDef ON customfielddata_defId = customfielddef_id
            WHERE customfielddef_module = ?
            AND customfielddata_itemId = ?)
            ORDER BY customfielddef_id';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\CustomFieldData');
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($id);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($id);

        DB::setReturnArray();

        $queryRes = DB::getResults($Data);

        $customFields = [];
        $customFieldsHash = '';

        foreach ($queryRes as $CustomFieldData) {
            /**
             * @var CustomFieldData    $CustomFieldData
             * @var CustomFieldDefData $fieldDef
             */
            $fieldDef = unserialize($CustomFieldData->getCustomfielddefField());

            if (get_class($fieldDef) === '__PHP_Incomplete_Class') {
                $fieldDef = Util::castToClass('SP\DataModel\CustomFieldDefData', $fieldDef);
            }

            $CustomFieldData->setDefinition($fieldDef);
            $CustomFieldData->setDefinitionId($CustomFieldData->getCustomfielddefId());
            $CustomFieldData->setTypeName(CustomFieldTypes::getFieldsTypes($fieldDef->getType()));
            $CustomFieldData->setValue($this->unencryptData($CustomFieldData));

            $customFields[] = $CustomFieldData;
            $customFieldsHash .= $CustomFieldData->getValue();
        }

        $customFields['hash'] = md5($customFieldsHash);

        return $customFields;
    }

    /**
     * Desencriptar y formatear los datos del campo
     *
     * @param CustomFieldData $CustomFieldData
     * @return string
     */
    protected function unencryptData(CustomFieldData $CustomFieldData)
    {
        if ($CustomFieldData->getCustomfielddataData() !== '') {
            return $this->formatValue(Crypt::getDecrypt($CustomFieldData->getCustomfielddataData(), $CustomFieldData->getCustomfielddataIv()));
        }

        return '';
    }

    /**
     * Formatear el valor del campo
     *
     * @param $value string El valor del campo
     * @return string
     */
    protected function formatValue($value)
    {
        if (preg_match('#https?://#', $value)) {
            return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
        }

        return $value;
    }

    /**
     * @return CustomFieldDefData[]|array
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT customfielddef_id,
            customfielddef_field
            FROM customFieldsDef
            WHERE customfielddef_module = ?';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\CustomFieldDefData');
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getModule());

        DB::setReturnArray();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array('hash' => '');
        }

        foreach ($queryRes as $CustomFieldDef) {
            /**
             * @var CustomFieldDefData $CustomFieldDef
             * @var CustomFieldDefData $fieldDef
             */

            $fieldDef = unserialize($CustomFieldDef->getCustomfielddefField());

            if (get_class($fieldDef) === '__PHP_Incomplete_Class') {
                $fieldDef = Util::castToClass('SP\DataModel\CustomFieldDefData', $fieldDef);
            }

            $CustomFieldData = new CustomFieldData();
            $CustomFieldData->setDefinition($fieldDef);
            $CustomFieldData->setId($CustomFieldDef->getCustomfielddefId());
            $CustomFieldData->setTypeName(CustomFieldTypes::getFieldsTypes($fieldDef->getType()));

            $fields[] = $fieldDef;
        }

        $customFields['hash'] = '';

        return $customFields;
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnAdd()
    {
        // TODO: Implement checkDuplicatedOnAdd() method.
    }
}