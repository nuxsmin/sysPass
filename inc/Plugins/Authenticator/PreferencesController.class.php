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

use InvalidArgumentException;
use SP\Controller\TabControllerBase;
use SP\Core\OldCrypt;
use SP\Core\Plugin\PluginBase;
use SP\Core\Plugin\PluginInterface;
use SP\Util\ArrayUtil;
use SP\Util\Util;

/**
 * Class Controller
 *
 * @package Plugins\Authenticator
 */
class PreferencesController
{
    /**
     * @var TabControllerBase
     */
    protected $Controller;
    /**
     * @var PluginBase
     */
    protected $Plugin;

    /**
     * Controller constructor.
     *
     * @param TabControllerBase $Controller
     * @param PluginInterface $Plugin
     */
    public function __construct(TabControllerBase $Controller, PluginInterface $Plugin)
    {
        $this->Controller = $Controller;
        $this->Plugin = $Plugin;
    }

    /**
     * Obtener la pestaña de seguridad
     */
    public function getSecurityTab()
    {
        $base = $this->Plugin->getThemeDir() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'userpreferences';

        // Datos del usuario de la sesión
        $UserData = $this->Controller->getUserData();

        // Buscar al usuario en los datos del plugin
        /** @var AuthenticatorData $AuthenticatorData */
        $AuthenticatorData = ArrayUtil::searchInObject($this->Plugin->getData(), 'userId', $UserData->getUserId(), new AuthenticatorData());

        $this->Controller->view->addTemplate('preferences-security', $base);

        try {
            $IV = null;

            if (!$AuthenticatorData->isTwofaEnabled()) {
                $IV = Util::generateRandomBytes();
                $AuthenticatorData->setIV($IV);
            } else {
                $IV = $AuthenticatorData->getIV();
            }

            Session::setUserData($AuthenticatorData);

            $twoFa = new Authenticator($UserData->getUserId(), $UserData->getUserLogin(), $IV);

            $this->Controller->view->assign('qrCode', !$AuthenticatorData->isTwofaEnabled() ? $twoFa->getUserQRCode() : '');
            $this->Controller->view->assign('userId', $UserData->getUserId());
            $this->Controller->view->assign('chk2FAEnabled', $AuthenticatorData->isTwofaEnabled());
            $this->Controller->view->assign('expireDays', $AuthenticatorData->getExpireDays());

            $this->Controller->view->assign('tabIndex', $this->Controller->addTab(_t('authenticator', 'Seguridad')), 'security');
            $this->Controller->view->assign('actionId', ActionController::ACTION_TWOFA_SAVE, 'security');
        } catch (InvalidArgumentException $e) {
        }
    }
}