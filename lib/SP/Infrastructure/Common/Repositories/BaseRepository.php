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

namespace SP\Infrastructure\Common\Repositories;

use Aura\SqlQuery\QueryFactory;
use Closure;
use Exception;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;
use function SP\logger;

/**
 * Class Repository
 */
abstract class BaseRepository implements Repository
{
    public function __construct(
        protected readonly DatabaseInterface        $db,
        protected readonly Context $context,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly QueryFactory             $queryFactory
    ) {
    }

    /**
     * Bubbles a Closure in a database transaction
     *
     * @param Closure $closure
     * @param object $newThis
     *
     * @return mixed
     * @throws ServiceException
     */
    final public function transactionAware(Closure $closure, object $newThis): mixed
    {
        if ($this->db->beginTransaction()) {
            try {
                $result = $closure->call($newThis);

                $this->db->endTransaction();

                return $result;
            } catch (Exception $e) {
                $this->db->rollbackTransaction();

                logger('Transaction:Rollback');

                $this->eventDispatcher->notify(
                    'database.rollback',
                    new Event($this, EventMessage::build()->addDescription(__u('Rollback')))
                );
                $this->eventDispatcher->notify('exception', new Event($e));

                throw new ServiceException(__u('Rollback'), SPException::ERROR, null, $e->getCode(), $e);
            }
        } else {
            throw new ServiceException(__u('Unable to start a transaction'));
        }
    }

    /**
     * Run a SQL select query to get any data from any table
     *
     * @param array $columns
     * @param string $from
     * @param string|null $where
     * @param array|null $bindValues
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    final public function getAny(
        array  $columns,
        string $from,
        ?string $where = null,
        ?array $bindValues = null
    ): QueryResult {
        $query = $this->queryFactory
            ->newSelect()
            ->cols($columns)
            ->from($from);

        if (!empty($where)) {
            $query->where($where)
                  ->bindValues($bindValues ?? []);
        }

        return $this->db->runQuery(QueryData::build($query));
    }
}
