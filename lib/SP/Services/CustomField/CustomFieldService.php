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
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\DataModel\CustomFieldDefData;
use SP\DataModel\ItemSearchData;
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
     * @param int   $itemId
     * @param int   $moduleId
     * @throws \SP\Core\Exceptions\SPException
     */
    public function addCustomFieldData($customFields, $itemId, $moduleId)
    {
        if (is_array($customFields)) {
            $customFieldData = new CustomFieldData();
            $customFieldData->setId($itemId);
            $customFieldData->setModule($moduleId);

            try {
                foreach ($customFields as $id => $value) {
                    $customFieldData->setDefinitionId($id);
                    $customFieldData->setValue($value);

                    $this->create($customFieldData);
                }
            } catch (CryptoException $e) {
                throw new SPException(SPException::SP_ERROR, __u('Error interno'));
            }
        }
    }

    /**
     * Creates an item
     *
     * @param mixed $itemData
     * @return bool
     * @throws CryptoException
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function create($itemData)
    {
        if ($itemData->getValue() === '') {
            return true;
        }

        $sessionKey = CryptSession::getSessionKey();
        $securedKey = Crypt::makeSecuredKey($sessionKey);

        if (strlen($securedKey) > 1000) {
            throw new QueryException(SPException::SP_ERROR, __u('Error interno'));
        }

        $query = /** @lang SQL */
            'INSERT INTO customFieldsData SET
            customfielddata_itemId = ?,
            customfielddata_moduleId = ?,
            customfielddata_defId = ?,
            customfielddata_data = ?,
            customfielddata_key = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getModule());
        $Data->addParam($itemData->getDefinitionId());
        $Data->addParam(Crypt::encrypt($itemData->getValue(), $securedKey, $sessionKey));
        $Data->addParam($securedKey);

        return DbWrapper::getQuery($Data);
    }

    /**
     * Actualizar los datos de los campos personalizados del módulo
     *
     * @param array $customFields
     * @param int   $itemId
     * @param int   $moduleId
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateCustomFieldData($customFields, $itemId, $moduleId)
    {
        if (is_array($customFields)) {
            $customFieldData = new CustomFieldData();
            $customFieldData->setId($itemId);
            $customFieldData->setModule($moduleId);

            try {
                foreach ($customFields as $id => $value) {
                    $customFieldData->setDefinitionId($id);
                    $customFieldData->setValue($value);

                    $this->update($customFieldData);
                }
            } catch (CryptoException $e) {
                throw new SPException(SPException::SP_ERROR, __u('Error interno'));
            }
        }
    }

    /**
     * Updates an item
     *
     * @param mixed $itemData
     * @return mixed
     * @throws CryptoException
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function update($itemData)
    {
        $exists = $this->checkExists($itemData);

        // Deletes item's custom field data if value is left blank
        if ($exists && $itemData->getValue() === '') {
            return $this->delete($itemData->getId());
        }

        // Create item's custom field data if value is set
        if (!$exists && $itemData->getValue() !== '') {
            return $this->create($itemData);
        }

        $sessionKey = CryptSession::getSessionKey();
        $securedKey = Crypt::makeSecuredKey($sessionKey);

        if (strlen($securedKey) > 1000) {
            throw new QueryException(SPException::SP_ERROR, __u('Error interno'));
        }

        $query = /** @lang SQL */
            'UPDATE customFieldsData SET
            customfielddata_data = ?,
            customfielddata_key = ?
            WHERE customfielddata_moduleId = ?
            AND customfielddata_itemId = ?
            AND customfielddata_defId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(Crypt::encrypt($itemData->getValue(), $securedKey, $sessionKey));
        $Data->addParam($securedKey);
        $Data->addParam($itemData->getModule());
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getDefinitionId());

        return DbWrapper::getQuery($Data);
    }

    /**
     * Comprueba si el elemento tiene campos personalizados con datos
     *
     * @param CustomFieldData $itemData
     * @return bool
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    protected function checkExists($itemData)
    {
        $query = /** @lang SQL */
            'SELECT customfielddata_id
            FROM customFieldsData
            WHERE customfielddata_moduleId = ?
            AND customfielddata_itemId = ?
            AND customfielddata_defId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getModule());
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getDefinitionId());

        DbWrapper::getQuery($Data);

        return ($Data->getQueryNumRows() >= 1);
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
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int $id
     * @param int $moduleId
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteCustomFieldData($id, $moduleId)
    {
        $query = /** @lang SQL */
            'DELETE FROM customFieldsData
            WHERE customfielddata_itemId = ?
            AND customfielddata_moduleId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->addParam($moduleId);

        return DbWrapper::getQuery($Data);
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

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     * @return bool
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return bool
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        // TODO: Implement checkDuplicatedOnAdd() method.
    }
}