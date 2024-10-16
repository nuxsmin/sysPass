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

namespace SP\Domain\Config\Ports;

use SP\Domain\Common\Ports\Repository;
use SP\Domain\Config\Models\Config as ConfigModel;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Interface ConfigRepository
 *
 * @template T of ConfigModel
 */
interface ConfigRepository extends Repository
{
    /**
     * @param ConfigModel $config
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ConfigModel $config): QueryResult;

    /**
     * @param ConfigModel $config
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ConfigModel $config): QueryResult;

    /**
     * @param string $param
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByParam(string $param): QueryResult;

    /**
     * @param string $param
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function has(string $param): bool;
}
