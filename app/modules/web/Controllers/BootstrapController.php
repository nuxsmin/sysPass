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

use SP\Bootstrap;
use SP\Core\CryptPKI;
use SP\Core\Plugin\PluginUtil;
use SP\Http\Cookies;
use SP\Http\Response;
use SP\Providers\Auth\Browser\Browser;

/**
 * Class BootstrapController
 *
 * @package SP\Modules\Web\Controllers
 */
class BootstrapController extends SimpleControllerBase
{
    /**
     * Returns environment data
     *
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getEnvironmentAction()
    {
        $configData = $this->config->getConfigData();

        $data = [
            'lang' => require CONFIG_PATH . DIRECTORY_SEPARATOR . 'strings.js.inc',
            'locale' => $configData->getSiteLang(),
            'app_root' => Bootstrap::$WEBURI,
            'max_file_size' => $configData->getFilesAllowedSize(),
            'check_updates' => $this->session->getAuthCompleted()
                && ($configData->isCheckUpdates() || $configData->isChecknotices())
                && ($this->session->getUserData()->getIsAdminApp() || $configData->isDemoEnabled()),
            'timezone' => date_default_timezone_get(),
            'debug' => DEBUG || $configData->isDebug(),
            'cookies_enabled' => Cookies::checkCookies(),
            'plugins' => PluginUtil::getEnabledPlugins(),
            'loggedin' => $this->session->isLoggedIn(),
            'authbasic_autologin' => Browser::getServerAuthUser() && $configData->isAuthBasicAutoLoginEnabled(),
            'pk' => $this->session->getPublicKey() ?: (new CryptPKI())->getPublicKey()
        ];

        Response::printJson($data, 0);
    }
}