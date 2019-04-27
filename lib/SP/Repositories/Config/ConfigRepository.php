<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Repositories\Config;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ConfigData;
use SP\Repositories\Repository;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class ConfigRepository
 *
 * @package SP\Repositories\Config
 */
final class ConfigRepository extends Repository
{
    /**
     * @param ConfigData $configData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ConfigData $configData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Config SET `value` = ? WHERE parameter = ?');
        $queryData->setParams([$configData->getValue(), $configData->getParameter()]);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * @param ConfigData $configData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ConfigData $configData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('INSERT INTO Config SET parameter = ?, `value` = ?');
        $queryData->setParams([$configData->getParameter(), $configData->getValue()]);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT parameter, `value` FROM Config ORDER BY parameter');

        return $this->db->doSelect($queryData);
    }

    /**
     * @param string $param
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByParam($param)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT parameter, `value` FROM Config WHERE parameter = ? LIMIT 1');
        $queryData->addParam($param);

        return $this->db->doSelect($queryData);
    }

    /**
     * @param string $param
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function has($param)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT parameter FROM Config WHERE parameter = ? LIMIT 1');
        $queryData->addParam($param);

        return $this->db->doSelect($queryData)->getNumRows() === 1;
    }

    /**
     * @param $param
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByParam($param)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Config WHERE parameter = ? LIMIT 1');
        $queryData->addParam($param);

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }
}