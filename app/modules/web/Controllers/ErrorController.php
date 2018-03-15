<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use Klein\Klein;
use SP\Bootstrap;
use SP\Core\Install\Installer;
use SP\Mvc\View\Template;
use SP\Util\Util;

/**
 * Class ErrorController
 *
 * @package SP\Modules\Web\Controllers
 */
class ErrorController
{
    /**
     * @var Template
     */
    protected $view;
    /**
     * @var Klein
     */
    protected $router;

    /**
     * ErrorController constructor.
     * @param Template $view
     * @param Klein $router
     */
    public function __construct(Template $view, Klein $router)
    {
        $this->view = $view;
        $this->router = $router;
    }

    /**
     * @todo
     */
    public function indexAction()
    {
        $this->view->assign('startTime', microtime());

        $this->view->assign('appInfo', Util::getAppInfo());
        $this->view->assign('appVersion', Installer::VERSION_TEXT);
        $this->view->assign('logoIcon', Bootstrap::$WEBURI . '/public/images/logo_icon.png');
        $this->view->assign('logoNoText', Bootstrap::$WEBURI . '/public/images/logo_icon.svg');
        $this->view->assign('logo', Bootstrap::$WEBURI . '/public/images/logo_full_bg.png');
        $this->view->assign('logonobg', Bootstrap::$WEBURI . '/public/images/logo_full_nobg.png');
        $this->view->assign('lang', 'en');
        $this->view->assign('error', 'Error!');

        $this->router->response()->header('Content-Type', 'text/html; charset=UTF-8');
        $this->router->response()->header('Cache-Control', 'public, no-cache, max-age=0, must-revalidate');
        $this->router->response()->header('Pragma', 'public; max-age=0');
    }
}