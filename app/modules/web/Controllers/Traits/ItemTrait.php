<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Modules\Web\Controllers\Traits;

use Defuse\Crypto\Exception\CryptoException;
use SP\Bootstrap;
use SP\Core\Context\SessionContext;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\DataModel\ItemSearchData;
use SP\Http\Request;
use SP\Services\CustomField\CustomFieldItem;
use SP\Services\CustomField\CustomFieldService;

/**
 * Trait ItemTrait
 *
 * @package SP\Modules\Web\Controllers\Traits
 */
trait ItemTrait
{
    /**
     * Obtener la lista de campos personalizados y sus valores
     *
     * @param int            $moduleId
     * @param int            $itemId
     * @param SessionContext $sessionContext
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getCustomFieldsForItem($moduleId, $itemId, SessionContext $sessionContext)
    {
        $customFieldService = Bootstrap::getContainer()->get(CustomFieldService::class);
        $customFields = [];

        foreach ($customFieldService->getForModuleById($moduleId, $itemId) as $item) {
            try {
                $customField = new CustomFieldItem();
                $customField->required = (bool)$item->required;
                $customField->showInList = (bool)$item->showInList;
                $customField->help = $item->help;
                $customField->definitionId = (int)$item->definitionId;
                $customField->definitionName = $item->definitionName;
                $customField->typeId = (int)$item->typeId;
                $customField->typeName = $item->typeName;
                $customField->typeText = $item->typeText;
                $customField->moduleId = (int)$item->moduleId;
                $customField->formId = CustomFieldService::getFormIdForName($item->definitionName);
                $customField->isEncrypted = (int)$item->isEncrypted;

                if ($item->data !== null
                    && $item->key !== null
                ) {
                    $customField->isValueEncrypted = true;
                    $customField->value = CustomFieldService::decryptData($item->data, $item->key, $sessionContext);
                } else {
                    $customField->isValueEncrypted = false;
                    $customField->value = $item->data;
                }

                $customFields[] = $customField;
            } catch (CryptoException $e) {
                debugLog($e->getMessage());
            }
        }

        return $customFields;
    }

    /**
     * Añadir los campos personalizados del elemento
     *
     * @param int       $moduleId
     * @param int|int[] $itemId
     * @throws SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function addCustomFieldsForItem($moduleId, $itemId)
    {
        if ($customFields = Request::analyzeArray('customfield')) {
            $customFieldService = Bootstrap::getContainer()->get(CustomFieldService::class);

            try {
                foreach ($customFields as $id => $value) {
                    $customFieldData = new CustomFieldData();
                    $customFieldData->setId($itemId);
                    $customFieldData->setModuleId($moduleId);
                    $customFieldData->setDefinitionId($id);
                    $customFieldData->setData($value);

                    $customFieldService->create($customFieldData);
                }
            } catch (CryptoException $e) {
                throw new SPException(__u('Error interno'), SPException::ERROR);
            }
        }
    }

    /**
     * Eliminar los campos personalizados del elemento
     *
     * @param int       $moduleId
     * @param int|int[] $itemId
     * @throws SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function deleteCustomFieldsForItem($moduleId, $itemId)
    {
        $customFieldService = Bootstrap::getContainer()->get(CustomFieldService::class);

        if (is_array($itemId)) {
            $customFieldService->deleteCustomFieldDataBatch($itemId, $moduleId);
        } else {
            $customFieldService->deleteCustomFieldData($itemId, $moduleId);
        }
    }

    /**
     * Actualizar los campos personalizados del elemento
     *
     * @param int       $moduleId
     * @param int|int[] $itemId
     * @throws SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function updateCustomFieldsForItem($moduleId, $itemId)
    {
        if ($customFields = Request::analyzeArray('customfield')) {
            $customFieldService = Bootstrap::getContainer()->get(CustomFieldService::class);

            try {
                foreach ($customFields as $id => $value) {
                    $customFieldData = new CustomFieldData();
                    $customFieldData->setId($itemId);
                    $customFieldData->setModuleId($moduleId);
                    $customFieldData->setDefinitionId($id);
                    $customFieldData->setData($value);

                    $customFieldService->update($customFieldData);
                }
            } catch (CryptoException $e) {
                throw new SPException(__u('Error interno'), SPException::ERROR);
            }
        }
    }

    /**
     * Returns search data object for the current request
     *
     * @param int $limitCount
     * @return ItemSearchData
     */
    protected function getSearchData($limitCount)
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString(Request::analyzeString('search'));
        $itemSearchData->setLimitStart(Request::analyzeInt('start'));
        $itemSearchData->setLimitCount(Request::analyzeInt('count', $limitCount));

        return $itemSearchData;
    }

    /**
     * @return mixed
     */
    protected function getItemsIdFromRequest()
    {
        return Request::analyzeArray('items');
    }
}