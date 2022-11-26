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

namespace SP\Domain\Config\Ports;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ConfigData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class ConfigRepository
 *
 * @package SP\Infrastructure\Common\Repositories\Config
 */
interface ConfigRepositoryInterface
{
    /**
     * @param  ConfigData  $configData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ConfigData $configData): bool;

    /**
     * @param  ConfigData  $configData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ConfigData $configData): int;

    /**
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult;

    /**
     * @param  string  $param
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByParam(string $param): QueryResult;

    /**
     * @param  string  $param
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function has(string $param): bool;

    /**
     * @param  string  $param
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByParam(string $param): int;
}
