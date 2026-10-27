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

namespace SP\Domain\CustomField\Services;

use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldDataService;

/**
 * Builds the custom field items of a module item.
 */
final class CustomFieldItems
{
    /**
     * @param CustomFieldDataService<CustomFieldDataModel> $customFieldDataService
     * @return CustomFieldItem[]
     * @throws SPException
     * @throws ServiceException
     */
    public static function getForItem(
        int $moduleId,
        ?int $itemId,
        CustomFieldDataService $customFieldDataService
    ): array {
        $customFields = [];

        foreach ($customFieldDataService->getBy($moduleId, $itemId) as $item) {
            $valueEncrypted = !empty($item['data']) && !empty($item['key']);
            $value = $valueEncrypted
                ? self::formatValue($customFieldDataService->decrypt($item['data'], $item['key']) ?? '')
                : $item['data'];

            $customFields[] = new CustomFieldItem(
                required:         (bool)$item['required'],
                showInList:       (bool)$item['showInList'],
                help:             $item['help'],
                definitionId:     (int)$item['definitionId'],
                definitionName:   $item['definitionName'],
                typeId:           (int)$item['typeId'],
                typeName:         $item['typeName'],
                typeText:         $item['typeText'],
                moduleId:         (int)$item['moduleId'],
                formId:           self::getFormIdForName($item['definitionName']),
                value:            $value,
                isEncrypted:      (bool)$item['isEncrypted'],
                isValueEncrypted: $valueEncrypted
            );
        }

        return $customFields;
    }

    private static function formatValue(string $value): string
    {
        if (preg_match('#https?://#', $value)) {
            return sprintf('<a href="%s" target="_blank">%s</a>', $value, $value);
        }

        return $value;
    }

    private static function getFormIdForName(string $name): string
    {
        return sprintf('cf_%s', strtolower(preg_replace('/\W*/', '', $name)));
    }
}
