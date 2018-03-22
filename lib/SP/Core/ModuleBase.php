<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
use Klein\Klein;
use SP\Bootstrap;
use SP\Config\Config;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;
use SP\Providers\Log\LogHandler;
use SP\Providers\Log\RemoteSyslogHandler;
use SP\Providers\Log\SyslogHandler;
use SP\Providers\Mail\MailHandler;
use SP\Providers\Notification\NotificationHandler;
use SP\Util\Checks;
use SP\Util\Util;

/**
 * Class ModuleBase
 *
 * @package SP\Core
 */
abstract class ModuleBase
{
    /**
     * @var \SP\Config\ConfigData
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
     * Module constructor.
     *
     * @param Container $container
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->get(Config::class);
        $this->configData = $this->config->getConfigData();
        $this->router = $container->get(Klein::class);
    }

    /**
     * @param string $controller
     * @return mixed
     */
    abstract public function initialize($controller);

    /**
     * Comprobar si el modo mantenimiento está activado
     * Esta función comprueba si el modo mantenimiento está activado.
     * Devuelve un error 503 y un reintento de 120s al cliente.
     *
     * @param ContextInterface $context
     * @return bool
     */
    public function checkMaintenanceMode(ContextInterface $context)
    {
        if ($this->configData->isMaintenance()) {
            Bootstrap::$LOCK = Util::getAppLock();

            return (Checks::isAjax($this->router)
                    || (Bootstrap::$LOCK !== false
                        && Bootstrap::$LOCK->userId > 0
                        && $context->isLoggedIn()
                        && Bootstrap::$LOCK->userId === $context->getUserData()->getId())
                ) === false;
        }

        return false;
    }

    /**
     * Initializes event handlers
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function initEventHandlers()
    {
        $eventDispatcher = $this->container->get(EventDispatcher::class);

        if ($this->configData->isLogEnabled()) {
            $eventDispatcher->attach($this->container->get(LogHandler::class));
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

        $eventDispatcher->attach($this->container->get(NotificationHandler::class));
    }
}