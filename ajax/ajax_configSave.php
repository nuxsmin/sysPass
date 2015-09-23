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

use SP\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

SP\Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$actionId = SP\Request::analyze('actionId', 0);
$activeTab = SP\Request::analyze('activeTab', 0);

$doActionOnClose = "sysPassUtil.Common.doAction($actionId,'',$activeTab);";

if ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_GENERAL
    || $actionId === SP\Controller\ActionsInterface::ACTION_CFG_WIKI
    || $actionId === SP\Controller\ActionsInterface::ACTION_CFG_LDAP
    || $actionId === SP\Controller\ActionsInterface::ACTION_CFG_MAIL
) {
    $log = SP\Log::newLog(_('Modificar Configuración'));

    if ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_GENERAL) {
        // General
        $siteLang = SP\Request::analyze('sitelang');
        $siteTheme = SP\Request::analyze('sitetheme');
        $sessionTimeout = SP\Request::analyze('session_timeout', 300);
        $logEnabled = SP\Request::analyze('log_enabled', false, false, true);
        $debugEnabled = SP\Request::analyze('debug', false, false, true);
        $maintenanceEnabled = SP\Request::analyze('maintenance', false, false, true);
        $checkUpdatesEnabled = SP\Request::analyze('updates', false, false, true);
        $checkNoticesEnabled = SP\Request::analyze('notices', false, false, true);

        SP\Config::setValue('sitelang', $siteLang);
        SP\Config::setValue('sitetheme', $siteTheme);
        SP\Config::setValue('session_timeout', $sessionTimeout);
        SP\Config::setValue('log_enabled', $logEnabled);
        SP\Config::setValue('debug', $debugEnabled);
        SP\Config::setValue('maintenance', $maintenanceEnabled);
        SP\Config::setValue('checkupdates', $checkUpdatesEnabled);
        SP\Config::setValue('checknotices', $checkNoticesEnabled);

        // Accounts
        $globalSearchEnabled = SP\Request::analyze('globalsearch', false, false, true);
        $accountPassToImageEnabled = SP\Request::analyze('account_passtoimage', false, false, true);
        $accountLinkEnabled = SP\Request::analyze('account_link', false, false, true);
        $accountCount = SP\Request::analyze('account_count', 10);
        $resultsAsCardsEnabled = SP\Request::analyze('resultsascards', false, false, true);

        SP\Config::setValue('globalsearch', $globalSearchEnabled);
        SP\Config::setValue('account_passtoimage', $accountPassToImageEnabled);
        SP\Config::setValue('account_link', $accountLinkEnabled);
        SP\Config::setValue('account_count', $accountCount);
        SP\Config::setValue('resultsascards', $resultsAsCardsEnabled);

        // Files
        $filesEnabled = SP\Request::analyze('files_enabled', false, false, true);
        $filesAllowedSize = SP\Request::analyze('files_allowed_size', 1024);
        $filesAllowedExts = SP\Request::analyze('files_allowed_exts');

        SP\Config::setValue('files_enabled', $filesEnabled);
        SP\Config::setValue('files_allowed_size', $filesAllowedSize);
        SP\Config::setValue('files_allowed_exts', $filesAllowedExts);

        if ($filesEnabled && $filesAllowedSize >= 16384) {
            SP\Common::printJSON(_('El tamaño máximo por archivo es de 16MB'));
        }

        // Proxy
        $proxyEnabled = SP\Request::analyze('proxy_enabled', false, false, true);
        $proxyServer = SP\Request::analyze('proxy_server');
        $proxyPort = SP\Request::analyze('proxy_port', 0);
        $proxyUser = SP\Request::analyze('proxy_user');
        $proxyPass = SP\Request::analyze('proxy_pass');


        // Valores para Proxy
        if ($proxyEnabled && (!$proxyServer || !$proxyPort)) {
            SP\Common::printJSON(_('Faltan parámetros de Proxy'));
        } elseif ($proxyEnabled) {
            SP\Config::setValue('proxy_enabled', true);
            SP\Config::setValue('proxy_server', $proxyServer);
            SP\Config::setValue('proxy_port', $proxyPort);
            SP\Config::setValue('proxy_user', $proxyUser);
            SP\Config::setValue('proxy_pass', $proxyPass);
        } else {
            SP\Config::setValue('proxy_enabled', false);
        }

        $log->addDescription(_('General'));
    } elseif ( $actionId === SP\Controller\ActionsInterface::ACTION_CFG_WIKI ) {
        // Wiki
        $wikiEnabled = SP\Request::analyze('wiki_enabled', false, false, true);
        $wikiSearchUrl = SP\Request::analyze('wiki_searchurl');
        $wikiPageUrl = SP\Request::analyze('wiki_pageurl');
        $wikiFilter = SP\Request::analyze('wiki_filter');

        // Valores para la conexión a la Wiki
        if ($wikiEnabled && (!$wikiSearchUrl || !$wikiPageUrl || !$wikiFilter)) {
            SP\Common::printJSON(_('Faltan parámetros de Wiki'));
        } elseif ($wikiEnabled) {
            SP\Config::setValue('wiki_enabled', true);
            SP\Config::setValue('wiki_searchurl', $wikiSearchUrl);
            SP\Config::setValue('wiki_pageurl', $wikiPageUrl);
            SP\Config::setValue('wiki_filter', $wikiFilter);
        } else {
            SP\Config::setValue('wiki_enabled', false);
        }

        $log->addDescription(_('Wiki'));
    } elseif ( $actionId === SP\Controller\ActionsInterface::ACTION_CFG_LDAP ) {
        // LDAP
        $ldapEnabled = SP\Request::analyze('ldap_enabled', false, false, true);
        $ldapADSEnabled = SP\Request::analyze('ldap_ads', false, false, true);
        $ldapServer = SP\Request::analyze('ldap_server');
        $ldapBase = SP\Request::analyze('ldap_base');
        $ldapGroup = SP\Request::analyze('ldap_group');
        $ldapDefaultGroup = SP\Request::analyze('ldap_defaultgroup', 0);
        $ldapDefaultProfile = SP\Request::analyze('ldap_defaultprofile', 0);
        $ldapBindUser = SP\Request::analyze('ldap_binduser');
        $ldapBindPass = SP\Request::analyze('ldap_bindpass', '', false, false, false);

        // Valores para la configuración de LDAP
        if ($ldapEnabled && (!$ldapServer || !$ldapBase || !$ldapBindUser)) {
            SP\Common::printJSON(_('Faltan parámetros de LDAP'));
        } elseif ($ldapEnabled) {
            SP\Config::setValue('ldap_enabled', true);
            SP\Config::setValue('ldap_ads', $ldapADSEnabled);
            SP\Config::setValue('ldap_server', $ldapServer);
            SP\Config::setValue('ldap_base', $ldapBase);
            SP\Config::setValue('ldap_group', $ldapGroup);
            SP\Config::setValue('ldap_defaultgroup', $ldapDefaultGroup);
            SP\Config::setValue('ldap_defaultprofile', $ldapDefaultProfile);
            SP\Config::setValue('ldap_binduser', $ldapBindUser);
            SP\Config::setValue('ldap_bindpass', $ldapBindPass);
        } else {
            SP\Config::setValue('ldap_enabled', false);
        }

        $log->addDescription(_('LDAP'));
    } elseif ( $actionId === SP\Controller\ActionsInterface::ACTION_CFG_MAIL ) {
        // Mail
        $mailEnabled = SP\Request::analyze('mail_enabled', false, false, true);
        $mailServer = SP\Request::analyze('mail_server');
        $mailPort = SP\Request::analyze('mail_port', 25);
        $mailUser = SP\Request::analyze('mail_user');
        $mailPass = SP\Request::analyze('mail_pass', '', false, false, false);
        $mailSecurity = SP\Request::analyze('mail_security');
        $mailFrom = SP\Request::analyze('mail_from');
        $mailRequests = SP\Request::analyze('mail_requestsenabled', false, false, true);
        $mailAuth = SP\Request::analyze('mail_authenabled', false, false, true);

        // Valores para la configuración del Correo
        if ($mailEnabled && (!$mailServer || !$mailFrom)) {
            SP\Common::printJSON(_('Faltan parámetros de Correo'));
        } elseif ($mailEnabled) {
            SP\Config::setValue('mail_enabled', true);
            SP\Config::setValue('mail_requestsenabled', $mailRequests);
            SP\Config::setValue('mail_server', $mailServer);
            SP\Config::setValue('mail_port', $mailPort);
            SP\Config::setValue('mail_security', $mailSecurity);
            SP\Config::setValue('mail_from', $mailFrom);

            if ($mailAuth) {
                SP\Config::setValue('mail_authenabled', $mailAuth);
                SP\Config::setValue('mail_user', $mailUser);
                SP\Config::setValue('mail_pass', $mailPass);
            }
        } else {
            SP\Config::setValue('mail_enabled', false);
            SP\Config::setValue('mail_requestsenabled', false);
            SP\Config::setValue('mail_authenabled', false);
        }

        $log->addDescription(_('Correo'));
    }

    $log->writeLog();

    SP\Email::sendEmail($log);

    if ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_GENERAL) {
        // Recargar la aplicación completa para establecer nuevos valores
        SP\Util::reload();
    }

    SP\Common::printJSON(_('Configuración actualizada'), 0, $doActionOnClose);
} elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION) {
    $currentMasterPass = SP\Request::analyze('curMasterPwd', '', false, false, false);
    $newMasterPass = SP\Request::analyze('newMasterPwd', '', false, false, false);
    $newMasterPassR = SP\Request::analyze('newMasterPwdR', '', false, false, false);
    $confirmPassChange = SP\Request::analyze('confirmPassChange', 0, false, 1);
    $noAccountPassChange = SP\Request::analyze('chkNoAccountChange', 0, false, 1);

    if (!UserUtil::checkUserUpdateMPass()) {
        SP\Common::printJSON(_('Clave maestra actualizada') . ';;' . _('Reinicie la sesión para cambiarla'));
    } elseif ($newMasterPass == '' && $currentMasterPass == '') {
        SP\Common::printJSON(_('Clave maestra no indicada'));
    } elseif ($confirmPassChange == 0) {
        SP\Common::printJSON(_('Se ha de confirmar el cambio de clave'));
    }

    try {
        // Desencriptar con la clave RSA
        $CryptPKI = new \SP\CryptPKI();
        $clearCurMasterPass = $CryptPKI->decryptRSA(base64_decode($currentMasterPass));
        $clearNewMasterPass = $CryptPKI->decryptRSA(base64_decode($newMasterPass));
        $clearNewMasterPassR = $CryptPKI->decryptRSA(base64_decode($newMasterPassR));
    } catch (Exception $e) {
        SP\Common::printJSON(_('Error en clave RSA'));
    }

    if ($clearNewMasterPass == $clearCurMasterPass) {
        SP\Common::printJSON(_('Las claves son idénticas'));
    } elseif ($clearNewMasterPass != $clearNewMasterPassR) {
        SP\Common::printJSON(_('Las claves maestras no coinciden'));
    } elseif (!SP\Crypt::checkHashPass($clearCurMasterPass, SP\Config::getConfigDbValue('masterPwd'))) {
        SP\Common::printJSON(_('La clave maestra actual no coincide'));
    }

    $hashMPass = SP\Crypt::mkHashPassword($clearNewMasterPass);

    if (!$noAccountPassChange) {
        $Account = new SP\Account();

        if (!$Account->updateAccountsMasterPass($clearCurMasterPass, $clearNewMasterPass)) {
            SP\Common::printJSON(_('Errores al actualizar las claves de las cuentas'));
        }

        $AccountHistory = new SP\AccountHistory();

        if (!$AccountHistory->updateAccountsMasterPass($clearCurMasterPass, $clearNewMasterPass, $hashMPass)) {
            SP\Common::printJSON(_('Errores al actualizar las claves de las cuentas del histórico'));
        }
    }

    if (SP\Util::demoIsEnabled()) {
        SP\Common::printJSON(_('Ey, esto es una DEMO!!'));
    }

    SP\Config::getConfigDb();
    SP\Config::setArrConfigValue('masterPwd', $hashMPass);
    SP\Config::setArrConfigValue('lastupdatempass', time());

    if (SP\Config::writeConfigDb()) {
        SP\Log::writeNewLogAndEmail(_('Actualizar Clave Maestra'));

        SP\Common::printJSON(_('Clave maestra actualizada'), 0);
    } else {
        SP\Common::printJSON(_('Error al guardar el hash de la clave maestra'));
    }

} elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS) {
    $tempMasterMaxTime = SP\Request::analyze('tmpass_maxtime', 3600);
    $tempMasterPass = SP\Config::setTempMasterPass($tempMasterMaxTime);

    if (!empty($tempMasterPass)) {
        SP\Email::sendEmail(new \SP\Log(_('Generar Clave Temporal'), SP\Html::strongText(_('Clave') . ': ') . $tempMasterPass));

        SP\Common::printJSON(_('Clave Temporal Generada'), 0, $doActionOnClose);
    }
} else {
    SP\Common::printJSON(_('Acción Inválida'));
}