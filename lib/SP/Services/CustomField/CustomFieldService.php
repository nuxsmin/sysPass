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

namespace SP\Services\CustomField;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\DataModel\CustomFieldDefData;
use SP\DataModel\ItemSearchData;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\CustomFields\CustomFieldTypes;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Util;
use SP\Core\Crypt\Session as CryptSession;

/**
 * Class CustomFieldService
 *
 * @package SP\Services
 */
class CustomFieldService extends Service implements ServiceItemInterface
{
    /**
     * Guardar los datos de los campos personalizados del módulo
     *
     * @param array $customFields
     * @param int   $id
     * @param int   $moduleId
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addCustomFieldData($customFields, $id, $moduleId)
    {
        if (is_array($customFields)) {
            $customFieldData = new CustomFieldData();
            $customFieldData->setId($id);
            $customFieldData->setModule($moduleId);

            $this->addItemCustomFields($customFields, $customFieldData);
        }
    }

    /**
     * Crear los campos personalizados de un elemento
     *
     * @param array           $customFields
     * @param CustomFieldData $CustomFieldData
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function addItemCustomFields(array &$customFields, CustomFieldData $CustomFieldData)
    {
        try {
            foreach ($customFields as $id => $value) {
                $CustomFieldData->setDefinitionId($id);
                $CustomFieldData->setValue($value);

                CustomField::getItem($CustomFieldData)->add();
            }
        } catch (CryptoException $e) {
            throw new SPException(SPException::SP_ERROR, __('Error interno', false));
        }
    }

    /**
     * Actualizar los datos de los campos personalizados del módulo
     *
     * @param array $customFields
     * @param int   $id
     * @param int   $moduleId
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateCustomFieldData($customFields, $id, $moduleId)
    {
        if (is_array($customFields)) {
            $customFieldData = new CustomFieldData();
            $customFieldData->setId($id);
            $customFieldData->setModule($moduleId);

            $this->updateItemCustomFields($customFields, $customFieldData);
        }
    }

    /**
     * Actualizar los campos personalizados de un elemento
     *
     * @param array           $customFields
     * @param CustomFieldData $CustomFieldData
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function updateItemCustomFields(array &$customFields, CustomFieldData $CustomFieldData)
    {
        try {
            foreach ($customFields as $id => $value) {
                $CustomFieldData->setDefinitionId($id);
                $CustomFieldData->setValue($value);

                CustomField::getItem($CustomFieldData)->update();
            }
        } catch (CryptoException $e) {
            throw new SPException(SPException::SP_ERROR, __('Error interno', false));
        }
    }

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int|array $id
     * @param int       $moduleId
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteCustomFieldData($id, $moduleId)
    {
        $customFieldData = new CustomFieldData();
        $customFieldData->setId($id);
        $customFieldData->setModule($moduleId);

        if (is_array($id)) {
            CustomField::getItem($customFieldData)->deleteBatch($id);
        } else {
            CustomField::getItem($customFieldData)->delete($id);
        }
    }

    /**
     * Creates an item
     *
     * @return mixed
     */
    public function create()
    {
        // TODO: Implement create() method.
    }

    /**
     * Updates an item
     *
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        // TODO: Implement update() method.
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     */
    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        // TODO: Implement getByIdBatch() method.
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return $this
     */
    public function deleteByIdBatch(array $ids)
    {
        // TODO: Implement deleteByIdBatch() method.
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return bool
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @return bool
     */
    public function checkDuplicatedOnAdd()
    {
        // TODO: Implement checkDuplicatedOnAdd() method.
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        // TODO: Implement search() method.
    }

    /**
     * Returns the module's item for given id
     *
     * @param $moduleId
     * @param $itemId
     * @return array
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public function getForModuleById($moduleId, $itemId)
    {
        $query = /** @lang SQL */
            'SELECT customfielddata_id,
            customfielddef_id,
            customfielddata_data,
            customfielddata_key,
            customfielddef_field
            FROM customFieldsDef a
            LEFT JOIN customFieldsData b ON b.customfielddata_defId = a.customfielddef_id
            WHERE customfielddef_module = ?
            AND (customfielddata_itemId = ? OR customfielddata_defId IS NULL) 
            ORDER BY customfielddef_id';

        $Data = new QueryData();
        $Data->setMapClassName(CustomFieldData::class);
        $Data->setQuery($query);
        $Data->addParam($moduleId);
        $Data->addParam($itemId);

        /** @var CustomFieldData[] $queryRes */
        $queryRes = DbWrapper::getResultsArray($Data);

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
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    protected function unencryptData(CustomFieldData $CustomFieldData)
    {
        if ($CustomFieldData->getCustomfielddataData() !== '') {
            $securedKey = Crypt::unlockSecuredKey($CustomFieldData->getCustomfielddataKey(), CryptSession::getSessionKey());

            return $this->formatValue(Crypt::decrypt($CustomFieldData->getCustomfielddataData(), $securedKey));
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
}