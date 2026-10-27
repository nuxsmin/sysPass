<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Common\Providers\Filter;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\CustomField\Services\CustomFieldItems;
use SP\Domain\Http\Ports\RequestService;

/**
 * Trait ItemTrait
 */
trait ItemTrait
{
    /**
     * Obtener la lista de campos personalizados y sus valores
     *
     * @throws SPException
     * @throws ServiceException
     */
    protected function getCustomFieldsForItem(
        int                    $moduleId,
        ?int                   $itemId,
        CustomFieldDataService $customFieldDataService
    ): array {
        return CustomFieldItems::getForItem($moduleId, $itemId, $customFieldDataService);
    }

    /**
     * Añadir los campos personalizados del elemento
     *
     * @param int $moduleId
     * @param int|int[] $itemId
     * @param RequestService $request
     * @param CustomFieldDataService $customFieldDataService
     *
     * @throws SPException
     * @throws ServiceException
     */
    protected function addCustomFieldsForItem(
        int                    $moduleId,
        int|array              $itemId,
        RequestService $request,
        CustomFieldDataService $customFieldDataService
    ): void {
        $customFields = self::getCustomFieldsFromRequest($request);

        if (!empty($customFields)) {
            foreach ($customFields as $id => $value) {
                $customFieldData = new CustomFieldDataModel(
                    [
                        'itemId' => $itemId,
                        'moduleId' => $moduleId,
                        'definitionId' => $id,
                        'data' => $value
                    ]
                );

                if (!empty($customFieldData->getData())) {
                    $customFieldDataService->create($customFieldData);
                }
            }
        }
    }

    /**
     * @param RequestService $request
     *
     * @return array|null
     */
    private static function getCustomFieldsFromRequest(RequestService $request): ?array
    {
        return $request->analyzeArray(
            'customfield',
            static fn($values) => array_map(static fn($value) => Filter::getString($value), $values)
        );
    }

    /**
     * Eliminar los campos personalizados del elemento
     *
     * @param int $moduleId
     * @param int|int[] $itemId
     * @param CustomFieldDataService $customFieldService
     *
     * @throws ServiceException
     */
    protected function deleteCustomFieldsForItem(
        int                    $moduleId,
        array|int              $itemId,
        CustomFieldDataService $customFieldService
    ): void {
        if (!is_array($itemId)) {
            $itemId = [$itemId];
        }

        $customFieldService->delete($itemId, $moduleId);
    }

    /**
     * Actualizar los campos personalizados del elemento
     *
     * @param int $moduleId
     * @param int|int[] $itemId
     * @param RequestService $request
     * @param CustomFieldDataService $customFieldDataService
     *
     * @throws ServiceException
     * @throws SPException
     */
    protected function updateCustomFieldsForItem(
        int                    $moduleId,
        int|array              $itemId,
        RequestService $request,
        CustomFieldDataService $customFieldDataService
    ): void {
        $customFields = self::getCustomFieldsFromRequest($request);

        if (!empty($customFields)) {
            foreach ($customFields as $id => $value) {
                $customFieldData = new CustomFieldDataModel(
                    [
                        'itemId' => $itemId,
                        'moduleId' => $moduleId,
                        'definitionId' => $id,
                        'data' => $value
                    ]
                );

                if (empty($customFieldData->getData())) {
                    $customFieldDataService->delete([$itemId], $moduleId);
                } else {
                    $customFieldDataService->updateOrCreate($customFieldData);
                }
            }
        }
    }

    /**
     * Returns search data object for the current request
     */
    protected function getSearchData(int $limitCount, RequestService $request): ItemSearchDto
    {
        return new ItemSearchDto(
            $request->analyzeString('search'),
            $request->analyzeInt('start', 0),
            $request->analyzeInt('count', $limitCount)
        );
    }

    protected function getItemsIdFromRequest(RequestService $request): ?array
    {
        return $request->analyzeArray('items');
    }

    private function processCustomFields(callable $action)
    {
    }
}
