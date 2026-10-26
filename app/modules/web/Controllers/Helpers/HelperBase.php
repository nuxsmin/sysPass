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

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Core\Application;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Mvc\View\TemplateInterface;

/**
 * Class HelperBase
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
abstract class HelperBase
{
    protected readonly TemplateInterface        $view;
    protected readonly ConfigDataInterface      $configData;
    protected readonly Context                  $context;
    protected readonly EventDispatcherInterface $eventDispatcher;
    protected readonly ConfigFileService        $config;

    public function __construct(
        Application                       $application,
        TemplateInterface                 $template,
        protected readonly RequestService $request
    ) {
        $this->config = $application->getConfig();
        $this->context = $application->getContext();
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->configData = $this->config->getConfigData();
        $this->view = $template;
    }
}
