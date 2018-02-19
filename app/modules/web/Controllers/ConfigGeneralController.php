<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Exceptions\SPException;
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
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function saveAction()
    {
        $messages = [];
        $configData = clone $this->config->getConfigData();

        // General
        $siteLang = Request::analyze('sitelang');
        $siteTheme = Request::analyze('sitetheme', 'material-blue');
        $sessionTimeout = Request::analyze('session_timeout', 300);
        $httpsEnabled = Request::analyze('https_enabled', false, false, true);
        $debugEnabled = Request::analyze('debug', false, false, true);
        $maintenanceEnabled = Request::analyze('maintenance', false, false, true);
        $checkUpdatesEnabled = Request::analyze('updates', false, false, true);
        $checkNoticesEnabled = Request::analyze('notices', false, false, true);
        $encryptSessionEnabled = Request::analyze('encryptsession', false, false, true);

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
        $logEnabled = Request::analyze('log_enabled', false, false, true);
        $syslogEnabled = Request::analyze('syslog_enabled', false, false, true);
        $remoteSyslogEnabled = Request::analyze('remotesyslog_enabled', false, false, true);
        $syslogServer = Request::analyze('remotesyslog_server');
        $syslogPort = Request::analyze('remotesyslog_port', 0);

        $configData->setLogEnabled($logEnabled);
        $configData->setSyslogEnabled($syslogEnabled);

        if ($remoteSyslogEnabled && (!$syslogServer || !$syslogPort)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de syslog remoto'));
        }

        if ($remoteSyslogEnabled) {
            $configData->setSyslogRemoteEnabled($remoteSyslogEnabled);
            $configData->setSyslogServer($syslogServer);
            $configData->setSyslogPort($syslogPort);
        } elseif ($configData->isSyslogEnabled()) {
            $configData->setSyslogRemoteEnabled(false);

            $messages[] = __u('Syslog remoto deshabilitado');
        }

        // Proxy
        $proxyEnabled = Request::analyze('proxy_enabled', false, false, true);
        $proxyServer = Request::analyze('proxy_server');
        $proxyPort = Request::analyze('proxy_port', 0);
        $proxyUser = Request::analyze('proxy_user');
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

            $messages[] = __u('Proxy habiltado');
        } elseif ($configData->isProxyEnabled()) {
            $configData->setProxyEnabled(false);

            $messages[] = __u('Proxy deshabilitado');
        }

        // Autentificación
        $authBasicEnabled = Request::analyze('authbasic_enabled', false, false, true);
        $authBasicAutologinEnabled = Request::analyze('authbasic_enabled', false, false, true);
        $authBasicDomain = Request::analyze('authbasic_domain');
        $authSsoDefaultGroup = Request::analyze('sso_defaultgroup', false, false, true);
        $authSsoDefaultProfile = Request::analyze('sso_defaultprofile', false, false, true);

        // Valores para Autentificación
        if ($authBasicEnabled) {
            $configData->setAuthBasicEnabled(true);
            $configData->setAuthBasicAutoLoginEnabled($authBasicAutologinEnabled);
            $configData->setAuthBasicDomain($authBasicDomain);
            $configData->setSsoDefaultGroup($authSsoDefaultGroup);
            $configData->setSsoDefaultProfile($authSsoDefaultProfile);

            $messages[] = __u('Auth Basic habilitada');
        } elseif ($configData->isAuthBasicEnabled()) {
            $configData->setAuthBasicEnabled(false);
            $configData->setAuthBasicAutoLoginEnabled(false);

            $messages[] = __u('Auth Basic deshabiltada');
        }

        $this->eventDispatcher->notifyEvent('save.config.general', new Event($this, $messages));

        $this->saveConfig($configData, $this->config);
    }

    protected function initialize()
    {
        try {
            if (!$this->checkAccess(ActionsInterface::CONFIG_GENERAL)) {
                throw new UnauthorizedPageException(SPException::INFO);
            }
        } catch (UnauthorizedPageException $e) {
            $this->returnJsonResponseException($e);
        }
    }
}