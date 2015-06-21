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

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

SP\Util::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP\Common::parseParams('p', 'sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$actionId = SP\Common::parseParams('p', 'actionId', 0);
$onCloseAction = SP\Common::parseParams('p', 'onCloseAction');
$activeTab = SP\Common::parseParams('p', 'activeTab', 0);

$doActionOnClose = "doAction($actionId,'',$activeTab);";

if ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_GENERAL) {
    $siteLang = SP\Common::parseParams('p', 'sitelang');
    $sessionTimeout = SP\Common::parseParams('p', 'session_timeout', 300);
    $logEnabled = SP\Common::parseParams('p', 'log_enabled', false, false, true);
    $debugEnabled = SP\Common::parseParams('p', 'debug', false, false, true);
    $maintenanceEnabled = SP\Common::parseParams('p', 'maintenance', false, false, true);
    $checkUpdatesEnabled = SP\Common::parseParams('p', 'updates', false, false, true);
    $filesEnabled = SP\Common::parseParams('p', 'files_enabled', false, false, true);
    $globalSearchEnabled = SP\Common::parseParams('p', 'globalsearch', false, false, true);
    $accountLinkEnabled = SP\Common::parseParams('p', 'account_link', false, false, true);
    $accountCount = SP\Common::parseParams('p', 'account_count', 10);
    $filesAllowedSize = SP\Common::parseParams('p', 'files_allowed_size', 1024);
    $filesAllowedExts = SP\Common::parseParams('p', 'files_allowed_exts');
    $resultsAsCardsEnabled = SP\Common::parseParams('p', 'resultsascards', false, false, true);

    $wikiEnabled = SP\Common::parseParams('p', 'wiki_enabled', false, false, true);
    $wikiSearchUrl = SP\Common::parseParams('p', 'wiki_searchurl');
    $wikiPageUrl = SP\Common::parseParams('p', 'wiki_pageurl');
    $wikiFilter = SP\Common::parseParams('p', 'wiki_filter');

    $ldapEnabled = SP\Common::parseParams('p', 'ldap_enabled', false, false, true);
    $ldapADSEnabled = SP\Common::parseParams('p', 'ldap_ads', false, false, true);
    $ldapServer = SP\Common::parseParams('p', 'ldap_server');
    $ldapBase = SP\Common::parseParams('p', 'ldap_base');
    $ldapGroup = SP\Common::parseParams('p', 'ldap_group');
    $ldapDefaultGroup = SP\Common::parseParams('p', 'ldap_defaultgroup', 0);
    $ldapDefaultProfile = SP\Common::parseParams('p', 'ldap_defaultprofile', 0);
    $ldapBindUser = SP\Common::parseParams('p', 'ldap_binduser');
    $ldapBindPass = SP\Common::parseParams('p', 'ldap_bindpass', '', false, false, false);

    $mailEnabled = SP\Common::parseParams('p', 'mail_enabled', false, false, true);
    $mailServer = SP\Common::parseParams('p', 'mail_server');
    $mailPort = SP\Common::parseParams('p', 'mail_port', 25);
    $mailUser = SP\Common::parseParams('p', 'mail_user');
    $mailPass = SP\Common::parseParams('p', 'mail_pass', '', false, false, false);
    $mailSecurity = SP\Common::parseParams('p', 'mail_security');
    $mailFrom = SP\Common::parseParams('p', 'mail_from');
    $mailRequests = SP\Common::parseParams('p', 'mail_requestsenabled', false, false, true);
    $mailAuth = SP\Common::parseParams('p', 'mail_authenabled', false, false, true);

    if ($accountCount == 'all') {
        $accountCount = 99;
    }

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

    if ($filesEnabled && $filesAllowedSize > 16384) {
        SP\Common::printJSON(_('El tamaño máximo de archivo es de 16MB'));
    }

    SP\Config::setValue('account_link', $accountLinkEnabled);
    SP\Config::setValue('account_count', $accountCount);
    SP\Config::setValue('sitelang', $siteLang);
    SP\Config::setValue('session_timeout', $sessionTimeout);
    SP\Config::setValue('log_enabled', $logEnabled);
    SP\Config::setValue('debug', $debugEnabled);
    SP\Config::setValue('maintenance', $maintenanceEnabled);
    SP\Config::setValue('checkupdates', $checkUpdatesEnabled);
    SP\Config::setValue('files_enabled', $filesEnabled);
    SP\Config::setValue('files_allowed_exts', $filesAllowedExts);
    SP\Config::setValue('files_allowed_size', $filesAllowedSize);
    SP\Config::setValue('resultsascards', $resultsAsCardsEnabled);
    SP\Config::setValue('globalsearch', $globalSearchEnabled);

    $message['action'] = _('Modificar Configuración');

    SP\Log::wrLogInfo($message);
    SP\Common::sendEmail($message);

    // Recargar la aplicación completa para establecer nuevos valores
    SP\Util::reload();

    SP\Common::printJSON(_('Configuración actualizada'), 0, $doActionOnClose);
} elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION) {
    $currentMasterPass = SP\Common::parseParams('p', 'curMasterPwd', '', false, false, false);
    $newMasterPass = SP\Common::parseParams('p', 'newMasterPwd', '', false, false, false);
    $newMasterPassR = SP\Common::parseParams('p', 'newMasterPwdR', '', false, false, false);
    $confirmPassChange = SP\Common::parseParams('p', 'confirmPassChange', 0, false, 1);
    $noAccountPassChange = SP\Common::parseParams('p', 'chkNoAccountChange', 0, false, 1);

    if (!SP\Users::checkUserUpdateMPass()) {
        SP\Common::printJSON(_('Clave maestra actualizada') . ';;' . _('Reinicie la sesión para cambiarla'));
    } elseif ($newMasterPass == '' && $currentMasterPass == '') {
        SP\Common::printJSON(_('Clave maestra no indicada'));
    } elseif ($confirmPassChange == 0) {
        SP\Common::printJSON(_('Se ha de confirmar el cambio de clave'));
    } elseif ($newMasterPass == $currentMasterPass) {
        SP\Common::printJSON(_('Las claves son idénticas'));
    } elseif ($newMasterPass != $newMasterPassR) {
        SP\Common::printJSON(_('Las claves maestras no coinciden'));
    } elseif (!SP\Crypt::checkHashPass($currentMasterPass, SP\Config::getConfigDbValue('masterPwd'))) {
        SP\Common::printJSON(_('La clave maestra actual no coincide'));
    }

    $hashMPass = SP\Crypt::mkHashPassword($newMasterPass);

    if (!$noAccountPassChange) {
        $account = new SP\Account();

        if (!$account->updateAccountsMasterPass($currentMasterPass, $newMasterPass)) {
            SP\Common::printJSON(_('Errores al actualizar las claves de las cuentas'));
        }

        $accountHistory = new SP\AccountHistory();

        if (!$accountHistory->updateAccountsMasterPass($currentMasterPass, $newMasterPass, $hashMPass)) {
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
        $message['action'] = _('Actualizar Clave Maestra');

        SP\Common::sendEmail($message);
        SP\Common::printJSON(_('Clave maestra actualizada'), 0);
    } else {
        SP\Common::printJSON(_('Error al guardar el hash de la clave maestra'));
    }

} elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS) {
    $tempMasterMaxTime = SP\Common::parseParams('p', 'tmpass_maxtime', 3600);
    $tempMasterPass = SP\Config::setTempMasterPass($tempMasterMaxTime);

    if (!empty($tempMasterPass)) {
        $message['action'] = _('Generar Clave Temporal');
        $message['text'][] = SP\Html::strongText(_('Clave') . ': ') . $tempMasterPass;

        SP\Common::sendEmail($message);
        SP\Common::printJSON(_('Clave Temporal Generada'), 0, $doActionOnClose);
    }
} else {
    SP\Common::printJSON(_('Acción Inválida'));
}