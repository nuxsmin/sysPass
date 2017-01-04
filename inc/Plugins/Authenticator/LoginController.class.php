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
use SP\Core\Init;
use SP\Core\Plugin\PluginBase;
use SP\Core\Session as CoreSession;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Util\Json;

/**
 * Class LoginController
 *
 * @package Plugins\Authenticator
 */
class LoginController
{
    /**
     * @var PluginBase
     */
    protected $Plugin;

    /**
     * Controller constructor.
     *
     * @param PluginBase        $Plugin
     */
    public function __construct(PluginBase $Plugin)
    {
        $this->Plugin = $Plugin;
    }

    /**
     * Obtener los datos para el interface de autentificación en 2 pasos
     *
     * @param ControllerBase $Controller
     */
    public function get2FA(ControllerBase $Controller)
    {
        $base = $this->Plugin->getThemeDir() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'main';

        $Controller->view->addTemplate('body-header');

        if (Request::analyze('f', 0) === 1) {
            $Controller->view->addTemplate('login-2fa', $base);

            $Controller->view->assign('action', Request::analyze('a'));
            $Controller->view->assign('userId', Request::analyze('i'));
            $Controller->view->assign('time', Request::analyze('t'));

            $Controller->view->assign('actionId', ActionController::ACTION_TWOFA_CHECKCODE);
        } else {
            $Controller->showError(ControllerBase::ERR_UNAVAILABLE, false);
        }

        $Controller->view->addTemplate('body-footer');
        $Controller->view->addTemplate('body-end');

        $Controller->view();
        exit();
    }

    /**
     * Comprobar 2FA en el login
     */
    public function checkLogin()
    {
        /** @var AuthenticatorData[] $data */
        $data = $this->Plugin->getData();

        $userId = CoreSession::getUserData()->getUserId();

        if (isset($data[$userId]) && $data[$userId]->isTwofaEnabled()) {
            Session::setTwoFApass(false);
            CoreSession::setAuthCompleted(false);

            $data = ['url' => Init::$WEBURI . '/index.php?a=2fa&i=' . $userId . '&t=' . time() . '&f=1'];

            $JsonResponse = new JsonResponse();
            $JsonResponse->setData($data);
            $JsonResponse->setStatus(0);
            Json::returnJson($JsonResponse);
        } else {
            Session::setTwoFApass(true);
        }
    }
}