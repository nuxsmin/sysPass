<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Mgmt\CustomFields;

defined('APP_ROOT') || die();

use SP\Core\OldCrypt;
use SP\DataModel\CustomFieldData;
use SP\DataModel\CustomFieldDefData;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
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
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function __construct($itemData, $customFieldDefId = null)
    {
        $this->setDataModel(CustomFieldData::class);

        parent::__construct($itemData);

        if (null !== $customFieldDefId) {
            $field = CustomFieldDef::getItem()->getById($customFieldDefId);

            $itemData->setDefinitionId($customFieldDefId);
            $itemData->setModule($field->getModule());
            $itemData->setName($field->getName());
            $itemData->setType($field->getType());
        }

        $this->itemData = $itemData;
    }

    /**
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        $exists = $this->checkExists();

        if (!$exists && $this->itemData->getValue() !== '') {
            return $this->add();
        } elseif ($exists && $this->itemData->getValue() === '') {
            return $this->delete($this->itemData->getId());
        }

        $securedKey = Crypt\Crypt::makeSecuredKey(Crypt\Session::getSessionKey());

        $query = /** @lang SQL */
            'UPDATE customFieldsData SET
            customfielddata_data = ?,
            customfielddata_iv = ?
            WHERE customfielddata_moduleId = ?
            AND customfielddata_itemId = ?
            AND customfielddata_defId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(Crypt\Crypt::encrypt($this->itemData->getValue(), $securedKey));
        $Data->addParam($securedKey);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($this->itemData->getId());
        $Data->addParam($this->itemData->getDefinitionId());

        return DB::getQuery($Data);
    }

    /**
     * Comprueba si el elemento tiene campos personalizados con datos
     *
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function checkExists()
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

        return ($Data->getQueryNumRows() >= 1);
    }

    /**
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    public function add()
    {
        if ($this->itemData->getValue() === '') {
            return true;
        }

        $securedKey = Crypt\Crypt::makeSecuredKey(Crypt\Session::getSessionKey());

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
        $Data->addParam(Crypt\Crypt::encrypt($this->itemData->getValue(), $securedKey));
        $Data->addParam($securedKey);

        return DB::getQuery($Data);
    }

    /**
     * @param $id int
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
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

        return DB::getQuery($Data);
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
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($id);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($id);

        /** @var CustomFieldData[] $queryRes */
        $queryRes = DB::getResultsArray($Data);

        $customFields = [];

        foreach ($queryRes as $CustomFieldData) {
            /** @var CustomFieldDefData $fieldDef */
            $fieldDef = Util::castToClass(CustomFieldDefData::class, $CustomFieldData->getCustomfielddefField());

            $CustomFieldData->setDefinition($fieldDef);
            $CustomFieldData->setDefinitionId($CustomFieldData->getCustomfielddefId());
            $CustomFieldData->setTypeName(CustomFieldTypes::getFieldsTypes($fieldDef->getType()));
            $CustomFieldData->setValue($this->unencryptData($CustomFieldData));

            $customFields[] = $CustomFieldData;
        }

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
            $securedKey = Crypt\Crypt::unlockSecuredKey($CustomFieldData->getCustomfielddataIv(), Crypt\Session::getSessionKey());

            return $this->formatValue(Crypt\Crypt::decrypt($CustomFieldData->getCustomfielddataData(), $securedKey));
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
        $Data->setMapClassName(CustomFieldDefData::class);
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getModule());

        /** @var CustomFieldDefData[] $queryRes */
        $queryRes = DB::getResultsArray($Data);

        if (count($queryRes) === 0) {
            return ['hash' => ''];
        }

        foreach ($queryRes as $CustomFieldDef) {
            /** @var CustomFieldDefData $fieldDef */
            $fieldDef = Util::castToClass(CustomFieldDefData::class, $CustomFieldDef->getCustomfielddefField());

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

    /**
     * Eliminar elementos en lote
     *
     * @param array $ids
     * @return $this
     */
    public function deleteBatch(array $ids)
    {
        // TODO: Implement deleteBatch() method.
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return mixed
     */
    public function getByIdBatch(array $ids)
    {
        // TODO: Implement getByIdBatch() method.
    }
}