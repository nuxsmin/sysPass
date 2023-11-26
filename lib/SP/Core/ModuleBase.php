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

namespace SP\Core;

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigFileService;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;

/**
 * Class ModuleBase
 *
 * @package SP\Core
 */
abstract class ModuleBase
{
    protected ConfigFileService      $config;
    protected ConfigDataInterface    $configData;
    protected ContextInterface       $context;
    private EventDispatcherInterface $eventDispatcher;

    /**
     * Module constructor.
     *
     * @param Application $application
     * @param ProvidersHelper $providersHelper
     */
    public function __construct(Application $application, private readonly ProvidersHelper $providersHelper)
    {
        $this->config = $application->getConfig();
        $this->configData = $this->config->getConfigData();
        $this->context = $application->getContext();
        $this->eventDispatcher = $application->getEventDispatcher();
    }

    abstract public function initialize(string $controller): void;

    /**
     * Initializes event handlers
     */
    protected function initEventHandlers(bool $partialInit = false): void
    {
        if (DEBUG || $this->configData->isDebug() || !$this->configData->isInstalled()) {
            $this->eventDispatcher->attach($this->providersHelper->getFileLogHandler());
        }

        if ($partialInit || !$this->configData->isInstalled()) {
            return;
        }

        if ($this->configData->isLogEnabled()) {
            $this->eventDispatcher->attach($this->providersHelper->getDatabaseLogHandler());
        }

        if ($this->configData->isMailEnabled()) {
            $this->eventDispatcher->attach($this->providersHelper->getMailHandler());
        }

        if ($this->configData->isSyslogEnabled()) {
            $this->eventDispatcher->attach($this->providersHelper->getSyslogHandler());
        }

        if ($this->configData->isSyslogRemoteEnabled()) {
            $this->eventDispatcher->attach($this->providersHelper->getRemoteSyslogHandler());
        }

        $this->eventDispatcher->attach($this->providersHelper->getAclHandler());
        $this->eventDispatcher->attach($this->providersHelper->getNotificationHandler());
    }
}
