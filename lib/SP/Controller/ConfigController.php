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
use SP\Core\SessionFactory;
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

        $this->configDB = ConfigDB::readConfig();

        $this->view->assign('tabs', []);
        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('isDemoMode', $this->configData->isDemoEnabled() && !$this->userData->isUserIsAdminApp());
        $this->view->assign('isDisabled', ($this->configData->isDemoEnabled() && !$this->userData->isUserIsAdminApp()) ? 'disabled' : '');
        $this->view->assign('ConfigData', $this->configData);
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

        $this->eventDispatcher->notifyEvent('show.config', $this);

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
        $this->view->assign('themesAvailable', $this->theme->getThemesAvailable());

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

        $this->view->assign('ldapIsAvailable', Checks::ldapIsAvailable());
        $this->view->assign('groups', Group::getItem()->getItemsForSelect());
        $this->view->assign('profiles', Profile::getItem()->getItemsForSelect());

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
        $this->view->assign('tempMasterPass', SessionFactory::getTemporaryMasterPass());
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
        $this->view->assign('isAdminApp', $this->userData->isUserIsAdminApp());

        $backupHash = $this->configData->getBackupHash();
        $exportHash = $this->configData->getExportHash();

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
        $this->view->assign('dbName', $this->configData->getDbName() . '@' . $this->configData->getDbHost());
        $this->view->assign('configBackupDate', date('r', $this->configDB['config_backupdate']));
        $this->view->assign('plugins', PluginUtil::getLoadedPlugins());
        $this->view->assign('locale', Language::$localeStatus ?: sprintf('%s (%s)', $this->configData->getSiteLang(), __('No instalado')));
        $this->view->assign('securedSession', CryptSessionHandler::$isSecured);

        $this->view->append('tabs', ['title' => __('Información')]);
        $this->view->assign('tabIndex', $this->getTabIndex(), 'info');
    }
}
