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

namespace SP\Controller;

use SP\Config;
use SP\ConfigDB;
use SP\CryptMasterPass;
use SP\Language;
use SP\Session;
use SP\SessionUtil;
use SP\Themes;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de preparar la presentación de las opciones de configuración
 *
 * @package Controller
 */
class ConfigC extends Controller implements ActionsInterface
{
    private $_tabIndex = 0;

    /**
     * Constructor
     *
     * @param $template \SP\Template con instancia de plantilla
     */
    public function __construct(\SP\Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('tabs', array());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('isDemoMode', (\SP\Util::demoIsEnabled() && !Session::getUserIsAdminApp()));
        $this->view->assign('isDisabled', (\SP\Util::demoIsEnabled() && !Session::getUserIsAdminApp()) ? 'DISABLED' : '');
    }

    /**
     * Obtener la pestaña de configuración
     *
     * @return bool
     */
    public function getGeneralTab()
    {
        $this->setAction(self::ACTION_CFG_GENERAL);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('config');

        $this->view->assign('langsAvailable',Language::getAvailableLanguages());
        $this->view->assign('currentLang', \SP\Config::getValue('sitelang'));
        $this->view->assign('themesAvailable', Themes::getThemesAvailable());
        $this->view->assign('currentTheme', \SP\Config::getValue('sitetheme'));
        $this->view->assign('chkHttps', (\SP\Config::getValue('https_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkFail2ban', (\SP\Config::getValue('fail2ban_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkLog', (\SP\Config::getValue('log_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkDebug', (\SP\Config::getValue('debug')) ? 'checked="checked"' : '');
        $this->view->assign('chkMaintenance', (\SP\Config::getValue('maintenance')) ? 'checked="checked"' : '');
        $this->view->assign('chkUpdates', (\SP\Config::getValue('checkupdates')) ? 'checked="checked"' : '');
        $this->view->assign('chkNotices', (\SP\Config::getValue('checknotices')) ? 'checked="checked"' : '');
        $this->view->assign('sessionTimeout', \SP\Config::getValue('session_timeout'));

        // Files
        $this->view->assign('chkFiles', (\SP\Config::getValue('files_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('filesAllowedExts', \SP\Config::getValue('files_allowed_exts'));
        $this->view->assign('filesAllowedSize', \SP\Config::getValue('files_allowed_size'));

        // Accounts
        $this->view->assign('chkGlobalSearch', (\SP\Config::getValue('globalsearch')) ? 'checked="checked"' : '');
        $this->view->assign('chkResultsAsCards', (\SP\Config::getValue('resultsascards')) ? 'checked="checked"' : '');
        $this->view->assign('chkAccountPassToImage', (\SP\Config::getValue('account_passtoimage')) ? 'checked="checked"' : '');
        $this->view->assign('chkAccountLink', (\SP\Config::getValue('account_link')) ? 'checked="checked"' : '');
        $this->view->assign('accountCount', \SP\Config::getValue('account_count'));

        // Proxy
        $this->view->assign('chkProxy', (\SP\Config::getValue('proxy_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('proxyServer', \SP\Config::getValue('proxy_server'));
        $this->view->assign('proxyPort', \SP\Config::getValue('proxy_port'));
        $this->view->assign('proxyUser', \SP\Config::getValue('proxy_user'));
        $this->view->assign('proxyPass', \SP\Config::getValue('proxy_pass'));

        $this->view->assign('actionId', $this->getAction(), 'config');
        $this->view->append('tabs', array('title' => _('General')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'config');
    }

    /**
     * Obtener la pestaña de encriptación
     *
     * @return bool
     */
    public function getEncryptionTab()
    {
        $this->setAction(self::ACTION_CFG_ENCRYPTION);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('encryption');

        $this->view->assign('lastUpdateMPass', ConfigDB::getValue("lastupdatempass"));
        $this->view->assign('tempMasterPassTime', ConfigDB::getValue("tempmaster_passtime"));
        $this->view->assign('tempMasterMaxTime', ConfigDB::getValue("tempmaster_maxtime"));
        $this->view->assign('tempMasterPass', Session::getTemporaryMasterPass());
        $this->view->assign('tempMasterAttempts', ConfigDB::getValue('tempmaster_attempts', 0) . '/' . CryptMasterPass::MAX_ATTEMPTS);

        $this->view->append('tabs', array('title' => _('Encriptación')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'encryption');
    }

    /**
     * Obtener la pestaña de copia de seguridad
     *
     * @return bool
     */
    public function getBackupTab()
    {
        $this->setAction(self::ACTION_CFG_BACKUP);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('backup');

        $this->view->assign('siteName', \SP\Util::getAppInfo('appname'));
        $this->view->assign('backupDir', \SP\Init::$SERVERROOT . '/backup');
        $this->view->assign('backupPath', \SP\Init::$WEBROOT . '/backup');

        $backupHash =  Config::getValue('backup_hash');
        $exportHash =  Config::getValue('export_hash');

        $this->view->assign('backupFile',
            array('absolute' => $this->view->backupDir . DIRECTORY_SEPARATOR . $this->view->siteName  . '-' . $backupHash . '.tar.gz',
                'relative' => $this->view->backupPath . '/' . $this->view->siteName . '-' . $backupHash . '.tar.gz',
                'filename' => $this->view->siteName . '-' . $backupHash . '.tar.gz')
        );
        $this->view->assign('backupDbFile',
            array('absolute' => $this->view->backupDir . DIRECTORY_SEPARATOR . $this->view->siteName . '_db-' . $backupHash . '.sql',
                'relative' => $this->view->backupPath . '/' . $this->view->siteName . '_db-' . $backupHash . '.sql',
                'filename' => $this->view->siteName . '_db-' . $backupHash . '.sql')
        );
        $this->view->assign('lastBackupTime', (file_exists($this->view->backupFile['absolute'])) ? _('Último backup') . ": " . date("r", filemtime($this->view->backupFile['absolute'])) : _('No se encontraron backups'));

        $this->view->assign('exportFile',
            array('absolute' => $this->view->backupDir . DIRECTORY_SEPARATOR . $this->view->siteName . '-' . $exportHash . '.xml',
                'relative' => $this->view->backupPath . '/' . $this->view->siteName . '-' . $exportHash . '.xml',
                'filename' => $this->view->siteName . '-' . $exportHash . '.xml')
        );
        $this->view->assign('lastExportTime', (file_exists($this->view->exportFile['absolute'])) ? _('Última exportación') . ': ' . date("r", filemtime($this->view->exportFile['absolute'])) : _('No se encontró archivo de exportación'));

        $this->view->append('tabs', array('title' => _('Copia de Seguridad')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'backup');
    }

    /**
     * Obtener la pestaña de Importación
     *
     * @return bool
     */
    public function getImportTab()
    {
        $this->setAction(self::ACTION_CFG_IMPORT);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('import');

        $this->view->assign('groups', \SP\DB::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'));
        $this->view->assign('users', \SP\DB::getValuesForSelect('usrData', 'user_id', 'user_name'));

        $this->view->append('tabs', array('title' => _('Importar Cuentas')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'import');
    }

    /**
     * Obtener la pestaña de información
     * @return bool
     */
    public function getInfoTab()
    {
        $this->setAction(self::ACTION_CFG_GENERAL);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('info');

        $this->view->assign('dbInfo', \SP\DB::getDBinfo());
        $this->view->assign('dbName', \SP\Config::getValue('dbname') . '@' . \SP\Config::getValue('dbhost'));

        $this->view->append('tabs', array('title' => _('Información')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'info');
    }

    /**
     * Obtener la pestaña de Wiki
     * @return bool
     */
    public function getWikiTab()
    {
        $this->setAction(self::ACTION_CFG_WIKI);

        if (!$this->checkAccess(self::ACTION_CFG_GENERAL)) {
            return;
        }

        $this->view->addTemplate('wiki');

        $this->view->assign('chkWiki', (\SP\Config::getValue('wiki_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('wikiSearchUrl', \SP\Config::getValue('wiki_searchurl'));
        $this->view->assign('wikiPageUrl', \SP\Config::getValue('wiki_pageurl'));
        $this->view->assign('wikiFilter', \SP\Config::getValue('wiki_filter'));

        $this->view->assign('actionId', $this->getAction(), 'wiki');
        $this->view->append('tabs', array('title' => _('Wiki')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'wiki');
    }

    /**
     * Obtener la pestaña de LDAP
     * @return bool
     */
    public function getLdapTab()
    {
        $this->setAction(self::ACTION_CFG_LDAP);

        if (!$this->checkAccess(self::ACTION_CFG_GENERAL)) {
            return;
        }

        $this->view->addTemplate('ldap');

        $this->view->assign('chkLdap', (\SP\Config::getValue('ldap_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkLdapADS', (\SP\Config::getValue('ldap_ads')) ? 'checked="checked"' : '');
        $this->view->assign('ldapIsAvailable', \SP\Util::ldapIsAvailable());
        $this->view->assign('ldapServer', \SP\Config::getValue('ldap_server'));
        $this->view->assign('ldapBindUser', \SP\Config::getValue('ldap_binduser'));
        $this->view->assign('ldapBindPass', \SP\Config::getValue('ldap_bindpass'));
        $this->view->assign('ldapBase', \SP\Config::getValue('ldap_base'));
        $this->view->assign('ldapGroup', \SP\Config::getValue('ldap_group'));
        $this->view->assign('groups', \SP\DB::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'));
        $this->view->assign('profiles', \SP\DB::getValuesForSelect('usrProfiles', 'userprofile_id', 'userprofile_name'));
        $this->view->assign('ldapDefaultGroup', \SP\Config::getValue('ldap_defaultgroup'));
        $this->view->assign('ldapDefaultProfile', \SP\Config::getValue('ldap_defaultprofile'));

        $this->view->assign('actionId', $this->getAction(), 'ldap');
        $this->view->append('tabs', array('title' => _('LDAP')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'ldap');
    }

    /**
     * Obtener la pestaña de Correo
     * @return bool
     */
    public function getMailTab()
    {
        $this->setAction(self::ACTION_CFG_MAIL);

        if (!$this->checkAccess(self::ACTION_CFG_GENERAL)) {
            return;
        }

        $this->view->addTemplate('mail');

        $this->view->assign('chkMail', (\SP\Config::getValue('mail_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkMailRequests', (\SP\Config::getValue('mail_requestsenabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkMailAuth', (\SP\Config::getValue('mail_authenabled')) ? 'checked="checked"' : '');
        $this->view->assign('mailServer', \SP\Config::getValue('mail_server','localhost'));
        $this->view->assign('mailPort', \SP\Config::getValue('mail_port',25));
        $this->view->assign('mailUser', \SP\Config::getValue('mail_user'));
        $this->view->assign('mailPass', \SP\Config::getValue('mail_pass'));
        $this->view->assign('currentMailSecurity', \SP\Config::getValue('mail_security'));
        $this->view->assign('mailFrom', \SP\Config::getValue('mail_from'));
        $this->view->assign('mailSecurity', array('SSL', 'TLS'));

        $this->view->assign('actionId', $this->getAction(), 'mail');
        $this->view->append('tabs', array('title' => _('Correo')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'mail');
    }

    /**
     * Obtener el índice actual de las pestañas
     *
     * @return int
     */
    private function getTabIndex(){
        $index = $this->_tabIndex;
        $this->_tabIndex++;

        return $index;
    }
}
