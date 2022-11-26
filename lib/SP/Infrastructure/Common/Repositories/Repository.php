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

namespace SP\Infrastructure\Common\Repositories;

use Aura\SqlQuery\QueryFactory;
use Closure;
use Exception;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcherInterface;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Ports\RepositoryInterface;
use SP\Domain\Common\Services\ServiceException;
use SP\Infrastructure\Database\DatabaseInterface;
use function SP\__u;
use function SP\logger;

/**
 * Class Repository
 *
 * @package SP\Infrastructure\Common\Repositories
 */
abstract class Repository implements RepositoryInterface
{
    protected ContextInterface         $context;
    protected DatabaseInterface        $db;
    protected QueryFactory             $queryFactory;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(
        DatabaseInterface $database,
        ContextInterface $session,
        EventDispatcherInterface $eventDispatcher,
        QueryFactory $queryFactory
    ) {
        $this->db = $database;
        $this->context = $session;
        $this->queryFactory = $queryFactory;
        $this->eventDispatcher = $eventDispatcher;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Bubbles a Closure in a database transaction
     *
     * @param  \Closure  $closure
     *
     * @return mixed
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \Exception
     */
    final public function transactionAware(Closure $closure): mixed
    {
        if ($this->db->beginTransaction()) {
            try {
                $result = $closure->call($this);

                $this->db->endTransaction();

                return $result;
            } catch (Exception $e) {
                $this->db->rollbackTransaction();

                logger('Transaction:Rollback');

                $this->eventDispatcher->notifyEvent(
                    'database.rollback',
                    new Event($this, EventMessage::factory()->addDescription(__u('Rollback')))
                );

                throw $e;
            }
        } else {
            throw new ServiceException(__u('Unable to start a transaction'));
        }
    }
}
