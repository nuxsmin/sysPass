<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\ActionsInterface;
use SP\Core\Plugin\PluginBase;

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
     * @param PluginBase        $Plugin
     */
    public function __construct(TabControllerBase $Controller, PluginBase $Plugin)
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

        $UserData = $this->Controller->getUserData();

        $this->Controller->view->addTemplate('preferences-security', $base);

        try {
            $twoFa = new Authenticator($UserData->getUserId(), $UserData->getUserLogin());

            $this->Controller->view->assign('qrCode', !$UserData->getUserPreferences()->isUse2Fa() ? $twoFa->getUserQRCode() : '');
            $this->Controller->view->assign('userId', $UserData->getUserId());
            $this->Controller->view->assign('chk2FAEnabled', $UserData->getUserPreferences()->isUse2Fa());

            $this->Controller->view->assign('tabIndex', $this->Controller->addTab(_('Seguridad')), 'security');
            $this->Controller->view->assign('actionId', ActionsInterface::ACTION_USR_PREFERENCES_SECURITY, 'security');
        } catch (InvalidArgumentException $e) {
        }
    }
}