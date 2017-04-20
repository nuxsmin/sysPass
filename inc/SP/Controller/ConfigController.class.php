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

defined('APP_ROOT') || die();

use SP\Account\AccountUtil;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Config\ConfigDB;
use SP\Core\ActionsInterface;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Core\CryptMasterPass;
use SP\Core\DiFactory;
use SP\Core\Init;
use SP\Core\Language;
use SP\Core\Plugin\PluginUtil;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Task;
use SP\Core\Template;
use SP\Mgmt\Groups\Group;
use SP\Mgmt\Profiles\Profile;
use SP\Mgmt\Users\User;
use SP\Storage\DBUtil;
use SP\Util\Checks;
use SP\Util\Util;

/**
 * Clase encargada de preparar la presentación de las opciones de configuración
 *
 * @package Controller
 */
class ConfigController extends ControllerBase implements ActionsInterface
{
    /**
     * @var int
     */
    private $tabIndex = 0;
    /**
     * @var ConfigData
     */
    private $Config;
    /**
     * @var array
     */
    private $configDB;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->Config = Config::getConfig();
        $this->configDB = ConfigDB::readConfig();

        $this->view->assign('tabs', []);
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('isDemoMode', Checks::demoIsEnabled() && !$this->UserData->isUserIsAdminApp());
        $this->view->assign('isDisabled', (Checks::demoIsEnabled() && !$this->UserData->isUserIsAdminApp()) ? 'disabled' : '');
    }

    /**
     * Realizar las accione del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public function doAction($type = null)
    {
        $this->view->addTemplate('tabs-start', 'common');

        $this->getGeneralTab();
        $this->getAccountsTab();
        $this->getWikiTab();
        $this->getLdapTab();
        $this->getMailTab();
        $this->getEncryptionTab();
        $this->getBackupTab();
        $this->getImportTab();
        $this->getInfoTab();

        $this->EventDispatcher->notifyEvent('show.config', $this);

        $this->view->addTemplate('tabs-end', 'common');
    }

    /**
     * Obtener la pestaña de configuración
     *
     * @return void
     */
    protected function getGeneralTab()
    {
        $this->setAction(self::ACTION_CFG_GENERAL);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('general');

        $this->view->assign('langsAvailable', Language::getAvailableLanguages());
        $this->view->assign('currentLang', $this->Config->getSiteLang());
        $this->view->assign('themesAvailable', DiFactory::getTheme()->getThemesAvailable());
        $this->view->assign('currentTheme', $this->Config->getSiteTheme());
        $this->view->assign('chkHttps', $this->Config->isHttpsEnabled() ? 'checked="checked"' : '');
        $this->view->assign('chkDebug', $this->Config->isDebug() ? 'checked="checked"' : '');
        $this->view->assign('chkMaintenance', $this->Config->isMaintenance() ? 'checked="checked"' : '');
        $this->view->assign('chkUpdates', $this->Config->isCheckUpdates() ? 'checked="checked"' : '');
        $this->view->assign('chkNotices', $this->Config->isChecknotices() ? 'checked="checked"' : '');
        $this->view->assign('chkEncryptSession', $this->Config->isEncryptSession() ? 'checked="checked"' : '');
        $this->view->assign('sessionTimeout', $this->Config->getSessionTimeout());

        // Events
        $this->view->assign('chkLog', $this->Config->isLogEnabled() ? 'checked="checked"' : '');
        $this->view->assign('chkSyslog', $this->Config->isSyslogEnabled() ? 'checked="checked"' : '');
        $this->view->assign('chkRemoteSyslog', $this->Config->isSyslogRemoteEnabled() ? 'checked="checked"' : '');
        $this->view->assign('remoteSyslogServer', $this->Config->getSyslogServer());
        $this->view->assign('remoteSyslogPort', $this->Config->getSyslogPort());

        // Proxy
        $this->view->assign('chkProxy', $this->Config->isProxyEnabled() ? 'checked="checked"' : '');
        $this->view->assign('proxyServer', $this->Config->getProxyServer());
        $this->view->assign('proxyPort', $this->Config->getProxyPort());
        $this->view->assign('proxyUser', $this->Config->getProxyUser());
        $this->view->assign('proxyPass', $this->Config->getProxyPass());

        $this->view->assign('actionId', $this->getAction(), 'config');
        $this->view->append('tabs', ['title' => __('General')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'config');
    }

    /**
     * Obtener el índice actual de las pestañas
     *
     * @return int
     */
    private function getTabIndex()
    {
        $index = $this->tabIndex;
        $this->tabIndex++;

        return $index;
    }

    /**
     * Obtener la pestaña de cuentas
     */
    protected function getAccountsTab()
    {
        $this->setAction(self::ACTION_CFG_ACCOUNTS);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('accounts');

        // Files
        $this->view->assign('chkFiles', $this->Config->isFilesEnabled() ? 'checked="checked"' : '');
        $this->view->assign('filesAllowedExts', implode(',', $this->Config->getFilesAllowedExts()));
        $this->view->assign('filesAllowedSize', $this->Config->getFilesAllowedSize());

        // Accounts
        $this->view->assign('chkGlobalSearch', $this->Config->isGlobalSearch() ? 'checked="checked"' : '');
        $this->view->assign('chkResultsAsCards', $this->Config->isResultsAsCards() ? 'checked="checked"' : '');
        $this->view->assign('chkAccountPassToImage', $this->Config->isAccountPassToImage() ? 'checked="checked"' : '');
        $this->view->assign('chkAccountLink', $this->Config->isAccountLink() ? 'checked="checked"' : '');
        $this->view->assign('accountCount', $this->Config->getAccountCount());
        $this->view->assign('chkAccountFullGroupAccess', $this->Config->isAccountFullGroupAccess() ? 'checked="checked"' : '');

        // PublicLinks
        $this->view->assign('chkPubLinks', $this->Config->isPublinksEnabled() ? 'checked="checked"' : '');
        $this->view->assign('chkPubLinksImage', $this->Config->isPublinksImageEnabled() ? 'checked="checked"' : '');
        $this->view->assign('pubLinksMaxTime', $this->Config->getPublinksMaxTime() / 60);
        $this->view->assign('pubLinksMaxViews', $this->Config->getPublinksMaxViews());

        $this->view->assign('actionId', $this->getAction(), 'accounts');
        $this->view->append('tabs', ['title' => __('Cuentas')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'accounts');
    }

    /**
     * Obtener la pestaña de Wiki
     *
     * @return void
     */
    protected function getWikiTab()
    {
        $this->setAction(self::ACTION_CFG_WIKI);

        if (!$this->checkAccess(self::ACTION_CFG_GENERAL)) {
            return;
        }

        $this->view->addTemplate('wiki');

        $this->view->assign('chkWiki', $this->Config->isWikiEnabled() ? 'checked="checked"' : '');
        $this->view->assign('wikiSearchUrl', $this->Config->getWikiSearchurl());
        $this->view->assign('wikiPageUrl', $this->Config->getWikiPageurl());
        $this->view->assign('wikiFilter', implode(',', $this->Config->getWikiFilter()));

        $this->view->assign('chkDokuWiki', $this->Config->isDokuwikiEnabled() ? 'checked="checked"' : '');
        $this->view->assign('dokuWikiUrl', $this->Config->getDokuwikiUrl());
        $this->view->assign('dokuWikiUrlBase', $this->Config->getDokuwikiUrlBase());
        $this->view->assign('dokuWikiUser', $this->Config->getDokuwikiUser());
        $this->view->assign('dokuWikiPass', $this->Config->getDokuwikiPass());
        $this->view->assign('dokuWikiNamespace', $this->Config->getDokuwikiNamespace());

        $this->view->assign('actionId', $this->getAction(), 'wiki');
        $this->view->append('tabs', ['title' => __('Wiki')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'wiki');
    }

    /**
     * Obtener la pestaña de LDAP
     *
     * @return void
     */
    protected function getLdapTab()
    {
        $this->setAction(self::ACTION_CFG_LDAP);

        if (!$this->checkAccess(self::ACTION_CFG_GENERAL)) {
            return;
        }

        $this->view->addTemplate('ldap');

        $this->view->assign('chkLdap', $this->Config->isLdapEnabled() ? 'checked="checked"' : '');
        $this->view->assign('chkLdapADS', $this->Config->isLdapAds() ? 'checked="checked"' : '');
        $this->view->assign('ldapIsAvailable', Checks::ldapIsAvailable());
        $this->view->assign('ldapServer', $this->Config->getLdapServer());
        $this->view->assign('ldapBindUser', $this->Config->getLdapBindUser());
        $this->view->assign('ldapBindPass', $this->Config->getLdapBindPass());
        $this->view->assign('ldapBase', $this->Config->getLdapBase());
        $this->view->assign('ldapGroup', $this->Config->getLdapGroup());
        $this->view->assign('groups', Group::getItem()->getItemsForSelect());
        $this->view->assign('profiles', Profile::getItem()->getItemsForSelect());
        $this->view->assign('ldapDefaultGroup', $this->Config->getLdapDefaultGroup());
        $this->view->assign('ldapDefaultProfile', $this->Config->getLdapDefaultProfile());

        $this->view->assign('actionId', $this->getAction(), 'ldap');
        $this->view->append('tabs', ['title' => __('LDAP')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'ldap');
    }

    /**
     * Obtener la pestaña de Correo
     *
     * @return void
     */
    protected function getMailTab()
    {
        $this->setAction(self::ACTION_CFG_MAIL);

        if (!$this->checkAccess(self::ACTION_CFG_GENERAL)) {
            return;
        }

        $this->view->addTemplate('mail');

        $this->view->assign('chkMail', $this->Config->isMailEnabled() ? 'checked="checked"' : '');
        $this->view->assign('chkMailRequests', $this->Config->isMailRequestsEnabled() ? 'checked="checked"' : '');
        $this->view->assign('chkMailAuth', $this->Config->isMailAuthenabled() ? 'checked="checked"' : '');
        $this->view->assign('mailServer', $this->Config->getMailServer());
        $this->view->assign('mailPort', $this->Config->getMailPort());
        $this->view->assign('mailUser', $this->Config->getMailUser());
        $this->view->assign('mailPass', $this->Config->getMailPass());
        $this->view->assign('currentMailSecurity', $this->Config->getMailSecurity());
        $this->view->assign('mailFrom', $this->Config->getMailFrom());
        $this->view->assign('mailSecurity', ['SSL', 'TLS']);

        $this->view->assign('actionId', $this->getAction(), 'mail');
        $this->view->append('tabs', ['title' => __('Correo')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'mail');
    }

    /**
     * Obtener la pestaña de encriptación
     *
     * @return void
     */
    protected function getEncryptionTab()
    {
        $this->setAction(self::ACTION_CFG_ENCRYPTION);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('encryption');

        $this->view->assign('numAccounts', AccountUtil::getTotalNumAccounts());
        $this->view->assign('taskId', Task::genTaskId('masterpass'));

        $this->view->assign('lastUpdateMPass', isset($this->configDB['lastupdatempass']) ? $this->configDB['lastupdatempass'] : 0);
        $this->view->assign('tempMasterPassTime', isset($this->configDB['tempmaster_passtime']) ? $this->configDB['tempmaster_passtime'] : 0);
        $this->view->assign('tempMasterMaxTime', isset($this->configDB['tempmaster_maxtime']) ? $this->configDB['tempmaster_maxtime'] : 0);
        $this->view->assign('tempMasterAttempts', isset($this->configDB['tempmaster_attempts']) ? sprintf('%d/%d', $this->configDB['tempmaster_attempts'], CryptMasterPass::MAX_ATTEMPTS) : 0);
        $this->view->assign('tempMasterPass', Session::getTemporaryMasterPass());
        $this->view->assign('groups', Group::getItem()->getItemsForSelect());

        $this->view->append('tabs', ['title' => __('Encriptación')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'encryption');
    }

    /**
     * Obtener la pestaña de copia de seguridad
     *
     * @return void
     */
    protected function getBackupTab()
    {
        $this->setAction(self::ACTION_CFG_BACKUP);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('backup');

        $this->view->assign('siteName', Util::getAppInfo('appname'));
        $this->view->assign('backupDir', Init::$SERVERROOT . '/backup');
        $this->view->assign('backupPath', Init::$WEBROOT . '/backup');
        $this->view->assign('isAdminApp', $this->UserData->isUserIsAdminApp());

        $backupHash = $this->Config->getBackupHash();
        $exportHash = $this->Config->getExportHash();

        $backupFile = $this->view->siteName . '-' . $backupHash . '.tar.gz';

        $this->view->assign('backupFile',
            ['absolute' => $this->view->backupDir . DIRECTORY_SEPARATOR . $backupFile,
                'relative' => $this->view->backupPath . '/' . $backupFile,
                'filename' => $backupFile]
        );

        $backupDbFile = $this->view->siteName . '_db-' . $backupHash . '.sql';

        $this->view->assign('backupDbFile',
            ['absolute' => $this->view->backupDir . DIRECTORY_SEPARATOR . $backupDbFile,
                'relative' => $this->view->backupPath . '/' . $backupDbFile,
                'filename' => $backupDbFile]
        );

        clearstatcache(true, $this->view->backupFile['absolute']);
        clearstatcache(true, $this->view->backupDbFile['absolute']);
        $this->view->assign('lastBackupTime', file_exists($this->view->backupFile['absolute']) ? __('Último backup') . ': ' . date('r', filemtime($this->view->backupFile['absolute'])) : __('No se encontraron backups'));

        $exportFile = $this->view->siteName . '-' . $exportHash . '.xml';

        $this->view->assign('exportFile',
            ['absolute' => $this->view->backupDir . DIRECTORY_SEPARATOR . $exportFile,
                'relative' => $this->view->backupPath . '/' . $exportFile,
                'filename' => $exportFile]
        );

        clearstatcache(true, $this->view->exportFile['absolute']);
        $this->view->assign('lastExportTime', file_exists($this->view->exportFile['absolute']) ? __('Última exportación') . ': ' . date('r', filemtime($this->view->exportFile['absolute'])) : __('No se encontró archivo de exportación'));

        $this->view->append('tabs', ['title' => __('Copia de Seguridad')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'backup');
    }

    /**
     * Obtener la pestaña de Importación
     *
     * @return void
     */
    protected function getImportTab()
    {
        $this->setAction(self::ACTION_CFG_IMPORT);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('import');

        $this->view->assign('groups', Group::getItem()->getItemsForSelect());
        $this->view->assign('users', User::getItem()->getItemsForSelect());

        $this->view->append('tabs', ['title' => __('Importar Cuentas')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'import');
    }

    /**
     * Obtener la pestaña de información
     *
     * @return void
     */
    protected function getInfoTab()
    {
        $this->setAction(self::ACTION_CFG_GENERAL);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('info');

        $this->view->assign('dbInfo', DBUtil::getDBinfo());
        $this->view->assign('dbName', $this->Config->getDbName() . '@' . $this->Config->getDbHost());
        $this->view->assign('configBackupDate', date('r', $this->configDB['config_backupdate']));
        $this->view->assign('plugins', PluginUtil::getLoadedPlugins());
        $this->view->assign('locale', Language::$localeStatus ?: sprintf('%s (%s)', Config::getConfig()->getSiteLang(), __('No instalado')));
        $this->view->assign('securedSession', CryptSessionHandler::$isSecured);

        $this->view->append('tabs', ['title' => __('Información')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'info');
    }
}
