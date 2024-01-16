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

namespace SP\Domain\Plugin\Ports;

use SP\Domain\Common\Ports\Repository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class PluginRepository
 *
 * @package SP\Infrastructure\Plugin\Repositories
 */
interface PluginRepository extends Repository
{
    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param  string  $name
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName(string $name): QueryResult;

    /**
     * Cambiar el estado del plugin
     *
     * @param  int  $id
     * @param  bool  $enabled
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabled(int $id, bool $enabled): int;

    /**
     * Cambiar el estado del plugin
     *
     * @param  string  $name
     * @param  bool  $enabled
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleEnabledByName(string $name, bool $enabled): int;

    /**
     * Cambiar el estado del plugin
     *
     * @param  int  $id
     * @param  bool  $available
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailable(int $id, bool $available): int;

    /**
     * Cambiar el estado del plugin
     *
     * @param  string  $name
     * @param  bool  $available
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function toggleAvailableByName(string $name, bool $available): int;

    /**
     * Restablecer los datos de un plugin
     *
     * @param  int  $id  Id del plugin
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function resetById(int $id): int;

    /**
     * Devolver los plugins activados
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getEnabled(): QueryResult;
}
