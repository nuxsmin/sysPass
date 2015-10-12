<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

use SP\Config\ConfigDB;
use SP\Core\CryptMasterPass;
use SP\Core\SessionUtil;
use SP\Mgmt\User\UserPass;
use SP\Util\Checks;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

\SP\Http\Request::checkReferer('POST');

if (!\SP\Core\Init::isLoggedIn()) {
    \SP\Http\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = \SP\Http\Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    \SP\Http\Response::printJSON(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$actionId = \SP\Http\Request::analyze('actionId', 0);
$activeTab = \SP\Http\Request::analyze('activeTab', 0);

$doActionOnClose = "sysPassUtil.Common.doAction($actionId,'',$activeTab);";

if ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_GENERAL
    || $actionId === \SP\Core\ActionsInterface::ACTION_CFG_WIKI
    || $actionId === \SP\Core\ActionsInterface::ACTION_CFG_LDAP
    || $actionId === \SP\Core\ActionsInterface::ACTION_CFG_MAIL
) {
    $Log = \SP\Log\Log::newLog(_('Modificar Configuración'));

    if ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_GENERAL) {
        // General
        $siteLang = \SP\Http\Request::analyze('sitelang');
        $siteTheme = \SP\Http\Request::analyze('sitetheme', 'material-blue');
        $sessionTimeout = \SP\Http\Request::analyze('session_timeout', 300);
        $httpsEnabled = \SP\Http\Request::analyze('https_enabled', false, false, true);
        $logEnabled = \SP\Http\Request::analyze('log_enabled', false, false, true);
        $debugEnabled = \SP\Http\Request::analyze('debug', false, false, true);
        $maintenanceEnabled = \SP\Http\Request::analyze('maintenance', false, false, true);
        $checkUpdatesEnabled = \SP\Http\Request::analyze('updates', false, false, true);
        $checkNoticesEnabled = \SP\Http\Request::analyze('notices', false, false, true);

        \SP\Config\Config::setCacheConfigValue('sitelang', $siteLang);
        \SP\Config\Config::setCacheConfigValue('sitetheme', $siteTheme);
        \SP\Config\Config::setCacheConfigValue('session_timeout', $sessionTimeout);
        \SP\Config\Config::setCacheConfigValue('https_enabled', $httpsEnabled);
        \SP\Config\Config::setCacheConfigValue('log_enabled', $logEnabled);
        \SP\Config\Config::setCacheConfigValue('debug', $debugEnabled);
        \SP\Config\Config::setCacheConfigValue('maintenance', $maintenanceEnabled);
        \SP\Config\Config::setCacheConfigValue('checkupdates', $checkUpdatesEnabled);
        \SP\Config\Config::setCacheConfigValue('checknotices', $checkNoticesEnabled);

        // Accounts
        $globalSearchEnabled = \SP\Http\Request::analyze('globalsearch', false, false, true);
        $accountPassToImageEnabled = \SP\Http\Request::analyze('account_passtoimage', false, false, true);
        $accountLinkEnabled = \SP\Http\Request::analyze('account_link', false, false, true);
        $accountCount = \SP\Http\Request::analyze('account_count', 10);
        $resultsAsCardsEnabled = \SP\Http\Request::analyze('resultsascards', false, false, true);

        \SP\Config\Config::setCacheConfigValue('globalsearch', $globalSearchEnabled);
        \SP\Config\Config::setCacheConfigValue('account_passtoimage', $accountPassToImageEnabled);
        \SP\Config\Config::setCacheConfigValue('account_link', $accountLinkEnabled);
        \SP\Config\Config::setCacheConfigValue('account_count', $accountCount);
        \SP\Config\Config::setCacheConfigValue('resultsascards', $resultsAsCardsEnabled);

        // Files
        $filesEnabled = \SP\Http\Request::analyze('files_enabled', false, false, true);
        $filesAllowedSize = \SP\Http\Request::analyze('files_allowed_size', 1024);
        $filesAllowedExts = \SP\Http\Request::analyze('files_allowed_exts');

        \SP\Config\Config::setCacheConfigValue('files_enabled', $filesEnabled);
        \SP\Config\Config::setCacheConfigValue('files_allowed_size', $filesAllowedSize);
        \SP\Config\Config::setCacheConfigValue('files_allowed_exts', $filesAllowedExts);

        if ($filesEnabled && $filesAllowedSize >= 16384) {
            \SP\Http\Response::printJSON(_('El tamaño máximo por archivo es de 16MB'));
        }

        // Public Links
        $pubLinksEnabled = \SP\Http\Request::analyze('publinks_enabled', false, false, true);
        $pubLinksImageEnabled = \SP\Http\Request::analyze('publinks_image_enabled', false, false, true);
        $pubLinksMaxTime = \SP\Http\Request::analyze('publinks_maxtime', 10);
        $pubLinksMaxViews = \SP\Http\Request::analyze('publinks_maxviews', 3);

        \SP\Config\Config::setCacheConfigValue('publinks_enabled', $pubLinksEnabled);
        \SP\Config\Config::setCacheConfigValue('publinks_image_enabled', $pubLinksImageEnabled);
        \SP\Config\Config::setCacheConfigValue('publinks_maxtime', $pubLinksMaxTime * 60);
        \SP\Config\Config::setCacheConfigValue('publinks_maxviews', $pubLinksMaxViews);

        // Proxy
        $proxyEnabled = \SP\Http\Request::analyze('proxy_enabled', false, false, true);
        $proxyServer = \SP\Http\Request::analyze('proxy_server');
        $proxyPort = \SP\Http\Request::analyze('proxy_port', 0);
        $proxyUser = \SP\Http\Request::analyze('proxy_user');
        $proxyPass = \SP\Http\Request::analyzeEncrypted('proxy_pass');


        // Valores para Proxy
        if ($proxyEnabled && (!$proxyServer || !$proxyPort)) {
            \SP\Http\Response::printJSON(_('Faltan parámetros de Proxy'));
        } elseif ($proxyEnabled) {
            \SP\Config\Config::setCacheConfigValue('proxy_enabled', true);
            \SP\Config\Config::setCacheConfigValue('proxy_server', $proxyServer);
            \SP\Config\Config::setCacheConfigValue('proxy_port', $proxyPort);
            \SP\Config\Config::setCacheConfigValue('proxy_user', $proxyUser);
            \SP\Config\Config::setCacheConfigValue('proxy_pass', $proxyPass);

            $Log->addDescription(_('Proxy habiltado'));
        } else {
            \SP\Config\Config::setCacheConfigValue('proxy_enabled', false);

            $Log->addDescription(_('Proxy deshabilitado'));
        }

        $Log->addDescription(sprintf('%s: %s', _('Sección'), _('General')));
    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_WIKI) {
        // Wiki
        $wikiEnabled = \SP\Http\Request::analyze('wiki_enabled', false, false, true);
        $wikiSearchUrl = \SP\Http\Request::analyze('wiki_searchurl');
        $wikiPageUrl = \SP\Http\Request::analyze('wiki_pageurl');
        $wikiFilter = \SP\Http\Request::analyze('wiki_filter');

        // Valores para la conexión a la Wiki
        if ($wikiEnabled && (!$wikiSearchUrl || !$wikiPageUrl || !$wikiFilter)) {
            \SP\Http\Response::printJSON(_('Faltan parámetros de Wiki'));
        } elseif ($wikiEnabled) {
            \SP\Config\Config::setCacheConfigValue('wiki_enabled', true);
            \SP\Config\Config::setCacheConfigValue('wiki_searchurl', $wikiSearchUrl);
            \SP\Config\Config::setCacheConfigValue('wiki_pageurl', $wikiPageUrl);
            \SP\Config\Config::setCacheConfigValue('wiki_filter', $wikiFilter);

            $Log->addDescription(_('Wiki habiltada'));
        } else {
            \SP\Config\Config::setCacheConfigValue('wiki_enabled', false);

            $Log->addDescription(_('Wiki deshabilitada'));
        }

        $Log->addDescription(sprintf('%s: %s', _('Sección'), _('Wiki')));
    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_LDAP) {
        // LDAP
        $ldapEnabled = \SP\Http\Request::analyze('ldap_enabled', false, false, true);
        $ldapADSEnabled = \SP\Http\Request::analyze('ldap_ads', false, false, true);
        $ldapServer = \SP\Http\Request::analyze('ldap_server');
        $ldapBase = \SP\Http\Request::analyze('ldap_base');
        $ldapGroup = \SP\Http\Request::analyze('ldap_group');
        $ldapDefaultGroup = \SP\Http\Request::analyze('ldap_defaultgroup', 0);
        $ldapDefaultProfile = \SP\Http\Request::analyze('ldap_defaultprofile', 0);
        $ldapBindUser = \SP\Http\Request::analyze('ldap_binduser');
        $ldapBindPass = \SP\Http\Request::analyzeEncrypted('ldap_bindpass');

        // Valores para la configuración de LDAP
        if ($ldapEnabled && (!$ldapServer || !$ldapBase || !$ldapBindUser)) {
            \SP\Http\Response::printJSON(_('Faltan parámetros de LDAP'));
        } elseif ($ldapEnabled) {
            \SP\Config\Config::setCacheConfigValue('ldap_enabled', true);
            \SP\Config\Config::setCacheConfigValue('ldap_ads', $ldapADSEnabled);
            \SP\Config\Config::setCacheConfigValue('ldap_server', $ldapServer);
            \SP\Config\Config::setCacheConfigValue('ldap_base', $ldapBase);
            \SP\Config\Config::setCacheConfigValue('ldap_group', $ldapGroup);
            \SP\Config\Config::setCacheConfigValue('ldap_defaultgroup', $ldapDefaultGroup);
            \SP\Config\Config::setCacheConfigValue('ldap_defaultprofile', $ldapDefaultProfile);
            \SP\Config\Config::setCacheConfigValue('ldap_binduser', $ldapBindUser);
            \SP\Config\Config::setCacheConfigValue('ldap_bindpass', $ldapBindPass);

            $Log->addDescription(_('LDAP habiltado'));
        } else {
            \SP\Config\Config::setCacheConfigValue('ldap_enabled', false);

            $Log->addDescription(_('LDAP deshabilitado'));
        }

        $Log->addDescription(sprintf('%s: %s', _('Sección'), _('LDAP')));
    } elseif ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_MAIL) {
        // Mail
        $mailEnabled = \SP\Http\Request::analyze('mail_enabled', false, false, true);
        $mailServer = \SP\Http\Request::analyze('mail_server');
        $mailPort = \SP\Http\Request::analyze('mail_port', 25);
        $mailUser = \SP\Http\Request::analyze('mail_user');
        $mailPass = \SP\Http\Request::analyzeEncrypted('mail_pass');
        $mailSecurity = \SP\Http\Request::analyze('mail_security');
        $mailFrom = \SP\Http\Request::analyze('mail_from');
        $mailRequests = \SP\Http\Request::analyze('mail_requestsenabled', false, false, true);
        $mailAuth = \SP\Http\Request::analyze('mail_authenabled', false, false, true);

        // Valores para la configuración del Correo
        if ($mailEnabled && (!$mailServer || !$mailFrom)) {
            \SP\Http\Response::printJSON(_('Faltan parámetros de Correo'));
        } elseif ($mailEnabled) {
            \SP\Config\Config::setCacheConfigValue('mail_enabled', true);
            \SP\Config\Config::setCacheConfigValue('mail_requestsenabled', $mailRequests);
            \SP\Config\Config::setCacheConfigValue('mail_server', $mailServer);
            \SP\Config\Config::setCacheConfigValue('mail_port', $mailPort);
            \SP\Config\Config::setCacheConfigValue('mail_security', $mailSecurity);
            \SP\Config\Config::setCacheConfigValue('mail_from', $mailFrom);

            if ($mailAuth) {
                \SP\Config\Config::setCacheConfigValue('mail_authenabled', $mailAuth);
                \SP\Config\Config::setCacheConfigValue('mail_user', $mailUser);
                \SP\Config\Config::setCacheConfigValue('mail_pass', $mailPass);
            }

            $Log->addDescription(_('Correo habiltado'));
        } else {
            \SP\Config\Config::setCacheConfigValue('mail_enabled', false);
            \SP\Config\Config::setCacheConfigValue('mail_requestsenabled', false);
            \SP\Config\Config::setCacheConfigValue('mail_authenabled', false);

            $Log->addDescription(_('Correo deshabilitado'));
        }

        $Log->addDescription(sprintf('%s: %s', _('Sección'), _('Correo')));
    }

    try {
        \SP\Config\Config::writeConfig();
    } catch (\SP\Core\SPException $e){
        $Log->addDescription($e->getMessage());
        $Log->addDescription($e->getHint());
        $Log->writeLog();

        \SP\Http\Response::printJSON($e->getMessage());
    }

    $Log->writeLog();

    \SP\Log\Email::sendEmail($Log);

    if ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_GENERAL) {
        // Recargar la aplicación completa para establecer nuevos valores
        \SP\Util\Util::reload();
    }

    \SP\Http\Response::printJSON(_('Configuración actualizada'), 0, $doActionOnClose);
} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_ENCRYPTION) {
    $currentMasterPass = \SP\Http\Request::analyzeEncrypted('curMasterPwd');
    $newMasterPass = \SP\Http\Request::analyzeEncrypted('newMasterPwd');
    $newMasterPassR = \SP\Http\Request::analyzeEncrypted('newMasterPwdR');
    $confirmPassChange = \SP\Http\Request::analyze('confirmPassChange', 0, false, 1);
    $noAccountPassChange = \SP\Http\Request::analyze('chkNoAccountChange', 0, false, 1);

    if (!UserPass::checkUserUpdateMPass()) {
        \SP\Http\Response::printJSON(_('Clave maestra actualizada') . ';;' . _('Reinicie la sesión para cambiarla'));
    } elseif ($newMasterPass == '' && $currentMasterPass == '') {
        \SP\Http\Response::printJSON(_('Clave maestra no indicada'));
    } elseif ($confirmPassChange == 0) {
        \SP\Http\Response::printJSON(_('Se ha de confirmar el cambio de clave'));
    }

    if ($newMasterPass == $currentMasterPass) {
        \SP\Http\Response::printJSON(_('Las claves son idénticas'));
    } elseif ($newMasterPass != $newMasterPassR) {
        \SP\Http\Response::printJSON(_('Las claves maestras no coinciden'));
    } elseif (!\SP\Core\Crypt::checkHashPass($currentMasterPass, ConfigDB::getValue('masterPwd'), true)) {
        \SP\Http\Response::printJSON(_('La clave maestra actual no coincide'));
    }

    $hashMPass = \SP\Core\Crypt::mkHashPassword($newMasterPass);

    if (!$noAccountPassChange) {
        $Account = new \SP\Account\Account();

        if (!$Account->updateAccountsMasterPass($currentMasterPass, $newMasterPass)) {
            \SP\Http\Response::printJSON(_('Errores al actualizar las claves de las cuentas'));
        }

        $AccountHistory = new \SP\Account\AccountHistory();

        if (!$AccountHistory->updateAccountsMasterPass($currentMasterPass, $newMasterPass, $hashMPass)) {
            \SP\Http\Response::printJSON(_('Errores al actualizar las claves de las cuentas del histórico'));
        }

        if (!\SP\Mgmt\CustomFields::updateCustomFieldsCrypt($currentMasterPass, $newMasterPass)) {
            \SP\Http\Response::printJSON(_('Errores al actualizar datos de campos personalizados'));
        }
    }

    if (Checks::demoIsEnabled()) {
        \SP\Http\Response::printJSON(_('Ey, esto es una DEMO!!'));
    }

//    ConfigDB::readConfig();
    ConfigDB::setCacheConfigValue('masterPwd', $hashMPass);
    ConfigDB::setCacheConfigValue('lastupdatempass', time());

    if (ConfigDB::writeConfig()) {
        \SP\Log\Log::writeNewLogAndEmail(_('Actualizar Clave Maestra'));

        \SP\Http\Response::printJSON(_('Clave maestra actualizada'), 0);
    } else {
        \SP\Http\Response::printJSON(_('Error al guardar el hash de la clave maestra'));
    }

} elseif ($actionId === \SP\Core\ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS) {
    $tempMasterMaxTime = \SP\Http\Request::analyze('tmpass_maxtime', 3600);
    $tempMasterPass = CryptMasterPass::setTempMasterPass($tempMasterMaxTime);

    if ($tempMasterPass !== false && !empty($tempMasterPass)) {
        \SP\Log\Email::sendEmail(new \SP\Log\Log(_('Generar Clave Temporal'), \SP\Html\Html::strongText(_('Clave') . ': ') . $tempMasterPass));

        \SP\Http\Response::printJSON(_('Clave Temporal Generada'), 0, $doActionOnClose);
    } else {
        \SP\Http\Response::printJSON(_('Error al generar clave temporal'));
    }
} else {
    \SP\Http\Response::printJSON(_('Acción Inválida'));
}