<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Application;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Crypt\CryptPKI;
use SP\Domain\Auth\Providers\Browser\BrowserAuthService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Crypt\CryptPKIHandler;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Services\ImportStrategy;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;

use function SP\logger;
use function SP\processException;

/**
 * Class GetEnvironmentController
 */
final class GetEnvironmentController extends SimpleControllerBase
{
    public function __construct(
        Application                           $application,
        SimpleControllerHelper                $simpleControllerHelper,
        private readonly CryptPKIHandler      $cryptPKI,
        private readonly BrowserAuthService   $browser,
        private readonly PluginManagerService $pluginManagerService,
        private readonly PathsContext         $pathsContext
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    /**
     * Returns environment data
     *
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function getEnvironmentAction(): ActionResponse
    {
        $checkStatus = $this->session->getAuthCompleted()
                       && ($this->session->getUserData()->isAdminApp
                           || $this->configData->isDemoEnabled());

        $data = [
            'lang' => $this->getJsLang(),
            'locale' => $this->configData->getSiteLang(),
            'app_root' => $this->uriContext->getWebUri(),
            'max_file_size' => $this->configData->getFilesAllowedSize(),
            'check_updates' => $checkStatus && $this->configData->isCheckUpdates(),
            'check_notices' => $checkStatus && $this->configData->isCheckNotices(),
            'check_notifications' => $this->getNotificationsEnabled(),
            'timezone' => date_default_timezone_get(),
            'debug' => DEBUG || $this->configData->isDebug(),
            'cookies_enabled' => $this->getCookiesEnabled(),
            'plugins' => $this->getPlugins(),
            'loggedin' => $this->session->isLoggedIn(),
            'authbasic_autologin' => $this->getAuthBasicAutologinEnabled(),
            'pki_key' => $this->getPublicKey(),
            'pki_max_size' => CryptPKI::getMaxDataSize(),
            'import_allowed_mime' => ImportStrategy::ALLOWED_MIME,
            'files_allowed_mime' => $this->configData->getFilesAllowedMime(),
            'session_timeout' => $this->configData->getSessionTimeout(),
            'csrf' => $this->getCSRF(),
        ];

        return ActionResponse::ok('', $data);
    }

    /**
     * @return array
     * @throws FileException
     * @throws InvalidClassException
     */
    private function getJsLang(): array
    {
        return FileSystem::require(FileSystem::buildPath($this->pathsContext[Path::RESOURCES], 'strings.js.inc'));
    }

    /**
     * @return bool
     */
    private function getNotificationsEnabled(): bool
    {
        if ($this->session->isLoggedIn()) {
            return $this->session->getUserData()->preferences->isCheckNotifications();
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
            return $this->pluginManagerService->getEnabled();
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
