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

namespace SP\Core;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Klein\Klein;
use Psr\Container\ContainerInterface;
use SP\Bootstrap;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;
use SP\Http\Request;
use SP\Providers\Acl\AclHandler;
use SP\Providers\Log\DatabaseLogHandler;
use SP\Providers\Log\FileLogHandler;
use SP\Providers\Log\RemoteSyslogHandler;
use SP\Providers\Log\SyslogHandler;
use SP\Providers\Mail\MailHandler;
use SP\Providers\Notification\NotificationHandler;
use SP\Util\Util;

/**
 * Class ModuleBase
 *
 * @package SP\Core
 */
abstract class ModuleBase
{
    /**
     * @var ConfigData
     */
    protected $configData;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Klein
     */
    protected $router;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Request
     */
    protected $request;

    /**
     * Module constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(Config::class);
        $this->configData = $this->config->getConfigData();
        $this->router = $container->get(Klein::class);
        $this->request = $container->get(Request::class);
    }

    /**
     * @param string $controller
     *
     * @return mixed
     */
    abstract public function initialize($controller);

    /**
     * Comprobar si el modo mantenimiento está activado
     * Esta función comprueba si el modo mantenimiento está activado.
     *
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function checkMaintenanceMode(ContextInterface $context)
    {
        if ($this->configData->isMaintenance()) {
            Bootstrap::$LOCK = Util::getAppLock();

            return !$this->request->isAjax()
                || !(Bootstrap::$LOCK !== false
                    && Bootstrap::$LOCK->userId > 0
                    && $context->isLoggedIn()
                    && Bootstrap::$LOCK->userId === $context->getUserData()->getId());
        }

        return false;
    }

    /**
     * Initializes event handlers
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function initEventHandlers()
    {
        $eventDispatcher = $this->container->get(EventDispatcher::class);

        if (DEBUG || $this->configData->isDebug()) {
            $eventDispatcher->attach($this->container->get(FileLogHandler::class));
        }

        if ($this->configData->isLogEnabled()) {
            $eventDispatcher->attach($this->container->get(DatabaseLogHandler::class));
        }

        if ($this->configData->isMailEnabled()) {
            $eventDispatcher->attach($this->container->get(MailHandler::class));
        }

        if ($this->configData->isSyslogEnabled()) {
            $eventDispatcher->attach($this->container->get(SyslogHandler::class));
        }

        if ($this->configData->isSyslogRemoteEnabled()) {
            $eventDispatcher->attach($this->container->get(RemoteSyslogHandler::class));
        }

        $eventDispatcher->attach($this->container->get(AclHandler::class));
        $eventDispatcher->attach($this->container->get(NotificationHandler::class));
    }
}