<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class ConfigRepository
 *
 * @package SP\Repositories\Config
 */
class ConfigRepository extends Repository
{
    /**
     * @param ConfigData $configData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(ConfigData $configData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('UPDATE Config SET value = ? WHERE parameter = ?');
        $queryData->addParam($configData->getValue());
        $queryData->addParam($configData->getParam());

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * @param ConfigData[] $data
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateBatch(array $data)
    {
        DbWrapper::beginTransaction($this->db);

        try {
            foreach ($data as $configData) {
                $queryData = new QueryData();
                $queryData->setQuery('UPDATE Config SET value = ? WHERE parameter = ?');
                $queryData->addParam($configData->getValue());
                $queryData->addParam($configData->getParam());

                DbWrapper::getQuery($queryData, $this->db);
            }
        } catch (QueryException $e) {
            debugLog($e->getMessage());
        } catch (ConstraintException $e) {
            debugLog($e->getMessage());
        } finally {
            return DbWrapper::endTransaction($this->db);
        }
    }

    /**
     * @param ConfigData $configData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(ConfigData $configData)
    {
        $queryData = new QueryData();
        $queryData->setQuery('INSERT INTO Config SET parameter = ?, value = ?');
        $queryData->addParam($configData->getParam());
        $queryData->addParam($configData->getValue());

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return ConfigData[]
     */
    public function getAll()
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT parameter, value FROM Config');

        return DbWrapper::getResults($queryData);
    }

    /**
     * @param string $param
     * @return mixed
     */
    public function getByParam($param)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT value FROM Config WHERE parameter = ? LIMIT 1');
        $queryData->addParam($param);

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * @param string $param
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function has($param)
    {
        $queryData = new QueryData();
        $queryData->setQuery('SELECT parameter FROM Config WHERE parameter = ? LIMIT 1');
        $queryData->addParam($param);

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows() === 1;
    }

    /**
     * @param $param
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByParam($param)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Config WHERE parameter = ? LIMIT 1');
        $queryData->addParam($param);

        return DbWrapper::getQuery($queryData, $this->db);
    }
}