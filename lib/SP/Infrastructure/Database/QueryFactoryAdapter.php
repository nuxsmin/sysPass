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

namespace SP\Infrastructure\Database;

use Aura\SqlQuery\QueryInterface;
use SP\Domain\Database\Ports\QueryDataInterface;
use SP\Domain\Database\Ports\QueryFactoryInterface;
use SP\Infrastructure\Common\Repositories\Query;

/**
 * Infrastructure adapter for the query factory port.
 */
final class QueryFactoryAdapter implements QueryFactoryInterface
{
    public function buildQuery(string $sql, array $values = []): QueryInterface
    {
        return Query::buildForMySQL($sql, $values);
    }

    public function buildQueryData(QueryInterface $query): QueryDataInterface
    {
        return QueryData::build($query);
    }
}
