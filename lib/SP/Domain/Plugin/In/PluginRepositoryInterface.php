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

namespace SP\Domain\Plugin\In;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\In\RepositoryInterface;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Plugin\Repositories\PluginModel;

/**
 * Class PluginRepository
 *
 * @package SP\Infrastructure\Plugin\Repositories
 */
interface PluginRepositoryInterface extends RepositoryInterface
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
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function toggleEnabled(int $id, bool $enabled): int;

    /**
     * Cambiar el estado del plugin
     *
     * @param  string  $name
     * @param  bool  $enabled
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function toggleEnabledByName(string $name, bool $enabled): int;

    /**
     * Cambiar el estado del plugin
     *
     * @param  int  $id
     * @param  bool  $available
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function toggleAvailable(int $id, bool $available): int;

    /**
     * Cambiar el estado del plugin
     *
     * @param  string  $name
     * @param  bool  $available
     *
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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