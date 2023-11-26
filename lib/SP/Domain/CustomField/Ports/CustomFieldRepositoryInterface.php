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

namespace SP\Domain\CustomField\Ports;

use SP\DataModel\CustomFieldData;
use SP\Domain\Common\Ports\RepositoryInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class CustomFieldRepository
 *
 * @package SP\Domain\Common\Services
 */
interface CustomFieldRepositoryInterface extends RepositoryInterface
{
    /**
     * Comprueba si el elemento tiene campos personalizados con datos
     *
     * @param  CustomFieldData  $itemData
     *
     * @return bool
     * @throws QueryException
     * @throws ConstraintException
     */
    public function checkExists(CustomFieldData $itemData): bool;

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param  int  $itemId
     * @param  int  $moduleId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldData(int $itemId, int $moduleId): int;

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param  int  $id
     * @param  int  $moduleId
     * @param  int|null  $definitionId
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteCustomFieldDataForDefinition(int $id, int $moduleId, ?int $definitionId): int;

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param  int  $definitionId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDefinitionData(int $definitionId): int;

    /**
     * Eliminar los datos de los elementos de una definición
     *
     * @param  array  $definitionIds
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteCustomFieldDefinitionDataBatch(array $definitionIds): int;

    /**
     * Eliminar los datos de los campos personalizados del módulo
     *
     * @param  int[]  $ids
     * @param  int  $moduleId
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function deleteCustomFieldDataBatch(array $ids, int $moduleId): int;

    /**
     * Returns all the items that were encryptes
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     */
    public function getAllEncrypted(): QueryResult;

    /**
     * Returns the module's item for given id
     *
     * @param  int  $moduleId
     * @param  int|null  $itemId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getForModuleAndItemId(int $moduleId, ?int $itemId): QueryResult;
}
