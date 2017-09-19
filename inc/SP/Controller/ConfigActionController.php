<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use SP\Account\AccountCrypt;
use SP\Account\AccountHistoryCrypt;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Config\ConfigDB;
use SP\Core\ActionsInterface;
use SP\Core\Backup;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\CryptMasterPass;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Core\Messages\LogMessage;
use SP\Core\Messages\NoticeMessage;
use SP\Core\Session;
use SP\Core\TaskFactory;
use SP\Core\XmlExport;
use SP\Http\Request;
use SP\Import\Import;
use SP\Import\ImportParams;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\CustomFields\CustomFieldsUtil;
use SP\Mgmt\Users\UserPass;
use SP\Mgmt\Users\UserUtil;
use SP\Storage\DB;
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
     * @var ConfigData
     */
    private $ConfigData;

    /**
     * ConfigActionController constructor.
     */
    public function __construct()
    {
        $this->init();

        $this->ConfigData = Config::getConfig();
    }

    /**
     * Realizar la acción solicitada en la la petición HTTP
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doAction()
    {
        $this->LogMessage = new LogMessage();

        try {
            switch ($this->actionId) {
                case ActionsInterface::ACTION_CFG_GENERAL:
                    $this->generalAction();
                    break;
                case ActionsInterface::ACTION_CFG_ACCOUNTS:
                    $this->accountsAction();
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
                case ActionsInterface::ACTION_CFG_ENCRYPTION_REFRESH:
                    $this->masterPassRefreshAction();
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
            $this->JsonResponse->setDescription($e->getMessage());
        }

        if ($this->LogMessage->getAction() !== null) {
            $Log = new Log($this->LogMessage);
            $Log->writeLog();

            $this->JsonResponse->setDescription($this->LogMessage->getHtmlDescription(true));
        }

        Json::returnJson($this->JsonResponse);
    }

    /**
     * Accion para opciones configuración general
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function generalAction()
    {
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

        $this->ConfigData->setSiteLang($siteLang);
        $this->ConfigData->setSiteTheme($siteTheme);
        $this->ConfigData->setSessionTimeout($sessionTimeout);
        $this->ConfigData->setHttpsEnabled($httpsEnabled);
        $this->ConfigData->setDebug($debugEnabled);
        $this->ConfigData->setMaintenance($maintenanceEnabled);
        $this->ConfigData->setCheckUpdates($checkUpdatesEnabled);
        $this->ConfigData->setChecknotices($checkNoticesEnabled);
        $this->ConfigData->setEncryptSession($encryptSessionEnabled);

        // Events
        $logEnabled = Request::analyze('log_enabled', false, false, true);
        $syslogEnabled = Request::analyze('syslog_enabled', false, false, true);
        $remoteSyslogEnabled = Request::analyze('remotesyslog_enabled', false, false, true);
        $syslogServer = Request::analyze('remotesyslog_server');
        $syslogPort = Request::analyze('remotesyslog_port', 0);

        $this->ConfigData->setLogEnabled($logEnabled);
        $this->ConfigData->setSyslogEnabled($syslogEnabled);

        if ($remoteSyslogEnabled && (!$syslogServer || !$syslogPort)) {
            $this->JsonResponse->setDescription(__('Faltan parámetros de syslog remoto', false));
            return;
        }

        if ($remoteSyslogEnabled) {
            $this->ConfigData->setSyslogRemoteEnabled($remoteSyslogEnabled);
            $this->ConfigData->setSyslogServer($syslogServer);
            $this->ConfigData->setSyslogPort($syslogPort);
        } elseif ($this->ConfigData->isSyslogEnabled()) {
            $this->ConfigData->setSyslogRemoteEnabled(false);

            $this->LogMessage->addDescription(__('Syslog remoto deshabilitado', false));
        }

        // Proxy
        $proxyEnabled = Request::analyze('proxy_enabled', false, false, true);
        $proxyServer = Request::analyze('proxy_server');
        $proxyPort = Request::analyze('proxy_port', 0);
        $proxyUser = Request::analyze('proxy_user');
        $proxyPass = Request::analyzeEncrypted('proxy_pass');


        // Valores para Proxy
        if ($proxyEnabled && (!$proxyServer || !$proxyPort)) {
            $this->JsonResponse->setDescription(__('Faltan parámetros de Proxy', false));
            return;
        }

        if ($proxyEnabled) {
            $this->ConfigData->setProxyEnabled(true);
            $this->ConfigData->setProxyServer($proxyServer);
            $this->ConfigData->setProxyPort($proxyPort);
            $this->ConfigData->setProxyUser($proxyUser);
            $this->ConfigData->setProxyPass($proxyPass);

            $this->LogMessage->addDescription(__('Proxy habiltado', false));
        } elseif ($this->ConfigData->isProxyEnabled()) {
            $this->ConfigData->setProxyEnabled(false);

            $this->LogMessage->addDescription(__('Proxy deshabilitado', false));
        }

        $this->LogMessage->addDetails(__('Sección', false), __('General', false));

        $this->saveConfig();
    }

    /**
     * Guardar la configuración
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function saveConfig()
    {
        try {
            if (Checks::demoIsEnabled()) {
                $this->JsonResponse->setDescription(__('Ey, esto es una DEMO!!', false));
                return;
            }

            Config::saveConfig($this->ConfigData);

            if (Config::getConfig()->isMaintenance()) {
                Util::lockApp(false);
            } elseif (Init::$LOCK > 0) {
                Util::unlockApp(false);
            }

            $this->JsonResponse->setStatus(0);

            $this->LogMessage->addDescription(__('Configuración actualizada', false));
        } catch (SPException $e) {
            $this->LogMessage->addDescription(__('Error al guardar la configuración', false));
            $this->LogMessage->addDetails($e->getMessage(), $e->getHint());
        }

        $this->LogMessage->setAction(__('Modificar Configuración', false));

        Email::sendEmail($this->LogMessage);
    }

    /**
     * Accion para opciones configuración de cuentas
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function accountsAction()
    {
        // Accounts
        $globalSearchEnabled = Request::analyze('globalsearch', false, false, true);
        $accountPassToImageEnabled = Request::analyze('account_passtoimage', false, false, true);
        $accountLinkEnabled = Request::analyze('account_link', false, false, true);
        $accountFullGroupAccessEnabled = Request::analyze('account_fullgroup_access', false, false, true);
        $accountCount = Request::analyze('account_count', 10);
        $resultsAsCardsEnabled = Request::analyze('resultsascards', false, false, true);

        $this->ConfigData->setGlobalSearch($globalSearchEnabled);
        $this->ConfigData->setAccountPassToImage($accountPassToImageEnabled);
        $this->ConfigData->setAccountLink($accountLinkEnabled);
        $this->ConfigData->setAccountFullGroupAccess($accountFullGroupAccessEnabled);
        $this->ConfigData->setAccountCount($accountCount);
        $this->ConfigData->setResultsAsCards($resultsAsCardsEnabled);

        // Files
        $filesEnabled = Request::analyze('files_enabled', false, false, true);
        $filesAllowedSize = Request::analyze('files_allowed_size', 1024);
        $filesAllowedExts = Request::analyze('files_allowed_exts');

        if ($filesEnabled && $filesAllowedSize >= 16384) {
            $this->JsonResponse->setDescription(__('El tamaño máximo por archivo es de 16MB', false));
            return;
        }

        $this->ConfigData->setFilesAllowedExts($filesAllowedExts);
        $this->ConfigData->setFilesEnabled($filesEnabled);
        $this->ConfigData->setFilesAllowedSize($filesAllowedSize);

        // Public Links
        $pubLinksEnabled = Request::analyze('publinks_enabled', false, false, true);
        $pubLinksImageEnabled = Request::analyze('publinks_image_enabled', false, false, true);
        $pubLinksMaxTime = Request::analyze('publinks_maxtime', 10);
        $pubLinksMaxViews = Request::analyze('publinks_maxviews', 3);

        $this->ConfigData->setPublinksEnabled($pubLinksEnabled);
        $this->ConfigData->setPublinksImageEnabled($pubLinksImageEnabled);
        $this->ConfigData->setPublinksMaxTime($pubLinksMaxTime * 60);
        $this->ConfigData->setPublinksMaxViews($pubLinksMaxViews);

        $this->LogMessage->addDetails(__('Sección', false), __('Cuentas', false));

        $this->saveConfig();
    }

    /**
     * Acción para opciones de Wiki
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function wikiAction()
    {
        // Wiki
        $wikiEnabled = Request::analyze('wiki_enabled', false, false, true);
        $wikiSearchUrl = Request::analyze('wiki_searchurl');
        $wikiPageUrl = Request::analyze('wiki_pageurl');
        $wikiFilter = Request::analyze('wiki_filter');

        // Valores para la conexión a la Wiki
        if ($wikiEnabled && (!$wikiSearchUrl || !$wikiPageUrl || !$wikiFilter)) {
            $this->JsonResponse->setDescription(__('Faltan parámetros de Wiki', false));
            return;
        }

        if ($wikiEnabled) {
            $this->ConfigData->setWikiEnabled(true);
            $this->ConfigData->setWikiSearchurl($wikiSearchUrl);
            $this->ConfigData->setWikiPageurl($wikiPageUrl);
            $this->ConfigData->setWikiFilter(explode(',', $wikiFilter));

            $this->LogMessage->addDescription(__('Wiki habiltada', false));
        } elseif ($this->ConfigData->isWikiEnabled()) {
            $this->ConfigData->setWikiEnabled(false);

            $this->LogMessage->addDescription(__('Wiki deshabilitada', false));
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
            $this->JsonResponse->setDescription(__('Faltan parámetros de DokuWiki', false));
            return;
        }

        if ($dokuWikiEnabled) {
            $this->ConfigData->setDokuwikiEnabled(true);
            $this->ConfigData->setDokuwikiUrl($dokuWikiUrl);
            $this->ConfigData->setDokuwikiUrlBase(trim($dokuWikiUrlBase, '/'));
            $this->ConfigData->setDokuwikiUser($dokuWikiUser);
            $this->ConfigData->setDokuwikiPass($dokuWikiPass);
            $this->ConfigData->setDokuwikiNamespace($dokuWikiNamespace);

            $this->LogMessage->addDescription(__('DokuWiki habiltada', false));
        } elseif ($this->ConfigData->isDokuwikiEnabled()) {
            $this->ConfigData->setDokuwikiEnabled(false);

            $this->LogMessage->addDescription(__('DokuWiki deshabilitada', false));
        }

        $this->LogMessage->addDetails(__('Sección', false), __('Wiki', false));

        $this->saveConfig();
    }

    /**
     * Acción para opciones de LDAP
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function ldapAction()
    {
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
            $this->JsonResponse->setDescription(__('Faltan parámetros de LDAP'));
            return;
        }

        if ($ldapEnabled) {
            $this->ConfigData->setLdapEnabled(true);
            $this->ConfigData->setLdapAds($ldapADSEnabled);
            $this->ConfigData->setLdapServer($ldapServer);
            $this->ConfigData->setLdapBase($ldapBase);
            $this->ConfigData->setLdapGroup($ldapGroup);
            $this->ConfigData->setLdapDefaultGroup($ldapDefaultGroup);
            $this->ConfigData->setLdapDefaultProfile($ldapDefaultProfile);
            $this->ConfigData->setLdapBindUser($ldapBindUser);
            $this->ConfigData->setLdapBindPass($ldapBindPass);

            $this->LogMessage->addDescription(__('LDAP habiltado', false));
        } elseif ($this->ConfigData->isLdapEnabled()) {
            $this->ConfigData->setLdapEnabled(false);

            $this->LogMessage->addDescription(__('LDAP deshabilitado', false));
        }

        $this->LogMessage->addDetails(__('Sección', false), __('LDAP', false));
        $this->JsonResponse->setStatus(0);

        $this->saveConfig();
    }

    /**
     * Accion para opciones de correo
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function mailAction()
    {
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
            $this->JsonResponse->setDescription(__('Faltan parámetros de Correo'));
            return;
        }

        if ($mailEnabled) {
            $this->ConfigData->setMailEnabled(true);
            $this->ConfigData->setMailRequestsEnabled($mailRequests);
            $this->ConfigData->setMailServer($mailServer);
            $this->ConfigData->setMailPort($mailPort);
            $this->ConfigData->setMailSecurity($mailSecurity);
            $this->ConfigData->setMailFrom($mailFrom);

            if ($mailAuth) {
                $this->ConfigData->setMailAuthenabled($mailAuth);
                $this->ConfigData->setMailUser($mailUser);
                $this->ConfigData->setMailPass($mailPass);
            }

            $this->LogMessage->addDescription(__('Correo habiltado', false));
        } elseif ($this->ConfigData->isMailEnabled()) {
            $this->ConfigData->setMailEnabled(false);
            $this->ConfigData->setMailRequestsEnabled(false);
            $this->ConfigData->setMailAuthenabled(false);

            $this->LogMessage->addDescription(__('Correo deshabilitado', false));
        }

        $this->LogMessage->addDetails(__('Sección', false), __('Correo', false));
        $this->JsonResponse->setStatus(0);

        $this->saveConfig();
    }

    /**
     * Acción para cambio de clave maestra
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \phpmailer\phpmailerException
     */
    protected function masterPassAction()
    {
        $currentMasterPass = Request::analyzeEncrypted('curMasterPwd');
        $newMasterPass = Request::analyzeEncrypted('newMasterPwd');
        $newMasterPassR = Request::analyzeEncrypted('newMasterPwdR');
        $confirmPassChange = Request::analyze('confirmPassChange', 0, false, 1);
        $noAccountPassChange = Request::analyze('chkNoAccountChange', 0, false, 1);

        if (!UserPass::checkUserUpdateMPass(Session::getUserData()->getUserId())) {
            $this->JsonResponse->setDescription(__('Clave maestra actualizada', false));
            $this->JsonResponse->addMessage(__('Reinicie la sesión para cambiarla', false));
            $this->JsonResponse->setStatus(100);
            return;
        }

        if (empty($newMasterPass) || empty($currentMasterPass)) {
            $this->JsonResponse->setDescription(__('Clave maestra no indicada'));
            return;
        }

        if ($confirmPassChange === false) {
            $this->JsonResponse->setDescription(__('Se ha de confirmar el cambio de clave', false));
            return;
        }

        if ($newMasterPass === $currentMasterPass) {
            $this->JsonResponse->setDescription(__('Las claves son idénticas', false));
            return;
        }

        if ($newMasterPass !== $newMasterPassR) {
            $this->JsonResponse->setDescription(__('Las claves maestras no coinciden', false));
            return;
        }

        if (!Hash::checkHashKey($currentMasterPass, ConfigDB::getValue('masterPwd'))) {
            $this->JsonResponse->setDescription(__('La clave maestra actual no coincide', false));
            return;
        }

        if (Checks::demoIsEnabled()) {
            $this->JsonResponse->setDescription(__('Ey, esto es una DEMO!!', false));
            return;
        }

        if (!$noAccountPassChange) {
            Util::lockApp();

            if (!DB::beginTransaction()) {
                $this->JsonResponse->setDescription(__('No es posible iniciar una transacción', false));
                return;
            }

            TaskFactory::createTask(__FUNCTION__, Request::analyze('taskId'));

            $Account = new AccountCrypt();

            if (!$Account->updatePass($currentMasterPass, $newMasterPass)) {
                DB::rollbackTransaction();

                TaskFactory::endTask();

                $this->JsonResponse->setDescription(__('Errores al actualizar las claves de las cuentas', false));
                return;
            }

            $AccountHistory = new AccountHistoryCrypt();

            if (!$AccountHistory->updatePass($currentMasterPass, $newMasterPass)) {
                DB::rollbackTransaction();

                TaskFactory::endTask();

                $this->JsonResponse->setDescription(__('Errores al actualizar las claves de las cuentas del histórico', false));
                return;
            }

            if (!CustomFieldsUtil::updateCustomFieldsCrypt($currentMasterPass, $newMasterPass)) {
                DB::rollbackTransaction();

                TaskFactory::endTask();

                $this->JsonResponse->setDescription(__('Errores al actualizar datos de campos personalizados', false));
                return;
            }

            if (!DB::endTransaction()) {
                TaskFactory::endTask();

                $this->JsonResponse->setDescription(__('No es posible finalizar una transacción', false));
                return;
            }

            TaskFactory::endTask();

            Util::unlockApp();
        }

        ConfigDB::setCacheConfigValue('masterPwd', Hash::hashKey($newMasterPass));
        ConfigDB::setCacheConfigValue('lastupdatempass', time());

        $this->LogMessage->setAction(__('Actualizar Clave Maestra', false));

        if (ConfigDB::writeConfig()) {
            $this->LogMessage->addDescription(__('Clave maestra actualizada', false));

            $this->JsonResponse->addMessage(__('Reinicie la sesión para cambiarla', false));
            $this->JsonResponse->setStatus(100);
        } else {
            $this->LogMessage->addDescription(__('Error al guardar el hash de la clave maestra', false));
        }

        Email::sendEmail($this->LogMessage);
    }

    /**
     * Regenerar el hash de la clave maestra
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    protected function masterPassRefreshAction()
    {
        if (Checks::demoIsEnabled()) {
            $this->JsonResponse->setDescription(__('Ey, esto es una DEMO!!', false));
            return;
        }

        $this->LogMessage->setAction(__('Actualizar Clave Maestra', false));

        if (ConfigDB::setValue('masterPwd', Hash::hashKey(CryptSession::getSessionKey()))) {
            $this->LogMessage->addDescription(__('Hash de clave maestra actualizado', false));

            $this->JsonResponse->setStatus(0);
        } else {
            $this->LogMessage->addDescription(__('Error al actualizar el hash de la clave maestra', false));
        }

        Email::sendEmail($this->LogMessage);
    }

    /**
     * Acción para generar clave maestra temporal
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    protected function tempMasterPassAction()
    {
        $tempMasterMaxTime = Request::analyze('tmpass_maxtime', 3600);
        $tempMasterPass = CryptMasterPass::setTempMasterPass($tempMasterMaxTime);
        $tempMasterGroup = Request::analyze('tmpass_group', 0);
        $tempMasterEmail = Request::analyze('tmpass_chkSendEmail', 0, false, 1);

        $this->LogMessage->setAction(__('Generar Clave Temporal', false));

        if ($tempMasterPass !== false && !empty($tempMasterPass)) {
            $this->LogMessage->addDescription(__('Clave Temporal Generada', false));

            if ($tempMasterEmail) {
                $Message = new NoticeMessage();
                $Message->setTitle(sprintf(__('Clave Maestra %s'), Util::getAppInfo('appname')));
                $Message->addDescription(__('Se ha generado una nueva clave para el acceso a sysPass y se solicitará en el siguiente inicio.'));
                $Message->addDescription('');
                $Message->addDescription(sprintf(__('La nueva clave es: %s'), $tempMasterPass));
                $Message->addDescription('');
                $Message->addDescription(__('No olvide acceder lo antes posible para guardar los cambios.'));

                if ($tempMasterGroup !== 0) {
                    Email::sendEmailBatch($Message, UserUtil::getUserGroupEmail($tempMasterGroup));
                } else {
                    Email::sendEmailBatch($Message, UserUtil::getUsersEmail());
                }
            }

            $this->JsonResponse->setStatus(0);
        } else {
            $this->LogMessage->addDescription(__('Error al generar clave temporal', false));
        }

        Email::sendEmail($this->LogMessage);
    }

    /**
     * Acción para importar cuentas
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function importAction()
    {
        if (Checks::demoIsEnabled()) {
            $this->JsonResponse->setDescription(__('Ey, esto es una DEMO!!', false));
            return;
        }

        $ImportParams = new ImportParams();
        $ImportParams->setDefaultUser(Request::analyze('import_defaultuser', Session::getUserData()->getUserId()));
        $ImportParams->setDefaultGroup(Request::analyze('import_defaultgroup', Session::getUserData()->getUserGroupId()));
        $ImportParams->setImportPwd(Request::analyzeEncrypted('importPwd'));
        $ImportParams->setImportMasterPwd(Request::analyzeEncrypted('importMasterPwd'));
        $ImportParams->setCsvDelimiter(Request::analyze('csvDelimiter'));

        $Import = new Import($ImportParams);
        $LogMessage = $Import->doImport($_FILES['inFile']);

        $this->JsonResponse->setDescription($LogMessage->getHtmlDescription(true));
        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acción para exportar cuentas
     */
    protected function exportAction()
    {
        $exportPassword = Request::analyzeEncrypted('exportPwd');
        $exportPasswordR = Request::analyzeEncrypted('exportPwdR');

        if (!empty($exportPassword) && $exportPassword !== $exportPasswordR) {
            $this->JsonResponse->setDescription(__('Las claves no coinciden', false));
            return;
        }

        if (!XmlExport::doExport($exportPassword)) {
            $this->JsonResponse->setDescription(__('Error al realizar la exportación', false));
            $this->JsonResponse->addMessage(__('Revise el registro de eventos para más detalles', false));
            return;
        }

        $this->JsonResponse->setDescription(__('Proceso de exportación finalizado', false));
        $this->JsonResponse->setStatus(0);
    }

    /**
     * Acción para realizar el backup de sysPass
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     */
    protected function backupAction()
    {
        if (Checks::demoIsEnabled()) {
            $this->JsonResponse->setDescription(__('Ey, esto es una DEMO!!', false));
            return;
        }

        if (!Backup::doBackup()) {
            $this->JsonResponse->setDescription(__('Error al realizar el backup', false));
            $this->JsonResponse->addMessage(__('Revise el registro de eventos para más detalles', false));
            return;
        }

        $this->JsonResponse->setDescription(__('Proceso de backup finalizado', false));
        $this->JsonResponse->setStatus(0);
    }
}