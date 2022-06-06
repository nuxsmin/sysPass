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

namespace SP\Domain\Common\Services;

use Closure;
use Defuse\Crypto\Exception\CryptoException;
use Exception;
use SP\Core\Application;
use SP\Core\Context\ContextException;
use SP\Core\Context\ContextInterface;
use SP\Core\Context\SessionContext;
use SP\Core\Crypt\Session;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcher;
use SP\Core\Events\EventMessage;
use SP\Domain\Config\ConfigInterface;
use SP\Infrastructure\Database\DatabaseInterface;

/**
 * Class Service
 *
 * @package SP\Domain\Common\Services
 */
abstract class Service
{
    protected const STATUS_INTERNAL_ERROR = 1000;

    protected ConfigInterface  $config;
    protected ContextInterface $context;
    protected EventDispatcher  $eventDispatcher;

    public function __construct(Application $application)
    {
        $this->config = $application->getConfig();
        $this->context = $application->getContext();
        $this->eventDispatcher = $application->getEventDispatcher();
    }

    /**
     * Bubbles a Closure in a database transaction
     *
     * @param  \Closure  $closure
     * @param  \SP\Infrastructure\Database\DatabaseInterface  $database
     *
     * @return mixed
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \Exception
     */
    protected function transactionAware(Closure $closure, DatabaseInterface $database)
    {
        if ($database->beginTransaction()) {
            try {
                $result = $closure->call($this);

                $database->endTransaction();

                return $result;
            } catch (Exception $e) {
                $database->rollbackTransaction();

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

    /**
     * @throws ServiceException
     */
    final protected function getMasterKeyFromContext(): string
    {
        try {
            if ($this->context instanceof SessionContext) {
                $key = Session::getSessionKey($this->context);
            } else {
                $key = $this->context->getTrasientKey('_masterpass');
            }

            if (empty($key)) {
                throw new ServiceException(__u('Error while retrieving master password from context'));
            }

            return $key;
        } catch (CryptoException $e) {
            logger($e->getMessage());

            throw new ServiceException(__u('Error while retrieving master password from context'));
        }
    }

    /**
     * @throws ServiceException
     */
    final protected function setMasterKeyInContext(string $masterPass): void
    {
        try {
            if ($this->context instanceof SessionContext) {
                Session::saveSessionKey($masterPass, $this->context);
            } else {
                $this->context->setTrasientKey('_masterpass', $masterPass);
            }
        } catch (ContextException|CryptoException $e) {
            logger($e->getMessage());

            throw new ServiceException(__u('Error while setting master password in context'));
        }
    }
}