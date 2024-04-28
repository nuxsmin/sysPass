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

namespace SP\Domain\Common\Services;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Application;
use SP\Core\Context\ContextException;
use SP\Core\Context\Session as SessionContext;
use SP\Core\Crypt\Session as CryptSession;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\CryptException;

use function SP\__u;
use function SP\logger;

/**
 * Class Service
 */
abstract class Service
{
    protected const STATUS_INTERNAL_ERROR = 1000;

    protected readonly ConfigFileService        $config;
    protected readonly Context $context;
    protected readonly EventDispatcherInterface $eventDispatcher;

    public function __construct(Application $application)
    {
        $this->config = $application->getConfig();
        $this->context = $application->getContext();
        $this->eventDispatcher = $application->getEventDispatcher();
    }

    /**
     * @throws ServiceException
     * @throws CryptException
     */
    final protected function getMasterKeyFromContext(): string
    {
        try {
            if ($this->context instanceof SessionContext) {
                $key = CryptSession::getSessionKey($this->context);
            } else {
                $key = $this->context->getTrasientKey(Context::MASTER_PASSWORD_KEY);
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
                CryptSession::saveSessionKey($masterPass, $this->context);
            } else {
                $this->context->setTrasientKey('_masterpass', $masterPass);
            }
        } catch (ContextException|CryptException $e) {
            logger($e->getMessage());

            throw new ServiceException(__u('Error while setting master password in context'));
        }
    }
}
