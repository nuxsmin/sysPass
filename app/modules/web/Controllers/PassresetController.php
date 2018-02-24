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

use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Util\ErrorUtil;

/**
 * Class PassresetController
 *
 * @package SP\Modules\Web\Controllers
 */
class PassresetController extends ControllerBase
{
    /**
     * Password reset action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    public function indexAction()
    {
        $LayoutHelper = $this->dic->get(LayoutHelper::class);
        $LayoutHelper->getPublicLayout('passreset', 'passreset');

        if ($this->configData->isMailEnabled() || Request::analyze('f', 0) === 1) {
            $this->view->assign('login', Request::analyze('login'));
            $this->view->assign('email', Request::analyze('email'));

            $this->view->assign('action', Request::analyze('a'));
            $this->view->assign('hash', Request::analyze('h'));
            $this->view->assign('time', Request::analyze('t'));

            $this->view->assign('passReset', $this->view->action === 'passreset' && !empty($this->view->hash) && !empty($this->view->time));
        } else {
            ErrorUtil::showErrorInView($this->view, self::ERR_UNAVAILABLE);
        }

        $this->view();
    }
}