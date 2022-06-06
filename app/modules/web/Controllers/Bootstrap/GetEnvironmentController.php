<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers\Bootstrap;

use Exception;
use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Crypt\CryptPKI;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Import\Services\ImportService;
use SP\Http\RequestInterface;
use SP\Infrastructure\File\FileException;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Plugin\PluginManager;
use SP\Providers\Auth\Browser\BrowserAuthInterface;

/**
 * Class GetEnvironmentController
 *
 * @package SP\Modules\Web\Controllers
 */
final class GetEnvironmentController extends SimpleControllerBase
{
    use JsonTrait;

    private CryptPKI             $cryptPKI;
    private PluginManager        $pluginManager;
    private BrowserAuthInterface $browser;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        CryptPKI $cryptPKI,
        PluginManager $pluginManager,
        BrowserAuthInterface $browser
    ) {
        $this->cryptPKI = $cryptPKI;
        $this->pluginManager = $pluginManager;
        $this->browser = $browser;

        parent::__construct($application, $theme, $router, $acl, $request, $extensionChecker);
    }

    /**
     * Returns environment data
     *
     * @return bool
     * @throws \JsonException
     */
    public function getEnvironmentAction(): bool
    {
        $checkStatus = $this->session->getAuthCompleted()
                       && ($this->session->getUserData()->getIsAdminApp()
                           || $this->configData->isDemoEnabled());

        $data = [
            'lang'                => $this->getJsLang(),
            'locale'              => $this->configData->getSiteLang(),
            'app_root'            => BootstrapBase::$WEBURI,
            'max_file_size'       => $this->configData->getFilesAllowedSize(),
            'check_updates'       => $checkStatus && $this->configData->isCheckUpdates(),
            'check_notices'       => $checkStatus && $this->configData->isCheckNotices(),
            'check_notifications' => $this->getNotificationsEnabled(),
            'timezone'            => date_default_timezone_get(),
            'debug'               => DEBUG || $this->configData->isDebug(),
            'cookies_enabled'     => $this->getCookiesEnabled(),
            'plugins'             => $this->getPlugins(),
            'loggedin'            => $this->session->isLoggedIn(),
            'authbasic_autologin' => $this->getAuthBasicAutologinEnabled(),
            'pki_key'             => $this->getPublicKey(),
            'pki_max_size'        => CryptPKI::getMaxDataSize(),
            'import_allowed_mime' => ImportService::ALLOWED_MIME,
            'files_allowed_mime'  => $this->configData->getFilesAllowedMime(),
            'session_timeout'     => $this->configData->getSessionTimeout(),
            'csrf'                => $this->getCSRF(),
        ];

        return $this->returnJsonResponseData($data);
    }

    /**
     * @return array
     */
    private function getJsLang(): array
    {
        return require RESOURCES_PATH.DIRECTORY_SEPARATOR.'strings.js.inc';
    }

    /**
     * @return bool
     */
    private function getNotificationsEnabled(): bool
    {
        if ($this->session->isLoggedIn()) {
            return $this->session
                ->getUserData()
                ->getPreferences()
                ->isCheckNotifications();
        }

        return false;
    }

    /**
     * @return bool
     */
    private function getCookiesEnabled(): bool
    {
        return $this->router->request()->cookies()->get(session_name()) !== null;
    }

    /**
     * @return array
     */
    private function getPlugins(): array
    {
        try {
            return $this->pluginManager->getEnabledPlugins();
        } catch (Exception $e) {
            processException($e);
        }

        return [];
    }

    /**
     * @return bool
     */
    private function getAuthBasicAutologinEnabled(): bool
    {
        return $this->browser->getServerAuthUser() !== null && $this->configData->isAuthBasicAutoLoginEnabled();
    }

    /**
     * @return string
     */
    private function getPublicKey(): string
    {
        try {
            return $this->session->getPublicKey() ?: $this->cryptPKI->getPublicKey();
        } catch (FileException $e) {
            processException($e);

            return '';
        }
    }

    /**
     * Generate the CSRF token if not set
     *
     * @return string|null
     */
    private function getCSRF(): ?string
    {
        logger(sprintf('CSRF key (get): %s', $this->session->getCSRF()));

        return $this->session->getCSRF();
    }
}