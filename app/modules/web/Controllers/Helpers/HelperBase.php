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

namespace SP\Modules\Web\Controllers\Helpers;

use DI\Container;
use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;
use SP\Mvc\View\Template;

/**
 * Class HelperBase
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
abstract class HelperBase
{
    /**
     * @var \SP\Mvc\View\Template
     */
    protected $view;
    /**
     * @var ConfigData
     */
    protected $configData;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var ContainerInterface
     */
    protected $dic;

    /**
     * Constructor
     *
     * @param \SP\Mvc\View\Template $template
     * @param Config                $config
     * @param Session               $session
     * @param EventDispatcher       $eventDispatcher
     * @param Container             $container
     */
    final public function __construct(Template $template, Config $config, Session $session, EventDispatcher $eventDispatcher, Container $container)
    {
        $this->dic = $container;
        $this->view = $template;
        $this->config = $config;
        $this->configData = $config->getConfigData();
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }
}