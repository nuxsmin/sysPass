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

use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;
use SP\Core\Template;
use SP\Core\Traits\InjectableTrait;

/**
 * Class HelperBase
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
abstract class HelperBase
{
    use InjectableTrait;

    /**
     * @var Template
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
     * Constructor
     *
     * @param Template        $template
     * @param Config          $config
     * @param Session         $session
     * @param EventDispatcher $eventDispatcher
     */
    final public function __construct(Template $template, Config $config, Session $session, EventDispatcher $eventDispatcher)
    {
        $this->injectDependencies();

        $this->view = $template;
        $this->configData = $config->getConfigData();
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }
}