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

namespace Controller;

use SP_Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de preparar la presentación de las opciones de configuración
 *
 * @package Controller
 */
class ConfigC extends \SP_Controller implements ActionsInterface
{
    private $_tabIndex = 0;
    public $activeTab = 0;

    /**
     * Constructor
     *
     * @param $template \SP_Template con instancia de plantilla
     */
    public function __construct(\SP_Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('tabs', array());
        $this->view->assign('sk', \SP_Common::getSessionKey(true));
    }

    /**
     * Obtener la pestaña de configuración
     *
     * @return bool
     */
    public function getConfigTab()
    {
        $this->setAction(self::ACTION_CFG_GENERAL);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('config');

        $this->view->assign('arrLangAvailable',
            array('Español' => 'es_ES',
                'English' => 'en_US',
                'Deutsch' => 'de_DE',
                'Magyar' => 'hu_HU',
                'Français' => 'fr_FR')
        );
        $this->view->assign('arrAccountCount', array(6, 9, 12, 15, 21, 27, 30, 51, 99));
        $this->view->assign('isDemoMode', \SP_Util::demoIsEnabled());
        $this->view->assign('isDisabled', (\SP_Util::demoIsEnabled()) ? 'DISABLED' : '');
        $this->view->assign('chkLog', (\SP_Config::getValue('log_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkDebug', (\SP_Config::getValue('debug')) ? 'checked="checked"' : '');
        $this->view->assign('chkMaintenance', (\SP_Config::getValue('maintenance')) ? 'checked="checked"' : '');
        $this->view->assign('chkUpdates', (\SP_Config::getValue('checkupdates')) ? 'checked="checked"' : '');
        $this->view->assign('chkGlobalSearch', (\SP_Config::getValue('globalsearch')) ? 'checked="checked"' : '');
        $this->view->assign('chkAccountLink', (\SP_Config::getValue('account_link')) ? 'checked="checked"' : '');
        $this->view->assign('chkFiles', (\SP_Config::getValue('files_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkWiki', (\SP_Config::getValue('wiki_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkLdap', (\SP_Config::getValue('ldap_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkLdapADS', (\SP_Config::getValue('ldap_ads')) ? 'checked="checked"' : '');
        $this->view->assign('chkMail', (\SP_Config::getValue('mail_enabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkMailRequests', (\SP_Config::getValue('mail_requestsenabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkMailAuth', (\SP_Config::getValue('mail_authenabled')) ? 'checked="checked"' : '');
        $this->view->assign('chkResultsAsCards', (\SP_Config::getValue('resultsascards')) ? 'checked="checked"' : '');

        $this->view->assign('filesAllowedExts', \SP_Config::getValue('files_allowed_exts'));
        $this->view->assign('filesAllowedSize', \SP_Config::getValue('files_allowed_size'));
        $this->view->assign('groupsSelData', \DB::getValuesForSelect('usrGroups', 'usergroup_id', 'usergroup_name'));
        $this->view->assign('groupsSelProp',
            array('name' => 'ldap_defaultgroup',
                'id' => 'ldap_defaultgroup',
                'class' => '',
                'size' => 1,
                'label' => '',
                'selected' => \SP_Config::getValue('ldap_defaultgroup'),
                'default' => '',
                'js' => '',
                'attribs' => array('required', $this->view->isDisabled))
        );
        $this->view->assign('profilesSelData', \DB::getValuesForSelect('usrProfiles', 'userprofile_id', 'userprofile_name'));
        $this->view->assign('profilesSelProp',
            array('name' => 'ldap_defaultprofile',
                'id' => 'ldap_defaultprofile',
                'class' => '',
                'size' => 1,
                'label' => '',
                'selected' => \SP_Config::getValue('ldap_defaultprofile'),
                'default' => '',
                'js' => '',
                'attribs' => array('required', $this->view->isDisabled))
        );
        $this->view->assign('currentLang', \SP_Config::getValue('sitelang'));
        $this->view->assign('sessionTimeout', \SP_Config::getValue('session_timeout'));
        $this->view->assign('accountCount', \SP_Config::getValue('account_count'));
        $this->view->assign('wikiSearchUrl', \SP_Config::getValue('wiki_searchurl'));
        $this->view->assign('wikiPageUrl', \SP_Config::getValue('wiki_pageurl'));
        $this->view->assign('wikiFilter', \SP_Config::getValue('wiki_filter'));
        $this->view->assign('ldapIsAvailable', \SP_Util::ldapIsAvailable());
        $this->view->assign('ldapServer', \SP_Config::getValue('ldap_server'));
        $this->view->assign('ldapBindUser', \SP_Config::getValue('ldap_binduser'));
        $this->view->assign('ldapBindPass', \SP_Config::getValue('ldap_bindpass'));
        $this->view->assign('ldapBase', \SP_Config::getValue('ldap_base'));
        $this->view->assign('ldapGroup', \SP_Config::getValue('ldap_group'));
        $this->view->assign('currentMailSecurity', \SP_Config::getValue('mail_security'));
        $this->view->assign('mailFrom', \SP_Config::getValue('mail_from'));
        $this->view->assign('mailSecurity', array('SSL', 'TLS'));
        $this->view->append('tabs', array('title' => _('Configuración')));
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

        $this->view->assign('lastUpdateMPass', \SP_Config::getConfigDbValue("lastupdatempass"));
        $this->view->assign('tempMasterPassTime', \SP_Config::getConfigDbValue("tempmaster_passtime"));
        $this->view->assign('tempMasterMaxTime', \SP_Config::getConfigDbValue("tempmaster_maxtime"));
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

        $this->view->assign('siteName', SP_Util::getAppInfo('appname'));
        $this->view->assign('backupDir', \SP_Init::$SERVERROOT . '/backup');
        $this->view->assign('backupPath', \SP_Init::$WEBROOT . '/backup');

        $this->view->assign('backupFile',
            array('absolute' => $this->view->backupDir . DIRECTORY_SEPARATOR . $this->view->siteName . '.tar.gz',
                'relative' => $this->view->backupPath . '/' . $this->view->siteName . '.tar.gz')
        );
        $this->view->assign('backupDbFile',
            array('absolute' => $this->view->backupDir . DIRECTORY_SEPARATOR . $this->view->siteName . '_db.sql',
                'relative' => $this->view->backupPath . '/' . $this->view->siteName . '_db.sql')
        );
        $this->view->assign('lastBackupTime', (file_exists($this->view->backupFile['absolute'])) ? _('Último backup') . ": " . date("r", filemtime($this->view->backupFile['absolute'])) : _('No se encontraron backups'));

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

        $this->view->assign('dbInfo', \DB::getDBinfo());
        $this->view->append('tabs', array('title' => _('Información')));
        $this->view->assign('tabIndex', $this->getTabIndex(), 'info');
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
