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

namespace SP\Domain\Account\Ports;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Common\Ports\RepositoryInterface;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountToTagRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
interface AccountToTagRepositoryInterface extends RepositoryInterface
{
    /**
     * Devolver las etiquetas de una cuenta
     *
     * @param  int  $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getTagsByAccountId(int $id): QueryResult;

    /**
     * Eliminar las etiquetas de una cuenta
     *
     * @param  int  $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountId(int $id): bool;

    /**
     * Actualizar las etiquetas de una cuenta
     *
     * @param  \SP\Domain\Account\Dtos\AccountRequest  $accountRequest
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(AccountRequest $accountRequest): void;
}
