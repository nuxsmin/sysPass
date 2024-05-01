<?php
declare(strict_types=1);
/*
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

namespace SP\Domain\CustomField\Ports;

use SP\Domain\Common\Services\ServiceException;
use SP\Domain\CustomField\Models\CustomFieldData;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;

/**
 * Class CustomFieldService
 *
 * @template T of CustomFieldDataModel
 */
interface CustomFieldDataService
{
    /**
     * Desencriptar y formatear los datos del campo
     *
     * @throws ServiceException
     */
    public function decrypt(string $data, string $key): ?string;

    /**
     * Returns the module's item for given id
     *
     * @throws ServiceException
     */
    public function getBy(int $moduleId, ?int $itemId): array;

    /**
     * Updates an item
     *
     * @throws ServiceException
     */
    public function updateOrCreate(CustomFieldData $customFieldData): void;

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @throws ServiceException
     */
    public function delete(array $itemsId, int $moduleId): void;

    /**
     * Creates an item
     *
     * @throws ServiceException
     */
    public function create(CustomFieldData $customFieldData): void;

    /**
     * Update data using the given masterpass
     *
     * @throws ServiceException
     */
    public function updateMasterPass(CustomFieldData $customFieldData, string $masterPass): int;

    /**
     * @return array<int, T>
     * @throws ServiceException
     */
    public function getAll(): array;

    /**
     * @return array<int, T>
     * @throws ServiceException
     */
    public function getAllEncrypted(): array;
}
