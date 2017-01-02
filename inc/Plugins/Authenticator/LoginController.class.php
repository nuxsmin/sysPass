<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace Plugins\Authenticator;

use SP\Controller\ControllerBase;
use SP\Core\Plugin\PluginBase;
use SP\Http\Request;

/**
 * Class LoginController
 *
 * @package Plugins\Authenticator
 */
class LoginController
{
    /**
     * @var ControllerBase
     */
    protected $Controller;
    /**
     * @var PluginBase
     */
    protected $Plugin;

    /**
     * Controller constructor.
     *
     * @param ControllerBase $Controller
     * @param PluginBase        $Plugin
     */
    public function __construct(ControllerBase $Controller, PluginBase $Plugin)
    {
        $this->Controller = $Controller;
        $this->Plugin = $Plugin;
    }

    /**
     * Obtener los datos para el interface de autentificación en 2 pasos
     */
    public function get2FA()
    {
        $base = $this->Plugin->getThemeDir() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'main';

        $this->Controller->view->addTemplate('body-header');

        if (Request::analyze('f', 0) === 1) {
            $this->Controller->view->addTemplate('login-2fa', $base);

            $this->Controller->view->assign('action', Request::analyze('a'));
            $this->Controller->view->assign('userId', Request::analyze('i'));
            $this->Controller->view->assign('time', Request::analyze('t'));
        } else {
            $this->Controller->showError(ControllerBase::ERR_UNAVAILABLE, false);
        }

        $this->Controller->view->addTemplate('body-footer');
        $this->Controller->view->addTemplate('body-end');

        $this->Controller->view();
        exit();
    }
}