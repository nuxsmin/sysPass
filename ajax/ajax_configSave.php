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

use SP\ConfigDB;
use SP\CryptMasterPass;
use SP\SessionUtil;
use SP\UserPass;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

SP\Request::checkReferer('POST');

if (!SP\Init::isLoggedIn()) {
    SP\Response::printJSON(_('La sesión no se ha iniciado o ha caducado'), 10);
}

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SessionUtil::checkSessionKey($sk)) {
    SP\Response::printJSON(_('CONSULTA INVÁLIDA'));
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
    $Log = SP\Log::newLog(_('Modificar Configuración'));

    if ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_GENERAL) {
        // General
        $siteLang = SP\Request::analyze('sitelang');
        $siteTheme = SP\Request::analyze('sitetheme', 'material-blue');
        $sessionTimeout = SP\Request::analyze('session_timeout', 300);
        $httpsEnabled = SP\Request::analyze('https_enabled', false, false, true);
        $logEnabled = SP\Request::analyze('log_enabled', false, false, true);
        $debugEnabled = SP\Request::analyze('debug', false, false, true);
        $maintenanceEnabled = SP\Request::analyze('maintenance', false, false, true);
        $checkUpdatesEnabled = SP\Request::analyze('updates', false, false, true);
        $checkNoticesEnabled = SP\Request::analyze('notices', false, false, true);

        if (!array_key_exists($siteTheme , \SP\Themes::getThemesAvailable())
            || (\SP\Util::demoIsEnabled() && $siteTheme !== 'material-blue')
        ) {
            SP\Response::printJSON(_('CONSULTA INVÁLIDA'));
        }

        SP\Config::setCacheConfigValue('sitelang', $siteLang);
        SP\Config::setCacheConfigValue('sitetheme', $siteTheme);
        SP\Config::setCacheConfigValue('session_timeout', $sessionTimeout);
        SP\Config::setCacheConfigValue('https_enabled', $httpsEnabled);
        SP\Config::setCacheConfigValue('log_enabled', $logEnabled);
        SP\Config::setCacheConfigValue('debug', $debugEnabled);
        SP\Config::setCacheConfigValue('maintenance', $maintenanceEnabled);
        SP\Config::setCacheConfigValue('checkupdates', $checkUpdatesEnabled);
        SP\Config::setCacheConfigValue('checknotices', $checkNoticesEnabled);

        // Accounts
        $globalSearchEnabled = SP\Request::analyze('globalsearch', false, false, true);
        $accountPassToImageEnabled = SP\Request::analyze('account_passtoimage', false, false, true);
        $accountLinkEnabled = SP\Request::analyze('account_link', false, false, true);
        $accountCount = SP\Request::analyze('account_count', 10);
        $resultsAsCardsEnabled = SP\Request::analyze('resultsascards', false, false, true);

        SP\Config::setCacheConfigValue('globalsearch', $globalSearchEnabled);
        SP\Config::setCacheConfigValue('account_passtoimage', $accountPassToImageEnabled);
        SP\Config::setCacheConfigValue('account_link', $accountLinkEnabled);
        SP\Config::setCacheConfigValue('account_count', $accountCount);
        SP\Config::setCacheConfigValue('resultsascards', $resultsAsCardsEnabled);

        // Files
        $filesEnabled = SP\Request::analyze('files_enabled', false, false, true);
        $filesAllowedSize = SP\Request::analyze('files_allowed_size', 1024);
        $filesAllowedExts = SP\Request::analyze('files_allowed_exts');

        if ($filesEnabled && $filesAllowedSize >= 16384) {
            SP\Response::printJSON(_('El tamaño máximo por archivo es de 16MB'));
        }

        if (!empty($filesAllowedExts)){
            $exts = explode(',', $filesAllowedExts);

            array_walk($exts, function(&$value) {
                if (preg_match('/[^a-z0-9_-]+/i', $value)) {
                    SP\Response::printJSON(_('Extensión no permitida'));
                }
            });
        }

        SP\Config::setCacheConfigValue('files_enabled', $filesEnabled);
        SP\Config::setCacheConfigValue('files_allowed_size', $filesAllowedSize);
        SP\Config::setCacheConfigValue('files_allowed_exts', $filesAllowedExts);

        // Proxy
        $proxyEnabled = SP\Request::analyze('proxy_enabled', false, false, true);
        $proxyServer = SP\Request::analyze('proxy_server');
        $proxyPort = SP\Request::analyze('proxy_port', 0);
        $proxyUser = SP\Request::analyze('proxy_user');
        $proxyPass = SP\Request::analyzeEncrypted('proxy_pass');


        // Valores para Proxy
        if ($proxyEnabled && (!$proxyServer || !$proxyPort)) {
            SP\Response::printJSON(_('Faltan parámetros de Proxy'));
        } elseif ($proxyEnabled) {
            SP\Config::setCacheConfigValue('proxy_enabled', true);
            SP\Config::setCacheConfigValue('proxy_server', $proxyServer);
            SP\Config::setCacheConfigValue('proxy_port', $proxyPort);
            SP\Config::setCacheConfigValue('proxy_user', $proxyUser);
            SP\Config::setCacheConfigValue('proxy_pass', $proxyPass);

            $Log->addDescription(_('Proxy habiltado'));
        } else {
            SP\Config::setCacheConfigValue('proxy_enabled', false);

            $Log->addDescription(_('Proxy deshabilitado'));
        }

        $Log->addDescription(sprintf('%s: %s', _('Sección'), _('General')));
    } elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_WIKI) {
        // Wiki
        $wikiEnabled = SP\Request::analyze('wiki_enabled', false, false, true);
        $wikiSearchUrl = SP\Request::analyze('wiki_searchurl');
        $wikiPageUrl = SP\Request::analyze('wiki_pageurl');
        $wikiFilter = SP\Request::analyze('wiki_filter');

        // Valores para la conexión a la Wiki
        if ($wikiEnabled && (!$wikiSearchUrl || !$wikiPageUrl || !$wikiFilter)) {
            SP\Response::printJSON(_('Faltan parámetros de Wiki'));
        } elseif ($wikiEnabled) {
            SP\Config::setCacheConfigValue('wiki_enabled', true);
            SP\Config::setCacheConfigValue('wiki_searchurl', $wikiSearchUrl);
            SP\Config::setCacheConfigValue('wiki_pageurl', $wikiPageUrl);
            SP\Config::setCacheConfigValue('wiki_filter', $wikiFilter);

            $Log->addDescription(_('Wiki habiltada'));
        } else {
            SP\Config::setCacheConfigValue('wiki_enabled', false);

            $Log->addDescription(_('Wiki deshabilitada'));
        }

        $Log->addDescription(sprintf('%s: %s', _('Sección'), _('Wiki')));
    } elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_LDAP) {
        // LDAP
        $ldapEnabled = SP\Request::analyze('ldap_enabled', false, false, true);
        $ldapADSEnabled = SP\Request::analyze('ldap_ads', false, false, true);
        $ldapServer = SP\Request::analyze('ldap_server');
        $ldapBase = SP\Request::analyze('ldap_base');
        $ldapGroup = SP\Request::analyze('ldap_group');
        $ldapDefaultGroup = SP\Request::analyze('ldap_defaultgroup', 0);
        $ldapDefaultProfile = SP\Request::analyze('ldap_defaultprofile', 0);
        $ldapBindUser = SP\Request::analyze('ldap_binduser');
        $ldapBindPass = SP\Request::analyzeEncrypted('ldap_bindpass');

        // Valores para la configuración de LDAP
        if ($ldapEnabled && (!$ldapServer || !$ldapBase || !$ldapBindUser)) {
            SP\Response::printJSON(_('Faltan parámetros de LDAP'));
        } elseif ($ldapEnabled) {
            SP\Config::setCacheConfigValue('ldap_enabled', true);
            SP\Config::setCacheConfigValue('ldap_ads', $ldapADSEnabled);
            SP\Config::setCacheConfigValue('ldap_server', $ldapServer);
            SP\Config::setCacheConfigValue('ldap_base', $ldapBase);
            SP\Config::setCacheConfigValue('ldap_group', $ldapGroup);
            SP\Config::setCacheConfigValue('ldap_defaultgroup', $ldapDefaultGroup);
            SP\Config::setCacheConfigValue('ldap_defaultprofile', $ldapDefaultProfile);
            SP\Config::setCacheConfigValue('ldap_binduser', $ldapBindUser);
            SP\Config::setCacheConfigValue('ldap_bindpass', $ldapBindPass);

            $Log->addDescription(_('LDAP habiltado'));
        } else {
            SP\Config::setCacheConfigValue('ldap_enabled', false);

            $Log->addDescription(_('LDAP deshabilitado'));
        }

        $Log->addDescription(sprintf('%s: %s', _('Sección'), _('LDAP')));
    } elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_MAIL) {
        // Mail
        $mailEnabled = SP\Request::analyze('mail_enabled', false, false, true);
        $mailServer = SP\Request::analyze('mail_server');
        $mailPort = SP\Request::analyze('mail_port', 25);
        $mailUser = SP\Request::analyze('mail_user');
        $mailPass = SP\Request::analyzeEncrypted('mail_pass');
        $mailSecurity = SP\Request::analyze('mail_security');
        $mailFrom = SP\Request::analyze('mail_from');
        $mailRequests = SP\Request::analyze('mail_requestsenabled', false, false, true);
        $mailAuth = SP\Request::analyze('mail_authenabled', false, false, true);

        // Valores para la configuración del Correo
        if ($mailEnabled && (!$mailServer || !$mailFrom)) {
            SP\Response::printJSON(_('Faltan parámetros de Correo'));
        } elseif ($mailEnabled) {
            SP\Config::setCacheConfigValue('mail_enabled', true);
            SP\Config::setCacheConfigValue('mail_requestsenabled', $mailRequests);
            SP\Config::setCacheConfigValue('mail_server', $mailServer);
            SP\Config::setCacheConfigValue('mail_port', $mailPort);
            SP\Config::setCacheConfigValue('mail_security', $mailSecurity);
            SP\Config::setCacheConfigValue('mail_from', $mailFrom);

            if ($mailAuth) {
                SP\Config::setCacheConfigValue('mail_authenabled', $mailAuth);
                SP\Config::setCacheConfigValue('mail_user', $mailUser);
                SP\Config::setCacheConfigValue('mail_pass', $mailPass);
            }

            $Log->addDescription(_('Correo habiltado'));
        } else {
            SP\Config::setCacheConfigValue('mail_enabled', false);
            SP\Config::setCacheConfigValue('mail_requestsenabled', false);
            SP\Config::setCacheConfigValue('mail_authenabled', false);

            $Log->addDescription(_('Correo deshabilitado'));
        }

        $Log->addDescription(sprintf('%s: %s', _('Sección'), _('Correo')));
    }

    try {
        SP\Config::writeConfig();
    } catch (\SP\SPException $e){
        $Log->addDescription($e->getMessage());
        $Log->addDescription($e->getHint());
        $Log->writeLog();

        SP\Response::printJSON($e->getMessage());
    }

    $Log->writeLog();

    SP\Email::sendEmail($Log);

    if ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_GENERAL) {
        // Recargar la aplicación completa para establecer nuevos valores
        SP\Util::reload();
    }

    SP\Response::printJSON(_('Configuración actualizada'), 0, $doActionOnClose);
} elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION) {
    $currentMasterPass = SP\Request::analyzeEncrypted('curMasterPwd');
    $newMasterPass = SP\Request::analyzeEncrypted('newMasterPwd');
    $newMasterPassR = SP\Request::analyzeEncrypted('newMasterPwdR');
    $confirmPassChange = SP\Request::analyze('confirmPassChange', 0, false, 1);
    $noAccountPassChange = SP\Request::analyze('chkNoAccountChange', 0, false, 1);

    if (!UserPass::checkUserUpdateMPass()) {
        SP\Response::printJSON(_('Clave maestra actualizada') . ';;' . _('Reinicie la sesión para cambiarla'));
    } elseif ($newMasterPass == '' && $currentMasterPass == '') {
        SP\Response::printJSON(_('Clave maestra no indicada'));
    } elseif ($confirmPassChange == 0) {
        SP\Response::printJSON(_('Se ha de confirmar el cambio de clave'));
    }

    if ($newMasterPass == $currentMasterPass) {
        SP\Response::printJSON(_('Las claves son idénticas'));
    } elseif ($newMasterPass != $newMasterPassR) {
        SP\Response::printJSON(_('Las claves maestras no coinciden'));
    } elseif (!SP\Crypt::checkHashPass($currentMasterPass, ConfigDB::getValue('masterPwd'), true)) {
        SP\Response::printJSON(_('La clave maestra actual no coincide'));
    }

    $hashMPass = SP\Crypt::mkHashPassword($newMasterPass);

    if (!$noAccountPassChange) {
        $Account = new SP\Account();

        if (!$Account->updateAccountsMasterPass($currentMasterPass, $newMasterPass)) {
            SP\Response::printJSON(_('Errores al actualizar las claves de las cuentas'));
        }

        $AccountHistory = new SP\AccountHistory();

        if (!$AccountHistory->updateAccountsMasterPass($currentMasterPass, $newMasterPass, $hashMPass)) {
            SP\Response::printJSON(_('Errores al actualizar las claves de las cuentas del histórico'));
        }

        if (!\SP\CustomFields::updateCustomFieldsCrypt($currentMasterPass, $newMasterPass)) {
            SP\Response::printJSON(_('Errores al actualizar datos de campos personalizados'));
        }
    }

    if (SP\Util::demoIsEnabled()) {
        SP\Response::printJSON(_('Ey, esto es una DEMO!!'));
    }

//    ConfigDB::readConfig();
    ConfigDB::setCacheConfigValue('masterPwd', $hashMPass);
    ConfigDB::setCacheConfigValue('lastupdatempass', time());

    if (ConfigDB::writeConfig()) {
        SP\Log::writeNewLogAndEmail(_('Actualizar Clave Maestra'));

        SP\Response::printJSON(_('Clave maestra actualizada'), 0);
    } else {
        SP\Response::printJSON(_('Error al guardar el hash de la clave maestra'));
    }

} elseif ($actionId === SP\Controller\ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS) {
    $tempMasterMaxTime = SP\Request::analyze('tmpass_maxtime', 3600);
    $tempMasterPass = CryptMasterPass::setTempMasterPass($tempMasterMaxTime);

    if ($tempMasterPass !== false && !empty($tempMasterPass)) {
        SP\Email::sendEmail(new \SP\Log(_('Generar Clave Temporal'), SP\Html::strongText(_('Clave') . ': ') . $tempMasterPass));

        SP\Response::printJSON(_('Clave Temporal Generada'), 0, $doActionOnClose);
    } else {
        SP\Response::printJSON(_('Error al generar clave temporal'));
    }
} else {
    SP\Response::printJSON(_('Acción Inválida'));
}