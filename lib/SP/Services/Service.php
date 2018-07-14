<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Services;

use DI\Container;
use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Storage\Database\Database;

/**
 * Class Service
 *
 * @package SP\Services
 */
abstract class Service
{
    const STATUS_INTERNAL_ERROR = 1000;

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var ContextInterface
     */
    protected $context;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var ContainerInterface
     */
    protected $dic;

    /**
     * Service constructor.
     *
     * @param Container $dic
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(Container $dic)
    {
        $this->dic = $dic;
        $this->config = $dic->get(Config::class);
        $this->context = $dic->get(ContextInterface::class);
        $this->eventDispatcher = $dic->get(EventDispatcher::class);

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * Bubbles a Closure in a database transaction
     *
     * @param \Closure $closure
     *
     * @return mixed
     * @throws ServiceException
     * @throws \Exception
     */
    protected function transactionAware(\Closure $closure)
    {
        $database = $this->dic->get(Database::class);

        if ($database->beginTransaction()) {
            try {
                $result = $closure->call($this);

                $database->endTransaction();

                return $result;
            } catch (\Exception $e) {
                $database->rollbackTransaction();

                debugLog('Transaction:Rollback');

                $this->eventDispatcher->notifyEvent('database.rollback',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Rollback')))
                );

                throw $e;
            }
        } else {
            throw new ServiceException(__u('No es posible iniciar una transacción'));
        }
    }
}