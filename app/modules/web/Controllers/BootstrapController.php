<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Bootstrap;
use SP\Core\Crypt\CryptPKI;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Plugin\PluginManager;
use SP\Providers\Auth\Browser\Browser;
use SP\Services\Import\ImportService;
use SP\Storage\File\FileException;

/**
 * Class BootstrapController
 *
 * @package SP\Modules\Web\Controllers
 */
final class BootstrapController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * Returns environment data
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getEnvironmentAction()
    {
        $checkStatus = $this->session->getAuthCompleted()
            && ($this->session->getUserData()->getIsAdminApp() || $this->configData->isDemoEnabled());

        $data = [
            'lang' => $this->getJsLang(),
            'locale' => $this->configData->getSiteLang(),
            'app_root' => Bootstrap::$WEBURI,
            'max_file_size' => $this->configData->getFilesAllowedSize(),
            'check_updates' => $checkStatus && $this->configData->isCheckUpdates(),
            'check_notices' => $checkStatus && $this->configData->isChecknotices(),
            'check_notifications' => $this->getNotificationsEnabled(),
            'timezone' => date_default_timezone_get(),
            'debug' => DEBUG || $this->configData->isDebug(),
            'cookies_enabled' => $this->getCookiesEnabled(),
            'plugins' => $this->getPlugins(),
            'loggedin' => $this->session->isLoggedIn(),
            'authbasic_autologin' => $this->getAuthBasicAutologinEnabled(),
            'pki_key' => $this->getPublicKey(),
            'pki_max_size' => CryptPKI::getMaxDataSize(),
            'import_allowed_mime' => ImportService::ALLOWED_MIME,
            'files_allowed_mime' => $this->configData->getFilesAllowedMime(),
            'session_timeout' => $this->configData->getSessionTimeout()
        ];

        return $this->returnJsonResponseData($data);
    }

    /**
     * @return array
     */
    private function getJsLang()
    {
        return require RESOURCES_PATH . DIRECTORY_SEPARATOR . 'strings.js.inc';
    }

    /**
     * @return bool
     */
    private function getNotificationsEnabled()
    {
        if ($this->session->isLoggedIn()) {
            return $this->session->getUserData()->getPreferences()->isCheckNotifications();
        }

        return false;
    }

    /**
     * @return bool
     */
    private function getCookiesEnabled()
    {
        return $this->router->request()->cookies()->get(session_name()) !== null;
    }

    /**
     * @return array
     */
    private function getPlugins()
    {
        try {
            return $this->dic->get(PluginManager::class)->getEnabledPlugins();
        } catch (Exception $e) {
            processException($e);
        }

        return [];
    }

    /**
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function getAuthBasicAutologinEnabled()
    {
        return $this->dic->get(Browser::class)->getServerAuthUser() !== null
            && $this->configData->isAuthBasicAutoLoginEnabled();
    }

    /**
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function getPublicKey()
    {
        try {
            return $this->session->getPublicKey() ?: $this->dic->get(CryptPKI::class)->getPublicKey();
        } catch (FileException $e) {
            processException($e);

            return '';
        }
    }

    /**
     * @return void
     */
    protected function initialize()
    {
        // TODO: Implement initialize() method.
    }
}