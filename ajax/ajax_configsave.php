<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
include_once (APP_ROOT . "/inc/init.php");

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Common::printXML(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP_Common::parseParams('p', 'sk', FALSE);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printXML(_('CONSULTA INVÁLIDA'));
}

SP_Users::checkUserAccess("config") || SP_Html::showCommonError('nopermission');

$frmAction =  SP_Common::parseParams('p', 'action');

if ($frmAction == "config") {
    $frmSiteLang = SP_Common::parseParams('p', 'sitelang');
    $frmSessionTimeout = SP_Common::parseParams('p', 'session_timeout', 300);
    $frmLogEnabled = SP_Common::parseParams('p', 'logenabled', 0, FALSE, 1);
    $frmDebugEnabled = SP_Common::parseParams('p', 'debug', 0, FALSE, 1);
    $frmMaintenanceEnabled = SP_Common::parseParams('p', 'maintenance', 0, FALSE, 1);
    $frmCheckUpdatesEnabled = SP_Common::parseParams('p', 'updates', 0, FALSE, 1);
    $frmFilesEnabled = SP_Common::parseParams('p', 'filesenabled', 0, FALSE, 1);
    $frmAccountLink = SP_Common::parseParams('p', 'account_link', 0, FALSE, 1);
    $frmAccountCount = SP_Common::parseParams('p', 'account_count', 10);
    $frmAllowedSize = SP_Common::parseParams('p', 'allowed_size', 1024);
    $frmAllowedExts = SP_Common::parseParams('p', 'allowed_exts');

    $frmWikiEnabled = SP_Common::parseParams('p', 'wikienabled', 0, FALSE, 1);
    $frmWikiSearchUrl = SP_Common::parseParams('p', 'wikisearchurl');
    $frmWikiPageUrl = SP_Common::parseParams('p', 'wikipageurl');
    $frmWikiFilter = SP_Common::parseParams('p', 'wikifilter');

    $frmLdapEnabled = SP_Common::parseParams('p', 'ldapenabled', 0, FALSE, 1);
    $frmLdapServer = SP_Common::parseParams('p', 'ldapserver');
    $frmLdapBase = SP_Common::parseParams('p', 'ldapbase');
    $frmLdapGroup = SP_Common::parseParams('p', 'ldapgroup');
    $frmLdapBindUser = SP_Common::parseParams('p', 'ldapbinduser');
    $frmLdapBindPass = SP_Common::parseParams('p', 'ldapbindpass');

    $frmMailEnabled = SP_Common::parseParams('p', 'mailenabled', 0, FALSE, 1);
    $frmMailServer = SP_Common::parseParams('p', 'mailserver');
    $frmMailFrom = SP_Common::parseParams('p', 'mailfrom');

    if ($frmAccountCount == "all") {
        $intAccountCount = 99;
    } else {
        $intAccountCount = $frmAccountCount;
    }

    if ($frmWikiEnabled && (!$frmWikiSearchUrl || !$frmWikiPageUrl || !is_array($frmWikiFilter) )) {
        SP_Common::printXML(_('Faltan parámetros de Wiki'));
    } elseif ($frmWikiEnabled) {
        SP_Config::setValue("wikienabled", 1);
        SP_Config::setValue("wikisearchurl", $frmWikiSearchUrl);
        SP_Config::setValue("wikipageurl", $frmWikiPageUrl);
        SP_Config::setValue("wikifilter", implode("||", $frmWikiFilter));
    } else {
        SP_Config::setValue("wikienabled", 0);
    }

    if ($frmLdapEnabled && (!$frmLdapServer || !$frmLdapBase || !$frmLdapGroup || !$frmLdapBindUser )) {
        SP_Common::printXML(_('Faltan parámetros de LDAP'));
    } elseif ($frmLdapEnabled) {
        SP_Config::setValue("ldapenabled", 1);
        SP_Config::setValue("ldapserver", $frmLdapServer);
        SP_Config::setValue("ldapbase", $frmLdapBase);
        SP_Config::setValue("ldapgroup", $frmLdapGroup);
        SP_Config::setValue("ldapbinduser", $frmLdapBindUser);
        SP_Config::setValue("ldapbindpass", $frmLdapBindPass);
    } else {
        SP_Config::setValue("ldapenabled", 0);
    }

    if ($frmMailEnabled && (!$frmMailServer || !$frmMailFrom )) {
        SP_Common::printXML(_('Faltan parámetros de Correo'));
    } elseif ($frmMailEnabled) {
        SP_Config::setValue("mailenabled", 1);
        SP_Config::setValue("mailserver", $frmMailServer);
        SP_Config::setValue("mailfrom", $frmMailFrom);
    } else {
        SP_Config::setValue("mailenabled", 0);
    }

    if ($frmAllowedSize > 16384) {
        SP_Common::printXML(_('El tamaño máximo de archivo es de 16MB'));
    } 

    SP_Config::setValue("allowed_exts", ( is_array($frmAllowedExts) ) ? implode(",", $frmAllowedExts) : "");
    SP_Config::setValue("account_link", $frmAccountLink);
    SP_Config::setValue("account_count", $frmAccountCount);
    SP_Config::setValue("sitelang", $frmSiteLang);
    SP_Config::setValue("session_timeout", $frmSessionTimeout);
    SP_Config::setValue("logenabled", $frmLogEnabled);
    SP_Config::setValue("debug", $frmDebugEnabled);
    SP_Config::setValue("maintenance", $frmMaintenanceEnabled);
    SP_Config::setValue("checkupdates", $frmCheckUpdatesEnabled);
    SP_Config::setValue("filesenabled", $frmFilesEnabled);
    SP_Config::setValue("allowed_size", $frmAllowedSize);

    $message['action'] = _('Modificar Configuración');
    $message['text'][] = '';

    SP_Common::wrLogInfo($message);
    SP_Common::sendEmail($message);

    SP_Common::printXML(_('Configuración actualizada'), 0);
} elseif ($frmAction == "crypt") {
    $currentMasterPass = SP_Common::parseParams('p', 'curMasterPwd');
    $newMasterPass = SP_Common::parseParams('p', 'newMasterPwd');
    $newMasterPassR = SP_Common::parseParams('p', 'newMasterPwdR');
    $confirmPassChange = SP_Common::parseParams('p', 'confirmPassChange', 0, FALSE, 1);
    $noAccountPassChange = SP_Common::parseParams('p', 'chkNoAccountChange', 0, FALSE, 1);

    if (!SP_Users::checkUserUpdateMPass()) {
        SP_Common::printXML(_('Clave maestra actualizada') . '<br>' . _('Reinicie la sesión para cambiarla'));
    }

    if ($newMasterPass == "" && $currentMasterPass == "") {
        SP_Common::printXML(_('Clave maestra no indicada'));
    }

    if ($confirmPassChange == 0) {
        SP_Common::printXML(_('Se ha de confirmar el cambio de clave'));
    }

    if ($newMasterPass == $currentMasterPass) {
        SP_Common::printXML(_('Las claves son idénticas'));
    }

    if ($newMasterPass != $newMasterPassR) {
        SP_Common::printXML(_('Las claves maestras no coinciden'));
    }

    if (!SP_Crypt::checkHashPass($currentMasterPass, SP_Config::getConfigValue("masterPwd"))) {
        SP_Common::printXML(_('La clave maestra actual no coincide'));
    }

    $hashMPass = SP_Crypt::mkHashPassword($newMasterPass);
    
    if (!$noAccountPassChange) {
        $objAccount = new SP_Account;

        if (!$objAccount->updateAllAccountsMPass($currentMasterPass, $newMasterPass)) {
            SP_Common::printXML(_('Errores al actualizar las claves de las cuentas'));
        }
        
        $objAccount->updateAllAccountsHistoryMPass($currentMasterPass, $newMasterPass, $hashMPass);
    }

    if (SP_Config::getValue('demoenabled', 0)) {
        SP_Common::printXML(_('DEMO'));
    }

    SP_Config::$arrConfigValue["masterPwd"] = $hashMPass;
    SP_Config::$arrConfigValue["lastupdatempass"] = time();
    
    if (SP_Config::writeConfig()) {
        $message['action'] = _('Actualizar Clave Maestra');
        $message['text'] = '';

        SP_Common::sendEmail($message);
        SP_Common::printXML(_('Clave maestra cambiada'), 0);
    }
    
    SP_Common::printXML(_('Error al guardar el hash de la clave maestra'));
} else {
    SP_Common::printXML(_('No es una acción válida'));
}