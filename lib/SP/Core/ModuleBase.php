<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core;

use SP\Config\Config;
use SP\Config\ConfigDataInterface;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;

/**
 * Class ModuleBase
 *
 * @package SP\Core
 */
abstract class ModuleBase
{
    protected Config              $config;
    protected ConfigDataInterface $configData;
    protected ContextInterface    $context;
    private EventDispatcher       $eventDispatcher;
    private ProvidersHelper       $providersHelper;

    /**
     * Module constructor.
     *
     * @param  \SP\Core\Application  $application
     * @param  \SP\Core\ProvidersHelper  $providersHelper
     */
    public function __construct(
        Application $application,
        ProvidersHelper $providersHelper
    ) {
        $this->config = $application->getConfig();
        $this->configData = $this->config->getConfigData();
        $this->context = $application->getContext();
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->providersHelper = $providersHelper;
    }

    /**
     * Initializes event handlers
     */
    protected function initEventHandlers(): void
    {
        if (DEBUG || $this->configData->isDebug()) {
            $handler = $this->providersHelper->getFileLogHandler();
            $handler->initialize();

            $this->eventDispatcher->attach($handler);
        }

        if ($this->configData->isLogEnabled()) {
            $handler = $this->providersHelper->getDatabaseLogHandler();
            $handler->initialize();

            $this->eventDispatcher->attach($handler);
        }

        if ($this->configData->isMailEnabled()) {
            $handler = $this->providersHelper->getMailHandler();
            $handler->initialize();

            $this->eventDispatcher->attach($handler);
        }

        if ($this->configData->isSyslogEnabled()) {
            $handler = $this->providersHelper->getSyslogHandler();
            $handler->initialize();

            $this->eventDispatcher->attach($handler);
        }

        if ($this->configData->isSyslogRemoteEnabled()) {
            $handler = $this->providersHelper->getRemoteSyslogHandler();
            $handler->initialize();

            $this->eventDispatcher->attach($handler);
        }

        $aclHandler = $this->providersHelper->getAclHandler();
        $aclHandler->initialize();

        $this->eventDispatcher->attach($aclHandler);

        $notificationHandler = $this->providersHelper->getNotificationHandler();
        $notificationHandler->initialize();

        $this->eventDispatcher->attach($notificationHandler);
    }

    abstract public function initialize(string $controller);
}