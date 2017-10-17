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

namespace SP\Modules\Web\Controllers;

use SP\Config\Config;
use SP\Core\Acl;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;
use SP\Core\Traits\InjectableTrait;
use SP\Core\UI\Theme;
use SP\Storage\Database;

/**
 * Class SimpleControllerBase
 *
 * @package SP\Modules\Web\Controllers
 */
abstract class SimpleControllerBase
{
    use InjectableTrait;

    /** @var  int Módulo a usar */
    protected $action;
    /** @var string Nombre del controlador */
    protected $controllerName;
    /** @var  EventDispatcher */
    protected $eventDispatcher;
    /** @var  Config */
    protected $config;
    /** @var  Session */
    protected $session;
    /** @var  Database */
    protected $db;
    /** @var  Theme */
    protected $theme;
    /** @var  Acl */
    protected $acl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->injectDependencies();

        $class = static::class;
        $this->controllerName = substr($class, strrpos($class, '\\') + 1, -strlen('Controller'));

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * @param Config          $config
     * @param Session         $session
     * @param Database        $db
     * @param Theme           $theme
     * @param EventDispatcher $ev
     * @param Acl             $acl
     */
    public function inject(Config $config, Session $session, Database $db, Theme $theme, EventDispatcher $ev, Acl $acl)
    {
        $this->config = $config;
        $this->session = $session;
        $this->db = $db;
        $this->theme = $theme;
        $this->eventDispatcher = $ev;
        $this->acl = $acl;
    }
}