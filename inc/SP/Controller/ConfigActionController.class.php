<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Controller;

use SP\Account\Account;
use SP\Account\AccountHistory;
use SP\Config\Config;
use SP\Config\ConfigDB;
use SP\Core\ActionsInterface;
use SP\Core\Backup;
use SP\Core\Crypt;
use SP\Core\CryptMasterPass;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\Core\XmlExport;
use SP\Html\Html;
use SP\Http\Request;
use SP\Import\Import;
use SP\Import\ImportParams;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\CustomFields\CustomFieldsUtil;
use SP\Mgmt\Users\UserPass;
use SP\Util\Checks;
use SP\Util\Json;
use SP\Util\Util;

/**
 * Class ConfigActionController
 *
 * @package SP\Controller
 */
class ConfigActionController implements ItemControllerInterface
{
    use RequestControllerTrait;

    /**
     * ConfigActionController constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Realizar la acción solicitada en la la petición HTTP
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doAction()
    {
        try {
            switch ($this->actionId) {
                case ActionsInterface::ACTION_CFG_GENERAL:
                    $this->generalAction();
                    break;
                case ActionsInterface::ACTION_CFG_WIKI:
                    $this->wikiAction();
                    break;
                case ActionsInterface::ACTION_CFG_LDAP:
                    $this->ldapAction();
                    break;
                case ActionsInterface::ACTION_CFG_MAIL:
                    $this->mailAction();
                    break;
                case ActionsInterface::ACTION_CFG_ENCRYPTION:
                    $this->masterPassAction();
                    break;
                case ActionsInterface::ACTION_CFG_ENCRYPTION_TEMPPASS:
                    $this->tempMasterPassAction();
                    break;
                case ActionsInterface::ACTION_CFG_IMPORT:
                    $this->importAction();
                    break;
                case ActionsInterface::ACTION_CFG_EXPORT:
                    $this->exportAction();
                    break;
                case ActionsInterface::ACTION_CFG_BACKUP:
                    $this->backupAction();
                    break;
                default:
                    $this->invalidAction();
            }
        } catch (\Exception $e) {
            $this->jsonResponse->setDescription($e->getMessage());
        }

        Json::returnJson($this->jsonResponse);
    }

    /**
     * Accion para opciones configuración general
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function generalAction()
    {
        $Log = Log::newLog(_('Modificar Configuración'));
        $Config = Session::getConfig();

        // General
        $siteLang = Request::analyze('sitelang');
        $siteTheme = Request::analyze('sitetheme', 'material-blue');
        $sessionTimeout = Request::analyze('session_timeout', 300);
        $httpsEnabled = Request::analyze('https_enabled', false, false, true);
        $debugEnabled = Request::analyze('debug', false, false, true);
        $maintenanceEnabled = Request::analyze('maintenance', false, false, true);
        $checkUpdatesEnabled = Request::analyze('updates', false, false, true);
        $checkNoticesEnabled = Request::analyze('notices', false, false, true);

        $Config->setSiteLang($siteLang);
        $Config->setSiteTheme($siteTheme);
        $Config->setSessionTimeout($sessionTimeout);
        $Config->setHttpsEnabled($httpsEnabled);
        $Config->setDebug($debugEnabled);
        $Config->setMaintenance($maintenanceEnabled);
        $Config->setCheckUpdates($checkUpdatesEnabled);
        $Config->setChecknotices($checkNoticesEnabled);

        // Events
        $logEnabled = Request::analyze('log_enabled', false, false, true);
        $syslogEnabled = Request::analyze('syslog_enabled', false, false, true);
        $remoteSyslogEnabled = Request::analyze('remotesyslog_enabled', false, false, true);
        $syslogServer = Request::analyze('remotesyslog_server');
        $syslogPort = Request::analyze('remotesyslog_port', 0);

        $Config->setLogEnabled($logEnabled);
        $Config->setSyslogEnabled($syslogEnabled);

        if ($remoteSyslogEnabled && (!$syslogServer || !$syslogPort)) {
            $this->jsonResponse->setDescription(_('Faltan parámetros de syslog remoto'));
            return;
        } elseif ($remoteSyslogEnabled) {
            $Config->setSyslogRemoteEnabled($remoteSyslogEnabled);
            $Config->setSyslogServer($syslogServer);
            $Config->setSyslogPort($syslogPort);
        } else {
            $Config->setSyslogRemoteEnabled(false);

            $Log->addDescription(_('Syslog remoto deshabilitado'));
        }

        // Accounts
        $globalSearchEnabled = Request::analyze('globalsearch', false, false, true);
        $accountPassToImageEnabled = Request::analyze('account_passtoimage', false, false, true);
        $accountLinkEnabled = Request::analyze('account_link', false, false, true);
        $accountCount = Request::analyze('account_count', 10);
        $resultsAsCardsEnabled = Request::analyze('resultsascards', false, false, true);

        $Config->setGlobalSearch($globalSearchEnabled);
        $Config->setAccountPassToImage($accountPassToImageEnabled);
        $Config->setAccountLink($accountLinkEnabled);
        $Config->setAccountCount($accountCount);
        $Config->setResultsAsCards($resultsAsCardsEnabled);

        // Files
        $filesEnabled = Request::analyze('files_enabled', false, false, true);
        $filesAllowedSize = Request::analyze('files_allowed_size', 1024);
        $filesAllowedExts = Request::analyze('files_allowed_exts');

        if ($filesEnabled && $filesAllowedSize >= 16384) {
            $this->jsonResponse->setDescription(_('El tamaño máximo por archivo es de 16MB'));
            Json::returnJson($this->jsonResponse);
        }

        if (!empty($filesAllowedExts)) {
            $exts = explode(',', $filesAllowedExts);
            array_walk($exts, function (&$value) {
                if (preg_match('/[^a-z0-9_-]+/i', $value)) {
                    $this->jsonResponse->setDescription(sprintf('%s: %s', _('Extensión no permitida'), $value));
                    Json::returnJson($this->jsonResponse);
                }
            });
            $Config->setFilesAllowedExts($exts);
        } else {
            $Config->setFilesAllowedExts([]);
        }

        $Config->setFilesEnabled($filesEnabled);
        $Config->setFilesAllowedSize($filesAllowedSize);

        // Public Links
        $pubLinksEnabled = Request::analyze('publinks_enabled', false, false, true);
        $pubLinksImageEnabled = Request::analyze('publinks_image_enabled', false, false, true);
        $pubLinksMaxTime = Request::analyze('publinks_maxtime', 10);
        $pubLinksMaxViews = Request::analyze('publinks_maxviews', 3);

        $Config->setPublinksEnabled($pubLinksEnabled);
        $Config->setPublinksImageEnabled($pubLinksImageEnabled);
        $Config->setPublinksMaxTime($pubLinksMaxTime * 60);
        $Config->setPublinksMaxViews($pubLinksMaxViews);

        // Proxy
        $proxyEnabled = Request::analyze('proxy_enabled', false, false, true);
        $proxyServer = Request::analyze('proxy_server');
        $proxyPort = Request::analyze('proxy_port', 0);
        $proxyUser = Request::analyze('proxy_user');
        $proxyPass = Request::analyzeEncrypted('proxy_pass');


        // Valores para Proxy
        if ($proxyEnabled && (!$proxyServer || !$proxyPort)) {
            $this->jsonResponse->setDescription(_('Faltan parámetros de Proxy'));
            return;
        } elseif ($proxyEnabled) {
            $Config->setProxyEnabled(true);
            $Config->setProxyServer($proxyServer);
            $Config->setProxyPort($proxyPort);
            $Config->setProxyUser($proxyUser);
            $Config->setProxyPass($proxyPass);

            $Log->addDescription(_('Proxy habiltado'));
        } else {
            $Config->setProxyEnabled(false);

            $Log->addDescription(_('Proxy deshabilitado'));
        }

        $Log->addDetails(_('Sección'), _('General'));

        // Recargar la aplicación completa para establecer nuevos valores
        Util::reload();

        $this->saveConfig($Log);
    }

    /**
     * Guardar la configuración
     *
     * @param Log $Log instancia de Log
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function saveConfig(Log $Log)
    {
        try {
            if (Checks::demoIsEnabled()) {
                $this->jsonResponse->setDescription(_('Ey, esto es una DEMO!!'));
                return;
            }

            Config::saveConfig();

            $this->jsonResponse->setStatus(0);
            $this->jsonResponse->setDescription(_('Configuración actualizada'));
        } catch (SPException $e) {
            $Log->addDescription(_('Error al guardar la configuración'));
            $Log->addDetails($e->getMessage(), $e->getHint());

            $this->jsonResponse->setDescription($e->getMessage());
        }

        $Log->writeLog();
        Email::sendEmail($Log);
    }

    /**
     * Acción para opciones de Wiki
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function wikiAction()
    {
        $Log = Log::newLog(_('Modificar Configuración'));
        $Config = Session::getConfig();

        // Wiki
        $wikiEnabled = Request::analyze('wiki_enabled', false, false, true);
        $wikiSearchUrl = Request::analyze('wiki_searchurl');
        $wikiPageUrl = Request::analyze('wiki_pageurl');
        $wikiFilter = Request::analyze('wiki_filter');

        // Valores para la conexión a la Wiki
        if ($wikiEnabled && (!$wikiSearchUrl || !$wikiPageUrl || !$wikiFilter)) {
            $this->jsonResponse->setDescription(_('Faltan parámetros de Wiki'));
            return;
        } elseif ($wikiEnabled) {
            $Config->setWikiEnabled(true);
            $Config->setWikiSearchurl($wikiSearchUrl);
            $Config->setWikiPageurl($wikiPageUrl);
            $Config->setWikiFilter(explode(',', $wikiFilter));

            $Log->addDescription(_('Wiki habiltada'));
        } else {
            $Config->setWikiEnabled(false);

            $Log->addDescription(_('Wiki deshabilitada'));
        }

        // DokuWiki
        $dokuWikiEnabled = Request::analyze('dokuwiki_enabled', false, false, true);
        $dokuWikiUrl = Request::analyze('dokuwiki_url');
        $dokuWikiUrlBase = Request::analyze('dokuwiki_urlbase');
        $dokuWikiUser = Request::analyze('dokuwiki_user');
        $dokuWikiPass = Request::analyzeEncrypted('dokuwiki_pass');
        $dokuWikiNamespace = Request::analyze('dokuwiki_namespace');

        // Valores para la conexión a la API de DokuWiki
        if ($dokuWikiEnabled && (!$dokuWikiUrl || !$dokuWikiUrlBase)) {
            $this->jsonResponse->setDescription(_('Faltan parámetros de DokuWiki'));
            return;
        } elseif ($dokuWikiEnabled) {
            $Config->setDokuwikiEnabled(true);
            $Config->setDokuwikiUrl($dokuWikiUrl);
            $Config->setDokuwikiUrlBase(trim($dokuWikiUrlBase, '/'));
            $Config->setDokuwikiUser($dokuWikiUser);
            $Config->setDokuwikiPass($dokuWikiPass);
            $Config->setDokuwikiNamespace($dokuWikiNamespace);

            $Log->addDescription(_('DokuWiki habiltada'));
        } else {
            $Config->setDokuwikiEnabled(false);

            $Log->addDescription(_('DokuWiki deshabilitada'));
        }

        $Log->addDetails(_('Sección'), _('Wiki'));

        $this->saveConfig($Log);
    }

    /**
     * Acción para opciones de LDAP
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function ldapAction()
    {
        $Log = Log::newLog(_('Modificar Configuración'));
        $Config = Session::getConfig();

        // LDAP
        $ldapEnabled = Request::analyze('ldap_enabled', false, false, true);
        $ldapADSEnabled = Request::analyze('ldap_ads', false, false, true);
        $ldapServer = Request::analyze('ldap_server');
        $ldapBase = Request::analyze('ldap_base');
        $ldapGroup = Request::analyze('ldap_group');
        $ldapDefaultGroup = Request::analyze('ldap_defaultgroup', 0);
        $ldapDefaultProfile = Request::analyze('ldap_defaultprofile', 0);
        $ldapBindUser = Request::analyze('ldap_binduser');
        $ldapBindPass = Request::analyzeEncrypted('ldap_bindpass');

        // Valores para la configuración de LDAP
        if ($ldapEnabled && (!$ldapServer || !$ldapBase || !$ldapBindUser)) {
            $this->jsonResponse->setDescription(_('Faltan parámetros de LDAP'));
            return;
        } elseif ($ldapEnabled) {
            $Config->setLdapEnabled(true);
            $Config->setLdapAds($ldapADSEnabled);
            $Config->setLdapServer($ldapServer);
            $Config->setLdapBase($ldapBase);
            $Config->setLdapGroup($ldapGroup);
            $Config->setLdapDefaultGroup($ldapDefaultGroup);
            $Config->setLdapDefaultProfile($ldapDefaultProfile);
            $Config->setLdapBindUser($ldapBindUser);
            $Config->setLdapBindPass($ldapBindPass);

            $Log->addDescription(_('LDAP habiltado'));
        } else {
            $Config->setLdapEnabled(false);

            $Log->addDescription(_('LDAP deshabilitado'));
        }

        $Log->addDetails(_('Sección'), _('LDAP'));

        $this->saveConfig($Log);
    }

    /**
     * Accion para opciones de correo
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function mailAction()
    {
        $Log = Log::newLog(_('Modificar Configuración'));
        $Config = Session::getConfig();

        // Mail
        $mailEnabled = Request::analyze('mail_enabled', false, false, true);
        $mailServer = Request::analyze('mail_server');
        $mailPort = Request::analyze('mail_port', 25);
        $mailUser = Request::analyze('mail_user');
        $mailPass = Request::analyzeEncrypted('mail_pass');
        $mailSecurity = Request::analyze('mail_security');
        $mailFrom = Request::analyze('mail_from');
        $mailRequests = Request::analyze('mail_requestsenabled', false, false, true);
        $mailAuth = Request::analyze('mail_authenabled', false, false, true);

        // Valores para la configuración del Correo
        if ($mailEnabled && (!$mailServer || !$mailFrom)) {
            $this->jsonResponse->setDescription(_('Faltan parámetros de Correo'));
            return;
        } elseif ($mailEnabled) {
            $Config->setMailEnabled(true);
            $Config->setMailRequestsEnabled($mailRequests);
            $Config->setMailServer($mailServer);
            $Config->setMailPort($mailPort);
            $Config->setMailSecurity($mailSecurity);
            $Config->setMailFrom($mailFrom);

            if ($mailAuth) {
                $Config->setMailAuthenabled($mailAuth);
                $Config->setMailUser($mailUser);
                $Config->setMailPass($mailPass);
            }

            $Log->addDescription(_('Correo habiltado'));
        } else {
            $Config->setMailEnabled(false);
            $Config->setMailRequestsEnabled(false);
            $Config->setMailAuthenabled(false);

            $Log->addDescription(_('Correo deshabilitado'));
        }

        $Log->addDetails(_('Sección'), _('Correo'));

        $this->saveConfig($Log);
    }

    /**
     * Acción para cambio de clave maestra
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function masterPassAction()
    {
        $currentMasterPass = Request::analyzeEncrypted('curMasterPwd');
        $newMasterPass = Request::analyzeEncrypted('newMasterPwd');
        $newMasterPassR = Request::analyzeEncrypted('newMasterPwdR');
        $confirmPassChange = Request::analyze('confirmPassChange', 0, false, 1);
        $noAccountPassChange = Request::analyze('chkNoAccountChange', 0, false, 1);

        if (!UserPass::getItem(Session::getUserData())->checkUserUpdateMPass()) {
            $this->jsonResponse->setDescription(_('Clave maestra actualizada'));
            $this->jsonResponse->addMessage(_('Reinicie la sesión para cambiarla'));
            return;
        } elseif ($newMasterPass === '' && $currentMasterPass === '') {
            $this->jsonResponse->setDescription(_('Clave maestra no indicada'));
            return;
        } elseif ($confirmPassChange === false) {
            $this->jsonResponse->setDescription(_('Se ha de confirmar el cambio de clave'));
            return;
        }

        if ($newMasterPass === $currentMasterPass) {
            $this->jsonResponse->setDescription(_('Las claves son idénticas'));
            return;
        } elseif ($newMasterPass !== $newMasterPassR) {
            $this->jsonResponse->setDescription(_('Las claves maestras no coinciden'));
            return;
        } elseif (!Crypt::checkHashPass($currentMasterPass, ConfigDB::getValue('masterPwd'), true)) {
            $this->jsonResponse->setDescription(_('La clave maestra actual no coincide'));
            return;
        }

        if (Checks::demoIsEnabled()) {
            $this->jsonResponse->setDescription(_('Ey, esto es una DEMO!!'));
            return;
        }

        $hashMPass = Crypt::mkHashPassword($newMasterPass);

        if (!$noAccountPassChange) {
            $Account = new Account();

            if (!$Account->updateAccountsMasterPass($currentMasterPass, $newMasterPass)) {
                $this->jsonResponse->setDescription(_('Errores al actualizar las claves de las cuentas'));
                return;
            }

            $AccountHistory = new AccountHistory();

            if (!$AccountHistory->updateAccountsMasterPass($currentMasterPass, $newMasterPass, $hashMPass)) {
                $this->jsonResponse->setDescription(_('Errores al actualizar las claves de las cuentas del histórico'));
                return;
            }

            if (!CustomFieldsUtil::updateCustomFieldsCrypt($currentMasterPass, $newMasterPass)) {
                $this->jsonResponse->setDescription(_('Errores al actualizar datos de campos personalizados'));
                return;
            }
        }

        ConfigDB::setCacheConfigValue('masterPwd', $hashMPass);
        ConfigDB::setCacheConfigValue('lastupdatempass', time());

        $Log = new Log(_('Actualizar Clave Maestra'));

        if (ConfigDB::writeConfig()) {
            $Log->addDescription(_('Clave maestra actualizada'));

            $this->jsonResponse->setStatus(0);
        } else {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al guardar el hash de la clave maestra'));
        }

        $this->jsonResponse->setDescription($Log->getDescription());

        $Log->writeLog();
        Email::sendEmail($Log);
    }

    /**
     * Acción para generar clave maestra temporal
     */
    protected function tempMasterPassAction()
    {
        $tempMasterMaxTime = Request::analyze('tmpass_maxtime', 3600);
        $tempMasterPass = CryptMasterPass::setTempMasterPass($tempMasterMaxTime);

        $Log = new Log('Generar Clave Temporal');

        if ($tempMasterPass !== false && !empty($tempMasterPass)) {
            $Log->addDescription(_('Clave Temporal Generada'));
            $Log->addDetails(Html::strongText(_('Clave')), $tempMasterPass);

            $this->jsonResponse->setStatus(0);
        } else {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al generar clave temporal'));

        }

        $this->jsonResponse->setDescription($Log->getDescription());

        $Log->writeLog();
        Email::sendEmail($Log);
    }

