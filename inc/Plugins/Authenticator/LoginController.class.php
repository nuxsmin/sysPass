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
use SP\Core\Messages\NoticeMessage;
use SP\Core\Plugin\PluginBase;
use SP\Core\Session as CoreSession;
use SP\DataModel\NoticeData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Mgmt\Notices\Notice;
use SP\Util\Json;

/**
 * Class LoginController
 *
 * @package Plugins\Authenticator
 */
class LoginController
{
    const WARNING_TIME = 432000;

    /**
     * @var PluginBase
     */
    protected $Plugin;

    /**
     * Controller constructor.
     *
     * @param PluginBase $Plugin
     */
    public function __construct(PluginBase $Plugin)
    {
        $this->Plugin = $Plugin;
    }

    /**
     * Obtener los datos para el interface de autentificación en 2 pasos
     *
     * @param ControllerBase $Controller
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function get2FA(ControllerBase $Controller)
    {
        $Controller->view->addTemplate('body-header');

        if (Request::analyze('f', 0) === 1) {
            $base = $this->Plugin->getThemeDir() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'main';

            $Controller->view->addTemplate('login-2fa', $base);

            $Controller->view->assign('action', Request::analyze('a'));
            $Controller->view->assign('userId', Request::analyze('i', 0));
            $Controller->view->assign('time', Request::analyze('t', 0));

            $Controller->view->assign('actionId', ActionController::ACTION_TWOFA_CHECKCODE);

            $this->checkExpireTime();
        } else {
            $Controller->showError(ControllerBase::ERR_UNAVAILABLE, false);
        }

        $Controller->view->addTemplate('body-footer');
        $Controller->view->addTemplate('body-end');

        $Controller->view();
        exit();
    }

    /**
     * Comprobar la caducidad del código
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function checkExpireTime()
    {
        /** @var AuthenticatorData[] $data */
        $data = $this->Plugin->getData();

        $userId = Request::analyze('i', 0);

        if (!isset($data[$userId]) || empty($data[$userId]->getExpireDays())) {
            return;
        }

        if (count(Notice::getItem()->getByUserCurrentDate()) > 0) {
            return;
        }

        $expireTime = $data[$userId]->getDate() + ($data[$userId]->getExpireDays() * 86400);
        $timeRemaining = $expireTime - time();

        $NoticeData = Notice::getItem()->getItemData();
        $NoticeData->setNoticeComponent($this->Plugin->getName());
        $NoticeData->setNoticeUserId($userId);
        $NoticeData->setNoticeType(_t('authenticator', 'Aviso Caducidad'));

        $Message = new NoticeMessage();

        if ($timeRemaining <= self::WARNING_TIME) {
            $Message->addDescription(sprintf(_t('authenticator', 'El código 2FA se ha de restablecer en %d días'), $timeRemaining / 86400));

            $NoticeData->setNoticeDescription($Message);

            Notice::getItem($NoticeData)->add();
        } elseif (time() > $expireTime) {
            $Message->addDescription(_t('authenticator', 'El código 2FA ha caducado. Es necesario restablecerlo desde las preferencias'));

            $NoticeData->setNoticeDescription($Message);

            Notice::getItem($NoticeData)->add();
        }
    }

    /**
     * Comprobar 2FA en el login
     *
     * @throws \SP\Core\Exceptions\SPException
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