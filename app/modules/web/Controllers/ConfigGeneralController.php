<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Config\ConfigUtil;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

/**
 * Class ConfigGeneral
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigGeneralController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * saveAction
     */
    public function saveAction()
    {
        $configData = $this->config->getConfigData();
        $eventMessage = EventMessage::factory();

        // General
        $siteLang = Request::analyzeString('sitelang');
        $siteTheme = Request::analyzeString('sitetheme', 'material-blue');
        $sessionTimeout = Request::analyzeInt('session_timeout', 300);
        $httpsEnabled = Request::analyzeBool('https_enabled', false);
        $debugEnabled = Request::analyzeBool('debug', false);
        $maintenanceEnabled = Request::analyzeBool('maintenance', false);
        $checkUpdatesEnabled = Request::analyzeBool('updates', false);
        $checkNoticesEnabled = Request::analyzeBool('notices', false);
        $encryptSessionEnabled = Request::analyzeBool('encryptsession', false);

        $configData->setSiteLang($siteLang);
        $configData->setSiteTheme($siteTheme);
        $configData->setSessionTimeout($sessionTimeout);
        $configData->setHttpsEnabled($httpsEnabled);
        $configData->setDebug($debugEnabled);
        $configData->setMaintenance($maintenanceEnabled);
        $configData->setCheckUpdates($checkUpdatesEnabled);
        $configData->setChecknotices($checkNoticesEnabled);
        $configData->setEncryptSession($encryptSessionEnabled);

        // Events
        $logEnabled = Request::analyzeBool('log_enabled', false);
        $syslogEnabled = Request::analyzeBool('syslog_enabled', false);
        $remoteSyslogEnabled = Request::analyzeBool('remotesyslog_enabled', false);
        $syslogServer = Request::analyzeString('remotesyslog_server');
        $syslogPort = Request::analyzeInt('remotesyslog_port', 0);

        $configData->setLogEnabled($logEnabled);
        $configData->setLogEvents(Request::analyzeArray('log_events', function ($items) {
            return ConfigUtil::eventsAdapter($items);
        }, []));

        $configData->setSyslogEnabled($syslogEnabled);

        if ($remoteSyslogEnabled) {
            if (!$syslogServer || !$syslogPort) {
                $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de syslog remoto'));
            }

            $configData->setSyslogRemoteEnabled(true);
            $configData->setSyslogServer($syslogServer);
            $configData->setSyslogPort($syslogPort);

            if ($configData->isSyslogRemoteEnabled() === false) {
                $eventMessage->addDescription(__u('Syslog remoto habilitado'));
            }
        } elseif ($remoteSyslogEnabled === false && $configData->isSyslogEnabled()) {
            $configData->setSyslogRemoteEnabled(false);

            $eventMessage->addDescription(__u('Syslog remoto deshabilitado'));
        }

        // Proxy
        $proxyEnabled = Request::analyzeBool('proxy_enabled', false);
        $proxyServer = Request::analyzeString('proxy_server');
        $proxyPort = Request::analyzeInt('proxy_port', 8080);
        $proxyUser = Request::analyzeString('proxy_user');
        $proxyPass = Request::analyzeEncrypted('proxy_pass');


        // Valores para Proxy
        if ($proxyEnabled && (!$proxyServer || !$proxyPort)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de Proxy'));
        }

        if ($proxyEnabled) {
            $configData->setProxyEnabled(true);
            $configData->setProxyServer($proxyServer);
            $configData->setProxyPort($proxyPort);
            $configData->setProxyUser($proxyUser);
            $configData->setProxyPass($proxyPass);

            if ($configData->isProxyEnabled() === false) {
                $eventMessage->addDescription(__u('Proxy habiltado'));
            }
        } elseif ($proxyEnabled === false && $configData->isProxyEnabled()) {
            $configData->setProxyEnabled(false);

            $eventMessage->addDescription(__u('Proxy deshabilitado'));
        }

        // Autentificación
        $authBasicEnabled = Request::analyzeBool('authbasic_enabled', false);
        $authBasicAutologinEnabled = Request::analyzeBool('authbasicautologin_enabled', false);
        $authBasicDomain = Request::analyzeString('authbasic_domain');
        $authSsoDefaultGroup = Request::analyzeInt('sso_defaultgroup');
        $authSsoDefaultProfile = Request::analyzeInt('sso_defaultprofile');

        // Valores para Autentificación
        if ($authBasicEnabled) {
            $configData->setAuthBasicEnabled(true);
            $configData->setAuthBasicAutoLoginEnabled($authBasicAutologinEnabled);
            $configData->setAuthBasicDomain($authBasicDomain);
            $configData->setSsoDefaultGroup($authSsoDefaultGroup);
            $configData->setSsoDefaultProfile($authSsoDefaultProfile);

            if ($configData->isAuthBasicEnabled() === false) {
                $eventMessage->addDescription(__u('Auth Basic habilitada'));
            }
        } elseif ($authBasicEnabled === false && $configData->isAuthBasicEnabled()) {
            $configData->setAuthBasicEnabled(false);
            $configData->setAuthBasicAutoLoginEnabled(false);

            $eventMessage->addDescription(__u('Auth Basic deshabiltada'));
        }

        $this->saveConfig($configData, $this->config, function () use ($eventMessage) {
            $this->eventDispatcher->notifyEvent('save.config.general', new Event($this, $eventMessage));
        });
    }

    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::CONFIG_GENERAL);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}