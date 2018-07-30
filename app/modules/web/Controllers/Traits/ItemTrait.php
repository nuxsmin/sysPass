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
     * @param int $moduleId
     * @param int $itemId
     *
     * @return array
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\ServiceException
     */
    protected function getCustomFieldsForItem($moduleId, $itemId)
    {
        $customFieldService = Bootstrap::getContainer()->get(CustomFieldService::class);
        $customFields = [];

        foreach ($customFieldService->getForModuleAndItemId($moduleId, $itemId) as $item) {
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
                    $customField->value = $customFieldService->decryptData($item->data, $item->key);
                } else {
                    $customField->isValueEncrypted = false;
                    $customField->value = $item->data;
                }

                $customFields[] = $customField;
            } catch (CryptoException $e) {
                logger($e->getMessage());
            }
        }

        return $customFields;
    }

    /**
     * Añadir los campos personalizados del elemento
     *
     * @param int       $moduleId
     * @param int|int[] $itemId
     * @param Request   $request
     *
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\NoSuchItemException
     * @throws \SP\Services\ServiceException
     */
    protected function addCustomFieldsForItem($moduleId, $itemId, Request $request)
    {
        if ($customFields = $request->analyzeArray('customfield')) {
            $customFieldService = Bootstrap::getContainer()->get(CustomFieldService::class);

            try {
                foreach ($customFields as $id => $value) {
                    $customFieldData = new CustomFieldData();
                    $customFieldData->setItemId($itemId);
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
     *
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
     * @param Request   $request
     *
     * @throws SPException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function updateCustomFieldsForItem($moduleId, $itemId, Request $request)
    {
        if ($customFields = $request->analyzeArray('customfield')) {
            $customFieldService = Bootstrap::getContainer()->get(CustomFieldService::class);

            try {
                foreach ($customFields as $id => $value) {
                    $customFieldData = new CustomFieldData();
                    $customFieldData->setItemId($itemId);
                    $customFieldData->setModuleId($moduleId);
                    $customFieldData->setDefinitionId($id);
                    $customFieldData->setData($value);

                    if ($customFieldService->updateOrCreateData($customFieldData) === false) {
                        throw new SPException(__u('Error al actualizar los datos del campo personalizado'));
                    }
                }
            } catch (CryptoException $e) {
                throw new SPException(__u('Error interno'), SPException::ERROR);
            }
        }
    }

    /**
     * Returns search data object for the current request
     *
     * @param int     $limitCount
     * @param Request $request
     *
     * @return ItemSearchData
     */
    protected function getSearchData($limitCount, Request $request)
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString($request->analyzeString('search'));
        $itemSearchData->setLimitStart($request->analyzeInt('start', 0));
        $itemSearchData->setLimitCount($request->analyzeInt('count', $limitCount));

        return $itemSearchData;
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    protected function getItemsIdFromRequest(Request $request)
    {
        return $request->analyzeArray('items');
    }
}