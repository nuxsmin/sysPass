<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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
require_once APP_ROOT.DIRECTORY_SEPARATOR.'inc'.DIRECTORY_SEPARATOR.'init.php';

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Common::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP_Common::parseParams('p', 'sk', false);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printJSON(_('CONSULTA INVÁLIDA'));
}

// Variables POST del formulario
$frmAction =  SP_Common::parseParams('p', 'action');
$frmOnCloseAction = SP_Common::parseParams('p', 'onCloseAction');
$frmActiveTab = SP_Common::parseParams('p', 'activeTab', 0);

$doActionOnClose = "doAction('$frmOnCloseAction','',$frmActiveTab);";

if ($frmAction == "config") {
    $frmSiteLang = SP_Common::parseParams('p', 'sitelang');
    $frmSessionTimeout = SP_Common::parseParams('p', 'session_timeout', 300);
    $frmLog = SP_Common::parseParams('p', 'log_enabled', false, false, true);
    $frmDebug = SP_Common::parseParams('p', 'debug', false, false, true);
    $frmMaintenance = SP_Common::parseParams('p', 'maintenance', false, false, true);
    $frmCheckUpdates = SP_Common::parseParams('p', 'updates', false, false, true);
    $frmFiles = SP_Common::parseParams('p', 'files_enabled', false, false, true);
    $frmGlobalSearch = SP_Common::parseParams('p', 'globalsearch', false, false, true);
    $frmAccountLink = SP_Common::parseParams('p', 'account_link', false, false, true);
    $frmAccountCount = SP_Common::parseParams('p', 'account_count', 10);
    $frmAllowedSize = SP_Common::parseParams('p', 'files_allowed_size', 1024);
    $frmAllowedExts = SP_Common::parseParams('p', 'files_allowed_exts');

    $frmWiki = SP_Common::parseParams('p', 'wiki_enabled', false, false, true);
    $frmWikiSearchUrl = SP_Common::parseParams('p', 'wiki_searchurl');
    $frmWikiPageUrl = SP_Common::parseParams('p', 'wiki_pageurl');
    $frmWikiFilter = SP_Common::parseParams('p', 'wiki_filter');

    $frmLdap = SP_Common::parseParams('p', 'ldap_enabled', false, false, true);
    $frmLdapServer = SP_Common::parseParams('p', 'ldap_server');
    $frmLdapBase = SP_Common::parseParams('p', 'ldap_base');
    $frmLdapGroup = SP_Common::parseParams('p', 'ldap_group');
    $frmLdapBindUser = SP_Common::parseParams('p', 'ldap_binduser');
    $frmLdapBindPass = SP_Common::parseParams('p', 'ldap_bindpass', '', false, false, false);

    $frmMail = SP_Common::parseParams('p', 'mail_enabled', false, false, true);
    $frmMailServer = SP_Common::parseParams('p', 'mail_server');
    $frmMailPort = SP_Common::parseParams('p', 'mail_port',25);
    $frmMailUser = SP_Common::parseParams('p', 'mail_user');
    $frmMailPass = SP_Common::parseParams('p', 'mail_pass', '', false, false, false);
    $frmMailSecurity = SP_Common::parseParams('p', 'mail_security');
    $frmMailFrom = SP_Common::parseParams('p', 'mail_from');
    $frmMailRequests = SP_Common::parseParams('p', 'mail_requestsenabled', false, false, true);
    $frmMailAuth = SP_Common::parseParams('p', 'mail_authenabled', false, false, true);

    if ($frmAccountCount == "all") {
        $intAccountCount = 99;
    } else {
        $intAccountCount = $frmAccountCount;
    }

    if ($frmWiki && (!$frmWikiSearchUrl || !$frmWikiPageUrl || !$frmWikiFilter )) {
        SP_Common::printJSON(_('Faltan parámetros de Wiki'));
    } elseif ($frmWiki) {
        SP_Config::setValue("wiki_enabled", true);
        SP_Config::setValue("wiki_searchurl", $frmWikiSearchUrl);
        SP_Config::setValue("wiki_pageurl", $frmWikiPageUrl);
        SP_Config::setValue("wiki_filter", $frmWikiFilter);
    } else {
        SP_Config::setValue("wiki_enabled", false);
    }

    if ($frmLdap && (!$frmLdapServer || !$frmLdapBase || !$frmLdapGroup || !$frmLdapBindUser )) {
        SP_Common::printJSON(_('Faltan parámetros de LDAP'));
    } elseif ($frmLdap) {
        SP_Config::setValue("ldap_enabled", true);
        SP_Config::setValue("ldap_server", $frmLdapServer);
        SP_Config::setValue("ldap_base", $frmLdapBase);
        SP_Config::setValue("ldap_group", $frmLdapGroup);
        SP_Config::setValue("ldap_binduser", $frmLdapBindUser);
        SP_Config::setValue("ldap_bindpass", $frmLdapBindPass);
    } else {
        SP_Config::setValue("ldap_enabled", false);
    }

    if ($frmMail && (!$frmMailServer || !$frmMailFrom )) {
        SP_Common::printJSON(_('Faltan parámetros de Correo'));
    } elseif ($frmMail) {
        SP_Config::setValue("mail_enabled", true);
        SP_Config::setValue("mail_requestsenabled", $frmMailRequests);
        SP_Config::setValue("mail_server", $frmMailServer);
        SP_Config::setValue("mail_port", $frmMailPort);
        SP_Config::setValue("mail_security", $frmMailSecurity);
        SP_Config::setValue("mail_from", $frmMailFrom);

        if ( $frmMailAuth ){
            SP_Config::setValue("mail_authenabled", $frmMailAuth);
            SP_Config::setValue("mail_user", $frmMailUser);
            SP_Config::setValue("mail_pass", $frmMailPass);
        }
    } else {
        SP_Config::setValue("mail_enabled", false);
        SP_Config::setValue("mail_requestsenabled", false);
        SP_Config::setValue("mail_authenabled", false);
    }

    if ($frmAllowedSize > 16384) {
        SP_Common::printJSON(_('El tamaño máximo de archivo es de 16MB'));
    } 

    SP_Config::setValue("allowed_exts", $frmAllowedExts);
    SP_Config::setValue("account_link", $frmAccountLink);
    SP_Config::setValue("account_count", $frmAccountCount);
    SP_Config::setValue("sitelang", $frmSiteLang);
    SP_Config::setValue("session_timeout", $frmSessionTimeout);
    SP_Config::setValue("log_enabled", $frmLog);
    SP_Config::setValue("debug", $frmDebug);
    SP_Config::setValue("maintenance", $frmMaintenance);
    SP_Config::setValue("checkupdates", $frmCheckUpdates);
    SP_Config::setValue("files_enabled", $frmFiles);
    SP_Config::setValue("globalsearch", $frmGlobalSearch);
    SP_Config::setValue("files_allowed_size", $frmAllowedSize);

    $message['action'] = _('Modificar Configuración');

    SP_Log::wrLogInfo($message);
    SP_Common::sendEmail($message);

    SP_Common::printJSON(_('Configuración actualizada'), 0, $doActionOnClose);
} elseif ($frmAction == "crypt") {
    $currentMasterPass = SP_Common::parseParams('p', 'curMasterPwd', '', false, false, false);
    $newMasterPass = SP_Common::parseParams('p', 'newMasterPwd', '', false, false, false);
    $newMasterPassR = SP_Common::parseParams('p', 'newMasterPwdR', '', false, false, false);
    $confirmPassChange = SP_Common::parseParams('p', 'confirmPassChange', 0, false, 1);
    $noAccountPassChange = SP_Common::parseParams('p', 'chkNoAccountChange', 0, false, 1);

    if (!SP_Users::checkUserUpdateMPass()) {
        SP_Common::printJSON(_('Clave maestra actualizada') . ';;' . _('Reinicie la sesión para cambiarla'));
    }

    if ($newMasterPass == "" && $currentMasterPass == "") {
        SP_Common::printJSON(_('Clave maestra no indicada'));
    }

    if ($confirmPassChange == 0) {
        SP_Common::printJSON(_('Se ha de confirmar el cambio de clave'));
    }

    if ($newMasterPass == $currentMasterPass) {
        SP_Common::printJSON(_('Las claves son idénticas'));
    }

    if ($newMasterPass != $newMasterPassR) {
        SP_Common::printJSON(_('Las claves maestras no coinciden'));
    }

    if (!SP_Crypt::checkHashPass($currentMasterPass, SP_Config::getConfigValue("masterPwd"))) {
        SP_Common::printJSON(_('La clave maestra actual no coincide'));
    }

    $hashMPass = SP_Crypt::mkHashPassword($newMasterPass);
    
    if (!$noAccountPassChange) {
        $objAccount = new SP_Account;

        if (!$objAccount->updateAllAccountsMPass($currentMasterPass, $newMasterPass)) {
            SP_Common::printJSON(_('Errores al actualizar las claves de las cuentas'));
        }
        
        $objAccount->updateAllAccountsHistoryMPass($currentMasterPass, $newMasterPass, $hashMPass);
    }

    if (SP_Util::demoIsEnabled()) {
        SP_Common::printJSON(_('Ey, esto es una DEMO!!'));
    }

    SP_Config::$arrConfigValue["masterPwd"] = $hashMPass;
    SP_Config::$arrConfigValue["lastupdatempass"] = time();
    
    if (SP_Config::writeConfig()) {
        $message['action'] = _('Actualizar Clave Maestra');

        SP_Common::sendEmail($message);
        SP_Common::printJSON(_('Clave maestra actualizada'), 0);
    }
    
    SP_Common::printJSON(_('Error al guardar el hash de la clave maestra'));
} else {
    SP_Common::printJSON(_('Acción Inválida'));
}