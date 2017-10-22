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

namespace SP\Services;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\CustomFields\CustomFieldsUtil;

/**
 * Class CustomFieldService
 *
 * @package SP\Services
 */
class CustomFieldService extends Service
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
}