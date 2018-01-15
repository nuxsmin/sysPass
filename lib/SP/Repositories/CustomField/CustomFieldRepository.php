<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories\CustomField;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemInterface;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class CustomFieldRepository
 *
 * @package SP\Services
 */
class CustomFieldRepository extends Repository implements RepositoryItemInterface
{
    /**
     * Returns the form Id for a given name
     *
     * @param $name
     * @return string
     */
    public static function getFormIdForName($name)
    {
        return 'cf_' . strtolower(preg_replace('/\W*/', '', $name));
    }

    /**
     * Desencriptar y formatear los datos del campo
     *
     * @param CustomFieldData $CustomFieldData
     * @return string
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public static function unencryptData(CustomFieldData $CustomFieldData)
    {
        if ($CustomFieldData->getData() !== '') {
            $securedKey = Crypt::unlockSecuredKey($CustomFieldData->getKey(), CryptSession::getSessionKey());

            return self::formatValue(Crypt::decrypt($CustomFieldData->getData(), $securedKey));
        }

        return '';
    }

    /**
     * Formatear el valor del campo
     *
     * @param $value string El valor del campo
     * @return string
     */
    public static function formatValue($value)
    {
        if (preg_match('#https?://#', $value)) {
            return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
        }

        return $value;
    }

    /**
     * Updates an item
     *
     * @param CustomFieldData $itemData
     * @return bool
     * @throws CryptoException
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function update($itemData)
    {
        $exists = $this->checkExists($itemData);

        // Deletes item's custom field data if value is left blank
        if ($exists && $itemData->getData() === '') {
            return $this->delete($itemData->getId());
        }

        // Create item's custom field data if value is set
        if (!$exists && $itemData->getData() !== '') {
            return $this->create($itemData);
        }

        $sessionKey = CryptSession::getSessionKey();
        $securedKey = Crypt::makeSecuredKey($sessionKey);

        if (strlen($securedKey) > 1000) {
            throw new QueryException(SPException::SP_ERROR, __u('Error interno'));
        }

        $query = /** @lang SQL */
            'UPDATE CustomFieldData SET
            `data` = ?,
            `key` = ?
            WHERE moduleId = ?
            AND itemId = ?
            AND definitionId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(Crypt::encrypt($itemData->getData(), $securedKey, $sessionKey));
        $Data->addParam($securedKey);
        $Data->addParam($itemData->getModuleId());
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getDefinitionId());

        return DbWrapper::getQuery($Data, $this->db);
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
            'SELECT id
            FROM CustomFieldData
            WHERE moduleId = ?
            AND itemId = ?
            AND definitionId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getModuleId());
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getDefinitionId());

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() >= 1;
    }

    /**
     * Deletes an item
     *
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Creates an item
     *
     * @param CustomFieldData $itemData
     * @return bool
     * @throws CryptoException
     * @throws QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function create($itemData)
    {
        if ($itemData->getData() === '') {
            return true;
        }

        $sessionKey = CryptSession::getSessionKey();
        $securedKey = Crypt::makeSecuredKey($sessionKey);

        if (strlen($securedKey) > 1000) {
            throw new QueryException(SPException::SP_ERROR, __u('Error interno'));
        }

        $query = /** @lang SQL */
            'INSERT INTO CustomFieldData SET itemId = ?, moduleId = ?, definitionId = ?, `data` = ?, `key` = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($itemData->getId());
        $Data->addParam($itemData->getModuleId());
        $Data->addParam($itemData->getDefinitionId());
        $Data->addParam(Crypt::encrypt($itemData->getData(), $securedKey, $sessionKey));
        $Data->addParam($securedKey);

        return DbWrapper::getQuery($Data, $this->db);
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
            'DELETE FROM CustomFieldData
            WHERE itemId = ?
            AND moduleId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->addParam($moduleId);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return void
     */
    public function getById($id)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return void
     */
    public function getByIdBatch(array $ids)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return void
     */
    public function deleteByIdBatch(array $ids)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return void
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Returns the module's item for given id
     *
     * @param $moduleId
     * @param $itemId
     * @return array
     */
    public function getForModuleById($moduleId, $itemId)
    {
        $query = /** @lang SQL */
            'SELECT CFD.name AS definitionName,
            CFD.id AS definitionId,
            CFD.moduleId,
            CFD.required,
            CFD.showInList,
            CFD.help,
            CFD2.data,
            CFD2.key,
            CFT.id AS typeId,
            CFT.name AS typeName,
            CFT.text AS typeText
            FROM CustomFieldDefinition CFD
            LEFT JOIN CustomFieldData CFD2 ON CFD2.definitionId = CFD.id
            INNER JOIN CustomFieldType CFT ON CFT.id = CFD.typeId
            WHERE CFD.moduleId = ?
            AND (CFD2.itemId = ? OR CFD2.definitionId IS NULL) 
            ORDER BY CFD.id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($moduleId);
        $Data->addParam($itemId);

        return DbWrapper::getResultsArray($Data, $this->db);
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     * @return void
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Unimplemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return void
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Unimplemented');
    }
}