    /**
     * Acción para importar cuentas
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function importAction()
    {
        if (Checks::demoIsEnabled()) {
            $this->jsonResponse->setDescription(_('Ey, esto es una DEMO!!'));
            return;
        }

        $ImportParams = new ImportParams();
        $ImportParams->setDefaultUser(Request::analyze('defUser', Session::getUserData()->getUserId()));
        $ImportParams->setDefaultGroup(Request::analyze('defGroup', Session::getUserData()->getUserGroupId()));
        $ImportParams->setImportPwd(Request::analyzeEncrypted('importPwd'));
        $ImportParams->setImportMasterPwd(Request::analyzeEncrypted('importMasterPwd'));
        $ImportParams->setCsvDelimiter(Request::analyze('csvDelimiter'));

        $Import = new Import($ImportParams);
        $Message = $Import->doImport($_FILES['inFile']);

        $this->jsonResponse->setDescription($Message->getDescription());
        $this->jsonResponse->addMessage($Message->getHint());
        $this->jsonResponse->setStatus(0);
    }

    /**
     * Acción para exportar cuentas
     */
    protected function exportAction()
    {
        $exportPassword = Request::analyzeEncrypted('exportPwd');
        $exportPasswordR = Request::analyzeEncrypted('exportPwdR');

        if (!empty($exportPassword) && $exportPassword !== $exportPasswordR) {
            $this->jsonResponse->setDescription(_('Las claves no coinciden'));
            return;
        }

        if (!XmlExport::doExport($exportPassword)) {
            $this->jsonResponse->setDescription(_('Error al realizar la exportación'));
            $this->jsonResponse->addMessage(_('Revise el registro de eventos para más detalles'));
            return;
        }

        $this->jsonResponse->setStatus(0);
        $this->jsonResponse->setDescription(_('Proceso de exportación finalizado'));
    }

    /**
     * Acción para realizar el backup de sysPass
     */
    protected function backupAction()
    {
        if (Checks::demoIsEnabled()) {
            $this->jsonResponse->setDescription(_('Ey, esto es una DEMO!!'));
            return;
        }

        if (!Backup::doBackup()) {
            $this->jsonResponse->setDescription(_('Error al realizar el backup'));
            $this->jsonResponse->addMessage(_('Revise el registro de eventos para más detalles'));
            return;
        }

        $this->jsonResponse->setStatus(0);
        $this->jsonResponse->setDescription(_('Proceso de backup finalizado'));
    }
}