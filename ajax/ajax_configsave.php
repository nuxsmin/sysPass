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

if (!isset($_POST["sk"]) || !SP_Common::checkSessionKey($_POST["sk"])) {
    SP_Common::printXML(_('CONSULTA INVÁLIDA'));
}

SP_Users::checkUserAccess("config") || die('<DIV CLASS="error">' . _('No tiene permisos para acceder a esta página') . '</DIV');

$frmAction = ( isset($_POST["action"]) ) ? $_POST["action"] : "";

if ($frmAction == "config") {
    $frmSiteLang = ( isset($_POST["sitelang"]) ) ? SP_Html::sanitize($_POST["sitelang"]) : "";
    $frmSessionTimeout = ( isset($_POST["session_timeout"]) ) ? (int) $_POST["session_timeout"] : 300;
    $frmLogEnabled = ( isset($_POST["logenabled"]) ) ? 1 : 0;
    $frmDebugEnabled = ( isset($_POST["debug"]) ) ? 1 : 0;
    $frmMaintenanceEnabled = ( isset($_POST["maintenance"]) ) ? 1 : 0;
    $frmCheckUpdatesEnabled = ( isset($_POST["updates"]) ) ? 1 : 0;
    $frmFilesEnabled = ( isset($_POST["filesenabled"]) ) ? 1 : 0;
    $frmAccountLink = ( isset($_POST["account_link"]) ) ? 1 : 0;
    $frmAccountCount = ( isset($_POST["account_count"]) ) ? (int) $_POST["account_count"] : 10;
    $frmAllowedSize = ( isset($_POST["allowed_size"]) ) ? (int) $_POST["allowed_size"] : 1024;
    $frmAllowedExts = ( isset($_POST["allowed_exts"]) ) ? SP_Html::sanitize($_POST["allowed_exts"]) : "";

    $frmWikiEnabled = ( isset($_POST["wikienabled"]) ) ? 1 : 0;
    $frmWikiSearchUrl = ( isset($_POST["wikisearchurl"]) ) ? SP_Html::sanitize($_POST["wikisearchurl"]) : "";
    $frmWikiPageUrl = ( isset($_POST["wikipageurl"]) ) ? SP_Html::sanitize($_POST["wikipageurl"]) : "";
    $frmWikiFilter = ( isset($_POST["wikifilter"]) ) ? SP_Html::sanitize($_POST["wikifilter"]) : "";

    $frmLdapEnabled = ( isset($_POST["ldapenabled"]) ) ? 1 : 0;
    $frmLdapServer = ( isset($_POST["ldapserver"]) ) ? SP_Html::sanitize($_POST["ldapserver"]) : "";
    $frmLdapBase = ( isset($_POST["ldapbase"]) ) ? SP_Html::sanitize($_POST["ldapbase"]) : "";
    $frmLdapGroup = ( isset($_POST["ldapgroup"]) ) ? SP_Html::sanitize($_POST["ldapgroup"]) : "";
    $frmLdapBindUser = ( isset($_POST["ldapbinduser"]) ) ? SP_Html::sanitize($_POST["ldapbinduser"]) : "";
    $frmLdapBindPass = ( isset($_POST["ldapbindpass"]) ) ? $_POST["ldapbindpass"] : "";

    $frmMailEnabled = ( isset($_POST["mailenabled"]) ) ? 1 : 0;
    $frmMailServer = ( isset($_POST["mailserver"]) ) ? SP_Html::sanitize($_POST["mailserver"]) : "";
    $frmMailFrom = ( isset($_POST["mailfrom"]) ) ? SP_Html::sanitize($_POST["mailfrom"]) : "";

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
    } else {
        SP_Config::setValue("allowed_size", $frmAllowedSize);
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

    $message['action'] = _('Modificar Configuración');
    $message['text'][] = '';

    SP_Common::wrLogInfo($message);
    SP_Common::sendEmail($message);

    SP_Common::printXML(_('Configuración actualizada'), 0);
} elseif ($frmAction == "crypt") {
    $currentMasterPass = ( isset($_POST["curMasterPwd"]) ) ? $_POST["curMasterPwd"] : "";
    $newMasterPass = ( isset($_POST["newMasterPwd"]) ) ? $_POST["newMasterPwd"] : "";
    $newMasterPassR = ( isset($_POST["newMasterPwdR"]) ) ? $_POST["newMasterPwdR"] : "";
    $confirmPassChange = ( isset($_POST["confirmPassChange"]) ) ? $_POST["confirmPassChange"] : 0;
    $noAccountPassChange = ( isset($_POST["chkNoAccountChange"]) ) ? $_POST["chkNoAccountChange"] : 0;

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
    } else {
        SP_Common::printXML(_('Error al guardar el hash de la clave maestra'));
    }
} else {
    SP_Common::printXML(_('No es una acción válida'));
}