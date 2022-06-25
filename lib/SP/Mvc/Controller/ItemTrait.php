<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Mvc\Controller;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\DataModel\ItemSearchData;
use SP\Domain\CustomField\CustomFieldServiceInterface;
use SP\Domain\CustomField\Services\CustomFieldItem;
use SP\Domain\CustomField\Services\CustomFieldService;
use SP\Http\RequestInterface;
use SP\Util\Filter;

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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    protected function getCustomFieldsForItem(
        int $moduleId,
        ?int $itemId,
        CustomFieldServiceInterface $customFieldService
    ): array {
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
                throw new SPException(__u('Internal error'), SPException::ERROR);
            }
        }

        return $customFields;
    }

    /**
     * Añadir los campos personalizados del elemento
     *
     * @param  int  $moduleId
     * @param  int|int[]  $itemId
     * @param  RequestInterface  $request
     * @param  \SP\Domain\CustomField\CustomFieldServiceInterface  $customFieldService
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    protected function addCustomFieldsForItem(
        int $moduleId,
        $itemId,
        RequestInterface $request,
        CustomFieldServiceInterface $customFieldService
    ): void {
        $customFields = $this->getCustomFieldsFromRequest($request);

        if (!empty($customFields)) {
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
                throw new SPException(__u('Internal error'), SPException::ERROR);
            }
        }
    }

    /**
     * Eliminar los campos personalizados del elemento
     *
     * @param  int  $moduleId
     * @param  int|int[]  $itemId
     * @param  \SP\Domain\CustomField\CustomFieldServiceInterface  $customFieldService
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function deleteCustomFieldsForItem(
        int $moduleId,
        $itemId,
        CustomFieldServiceInterface $customFieldService
    ): void {
        if (is_array($itemId)) {
            $customFieldService->deleteCustomFieldDataBatch($itemId, $moduleId);
        } else {
            $customFieldService->deleteCustomFieldData($itemId, $moduleId);
        }
    }

    /**
     * Actualizar los campos personalizados del elemento
     *
     * @param  int  $moduleId
     * @param  int|int[]  $itemId
     * @param  RequestInterface  $request
     * @param  \SP\Domain\CustomField\CustomFieldServiceInterface  $customFieldService
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function updateCustomFieldsForItem(
        int $moduleId,
        $itemId,
        RequestInterface $request,
        CustomFieldServiceInterface $customFieldService
    ): void {
        $customFields = $this->getCustomFieldsFromRequest($request);

        if (!empty($customFields)) {
            try {
                foreach ($customFields as $id => $value) {
                    $customFieldData = new CustomFieldData();
                    $customFieldData->setItemId($itemId);
                    $customFieldData->setModuleId($moduleId);
                    $customFieldData->setDefinitionId($id);
                    $customFieldData->setData($value);

                    if ($customFieldService->updateOrCreateData($customFieldData) === false) {
                        throw new SPException(__u('Error while updating custom field\'s data'));
                    }
                }
            } catch (CryptoException $e) {
                throw new SPException(__u('Internal error'), SPException::ERROR);
            }
        }
    }

    /**
     * Returns search data object for the current request
     */
    protected function getSearchData(int $limitCount, RequestInterface $request): ItemSearchData
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString($request->analyzeString('search'));
        $itemSearchData->setLimitStart($request->analyzeInt('start', 0));
        $itemSearchData->setLimitCount($request->analyzeInt('count', $limitCount));

        return $itemSearchData;
    }

    protected function getItemsIdFromRequest(RequestInterface $request): ?array
    {
        return $request->analyzeArray('items');
    }

    /**
     * @param  \SP\Http\RequestInterface  $request
     *
     * @return array|null
     */
    private static function getCustomFieldsFromRequest(RequestInterface $request): ?array
    {
        return $request->analyzeArray(
            'customfield',
            fn($values) => array_map(static fn($value) => Filter::getString($value), $values)
        );
    }
}