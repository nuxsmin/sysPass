<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\ConfigGeneral;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigUtil;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;

/**
 * Class SaveController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveController extends SimpleControllerBase
{
    use ConfigTrait;

    public function __construct(
        Application            $application,
        SimpleControllerHelper $simpleControllerHelper,
        private readonly AppLockHandler $appLock
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }


    /**
     * @throws SPException
     * @throws ValidationException
     */
    #[Action(ResponseType::JSON)]
    public function saveAction(): ActionResponse
    {
        $configData = $this->config->getConfigData();
        $eventMessage = EventMessage::build();

        $this->handleGeneralConfig($configData);
        $this->handleEventsConfig($configData, $eventMessage);
        $this->handleProxyConfig($configData, $eventMessage);
        $this->handleAuthConfig($configData, $eventMessage);

        return $this->saveConfig(
            $configData,
            $this->config,
            function () use ($eventMessage, $configData) {
                if ($configData->isMaintenance()) {
                    $this->appLock->lock($this->session->getUserData()->id, 'config');
                } elseif ($this->appLock->getLock() !== false) {
                    $this->appLock->unlock();
                }

                $this->eventDispatcher->notify('save.config.general', new Event($this, $eventMessage));
            }
        );
    }

    private function handleGeneralConfig(ConfigDataInterface $configData): void
    {
        $siteLang = $this->request->analyzeString('site_lang');
        $siteTheme = $this->request->analyzeString('site_theme', 'material-blue');
        $sessionTimeout = $this->request->analyzeInt('session_timeout', 300);
        $applicationUrl = $this->request->analyzeString('app_url');
        $httpsEnabled = $this->request->analyzeBool('https_enabled', false);
        $debugEnabled = $this->request->analyzeBool('debug_enabled', false);
        $maintenanceEnabled = $this->request->analyzeBool('maintenance_enabled', false);
        $checkUpdatesEnabled = $this->request->analyzeBool('check_updates_enabled', false);
        $checkNoticesEnabled = $this->request->analyzeBool('check_notices_enabled', false);
        $encryptSessionEnabled = $this->request->analyzeBool('encrypt_session_enabled', false);

        $configData->setSiteLang($siteLang);
        $configData->setSiteTheme($siteTheme);
        $configData->setSessionTimeout($sessionTimeout);
        $configData->setApplicationUrl($applicationUrl);
        $configData->setHttpsEnabled($httpsEnabled);
        $configData->setDebug($debugEnabled);
        $configData->setMaintenance($maintenanceEnabled);
        $configData->setCheckUpdates($checkUpdatesEnabled);
        $configData->setCheckNotices($checkNoticesEnabled);
        $configData->setEncryptSession($encryptSessionEnabled);
    }

    /**
     * @throws ValidationException
     */
    private function handleEventsConfig(ConfigDataInterface $configData, EventMessage $eventMessage): void
    {
        $logEnabled = $this->request->analyzeBool('log_enabled', false);
        $syslogEnabled = $this->request->analyzeBool('syslog_enabled', false);
        $remoteSyslogEnabled = $this->request->analyzeBool('syslog_remote_enabled', false);
        $syslogServer = $this->request->analyzeString('syslog_remote_server');
        $syslogPort = $this->request->analyzeInt('syslog_remote_port', 0);

        $configData->setLogEnabled($logEnabled);
        $configData->setLogEvents(
            $this->request->analyzeArray(
                'log_events',
                fn($items) => ConfigUtil::eventsAdapter($items),
                []
            )
        );

        $configData->setSyslogEnabled($syslogEnabled);

        if ($remoteSyslogEnabled) {
            if (!$syslogServer || !$syslogPort) {
                throw new ValidationException(SPException::ERROR, __u('Missing remote syslog parameters'));
            }

            $configData->setSyslogRemoteEnabled(true);
            $configData->setSyslogServer($syslogServer);
            $configData->setSyslogPort($syslogPort);

            if ($configData->isSyslogRemoteEnabled() === false) {
                $eventMessage->addDescription(__u('Remote syslog enabled'));
            }
        } elseif ($configData->isSyslogRemoteEnabled()) {
            $configData->setSyslogRemoteEnabled(false);

            $eventMessage->addDescription(__u('Remote syslog disabled'));
        }
    }

    /**
     * @throws ValidationException
     */
    private function handleProxyConfig(ConfigDataInterface $configData, EventMessage $eventMessage): void
    {
        $proxyEnabled = $this->request->analyzeBool('proxy_enabled', false);
        $proxyServer = $this->request->analyzeString('proxy_server');
        $proxyPort = $this->request->analyzeInt('proxy_port', 8080);
        $proxyUser = $this->request->analyzeString('proxy_user');
        $proxyPass = $this->request->analyzeEncrypted('proxy_pass');

        if ($proxyEnabled && (!$proxyServer || !$proxyPort)) {
            throw new ValidationException(SPException::ERROR, __u('Missing Proxy parameters '));
        }

        if ($proxyEnabled) {
            $configData->setProxyEnabled(true);
            $configData->setProxyServer($proxyServer);
            $configData->setProxyPort($proxyPort);
            $configData->setProxyUser($proxyUser);

            if ($proxyPass !== '***') {
                $configData->setProxyPass($proxyPass);
            }

            if ($configData->isProxyEnabled() === false) {
                $eventMessage->addDescription(__u('Proxy enabled'));
            }
        } elseif ($configData->isProxyEnabled()) {
            $configData->setProxyEnabled(false);

            $eventMessage->addDescription(__u('Proxy disabled'));
        }
    }

    private function handleAuthConfig(ConfigDataInterface $configData, EventMessage $eventMessage): void
    {
        $authBasicEnabled = $this->request->analyzeBool('authbasic_enabled', false);
        $authBasicAutologinEnabled = $this->request->analyzeBool('authbasic_autologin_enabled', false);
        $authBasicDomain = $this->request->analyzeString('authbasic_domain');
        $authSsoDefaultGroup = $this->request->analyzeInt('sso_default_group');
        $authSsoDefaultProfile = $this->request->analyzeInt('sso_default_profile');

        if ($authBasicEnabled) {
            $configData->setAuthBasicEnabled(true);
            $configData->setAuthBasicAutoLoginEnabled($authBasicAutologinEnabled);
            $configData->setAuthBasicDomain($authBasicDomain);
            $configData->setSsoDefaultGroup($authSsoDefaultGroup);
            $configData->setSsoDefaultProfile($authSsoDefaultProfile);

            if ($configData->isAuthBasicEnabled() === false) {
                $eventMessage->addDescription(__u('Auth Basic enabled'));
            }
        } elseif ($configData->isAuthBasicEnabled()) {
            $configData->setAuthBasicEnabled(false);
            $configData->setAuthBasicAutoLoginEnabled(false);

            $eventMessage->addDescription(__u('Auth Basic disabled'));
        }
    }

    /**
     * @throws SPException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checks();
        $this->checkAccess(AclActionsInterface::CONFIG_GENERAL);
    }
}
