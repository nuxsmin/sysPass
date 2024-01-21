<?php
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

use Defuse\Crypto\Exception\CryptoException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Models\CustomFieldData;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class CustomFieldService
 *
 * @package SP\Domain\CustomField\Services
 */
interface CustomFieldServiceInterface
{
    /**
     * Desencriptar y formatear los datos del campo
     *
     * @throws CryptoException
     * @throws ServiceException
     */
    public function decryptData(string $data, string $key): string;

    /**
     * Returns the module's item for given id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForModuleAndItemId(int $moduleId, ?int $itemId): array;

    /**
     * Updates an item
     *
     * @throws CryptoException
     * @throws QueryException
     * @throws ConstraintException
     * @throws SPException
     */
    public function updateOrCreateData(CustomFieldData $customFieldData): bool;

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @throws SPException
     */
    public function deleteCustomFieldData(int $itemId, int $moduleId, ?int $definitionId = null): int;

    /**
     * Creates an item
     *
     * @throws CryptoException
     * @throws QueryException
     * @throws ServiceException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function create(CustomFieldData $customFieldData): bool;

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDefinitionData(int $definitionId): int;

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param int[] $ids
     * @param int $moduleId
     *
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDataBatch(array $ids, int $moduleId): bool;

    /**
     * Updates an item
     *
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function updateMasterPass(CustomFieldData $customFieldData, string $masterPass): int;

    /**
     * @return CustomFieldData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAll(): array;

    /**
     * @return CustomFieldData[]
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAllEncrypted(): array;
}
