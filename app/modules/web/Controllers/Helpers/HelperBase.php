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

namespace SP\Modules\Web\Controllers\Helpers;

use SP\Core\Application;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcher;
use SP\Domain\Config\In\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigFileService;
use SP\Http\Request;
use SP\Http\RequestInterface;
use SP\Mvc\View\TemplateInterface;

/**
 * Class HelperBase
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
abstract class HelperBase
{
    protected TemplateInterface   $view;
    protected ConfigDataInterface $configData;
    protected ContextInterface    $context;
    protected EventDispatcher   $eventDispatcher;
    protected ConfigFileService $config;
    protected Request           $request;

    /**
     * Constructor
     *
     * @param  \SP\Core\Application  $application
     * @param  \SP\Mvc\View\TemplateInterface  $template
     * @param  \SP\Http\RequestInterface  $request
     */
    public function __construct(
        Application $application,
        TemplateInterface $template,
        RequestInterface $request
    ) {
        $this->config = $application->getConfig();
        $this->context = $application->getContext();
        $this->eventDispatcher = $application->getEventDispatcher();
        $this->request = $request;
        $this->configData = $this->config->getConfigData();
        $this->view = $template;
    }
}