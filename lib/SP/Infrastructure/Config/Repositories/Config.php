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

namespace SP\Infrastructure\Config\Repositories;

use SP\Domain\Config\Models\Config as ConfigModel;
use SP\Domain\Config\Ports\ConfigRepository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class Config
 *
 * @template T of ConfigModel
 */
final class Config extends BaseRepository implements ConfigRepository
{
    public const TABLE = 'Config';

    /**
     * @param ConfigModel $config
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(ConfigModel $config): QueryResult
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols($config->toArray(['value']))
            ->where('parameter = :parameter')
            ->limit(1)
            ->bindValues(
                [
                    'value' => $config->getValue(),
                    'parameter' => $config->getParameter()
                ]
            );

        $queryData = QueryData::build($query);

        return $this->db->runQuery($queryData);
    }

    /**
     * @param ConfigModel $config
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ConfigModel $config): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into(self::TABLE)
            ->cols($config->toArray());

        $queryData = QueryData::build($query);

        return $this->db->runQuery($queryData);
    }

    /**
     * @param string $param
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByParam(string $param): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(ConfigModel::getCols())
            ->where('parameter = :parameter')
            ->bindValues(['parameter' => $param])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, ConfigModel::class);

        return $this->db->runQuery($queryData);
    }

    /**
     * @param string $param
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function has(string $param): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(ConfigModel::getCols(['value']))
            ->where('parameter = :parameter')
            ->bindValues(['parameter' => $param])
            ->limit(1);

        $queryData = QueryData::build($query);

        return $this->db->runQuery($queryData)->getNumRows() === 1;
    }
}
