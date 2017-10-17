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

use SP\Controller\ControllerBase;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;

/**
 * Class PassresetController
 *
 * @package SP\Modules\Web\Controllers
 */
class PassresetController extends ControllerBase
{
    /**
     * Password reset action
     */
    public function indexAction()
    {
        $LayoutHelper = new LayoutHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
        $LayoutHelper->initBody();
        $LayoutHelper->setPage('passreset');

        $this->view->addPartial('body-header');

        if ($this->configData->isMailEnabled() || Request::analyze('f', 0) === 1) {
            $this->view->addTemplate('passreset');

            $this->view->assign('login', Request::analyze('login'));
            $this->view->assign('email', Request::analyze('email'));

            $this->view->assign('action', Request::analyze('a'));
            $this->view->assign('hash', Request::analyze('h'));
            $this->view->assign('time', Request::analyze('t'));

            $this->view->assign('passReset', $this->view->action === 'passreset' && !empty($this->view->hash) && !empty($this->view->time));
        } else {
            $this->showError(self::ERR_UNAVAILABLE, false);
        }

        $this->view->addPartial('body-footer');
        $this->view->addPartial('body-end');

        $this->view();
    }
}