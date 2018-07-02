<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers;

use SP\Bootstrap;
use SP\Core\Acl\Acl;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Core\Events\Event;
use SP\Core\Language;
use SP\Core\Plugin\PluginUtil;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\TabsHelper;
use SP\Mvc\View\Components\DataTab;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Providers\Log\LogHandler;
use SP\Providers\Mail\MailHandler;
use SP\Services\Account\AccountService;
use SP\Services\Config\ConfigService;
use SP\Services\Crypt\TemporaryMasterPassService;
use SP\Services\Task\Task;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;
use SP\Storage\Database\DBUtil;
use SP\Storage\Database\MySQLHandler;
use SP\Util\Checks;
use SP\Util\Util;

/**
 * Class ConfigManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigManagerController extends ControllerBase
{
    /**
     * @var TabsHelper
     */
    protected $tabsHelper;

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Services\Config\ParameterNotFoundException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function indexAction()
    {
        $this->getTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Services\Config\ParameterNotFoundException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getTabs()
    {
        $this->tabsHelper = $this->dic->get(TabsHelper::class);

        if ($this->checkAccess(Acl::CONFIG_GENERAL)) {
            $this->tabsHelper->addTab($this->getConfigGeneral());
        }

        if ($this->checkAccess(Acl::ACCOUNT_CONFIG)) {
            $this->tabsHelper->addTab($this->getAccountConfig());
        }

        if ($this->checkAccess(Acl::WIKI_CONFIG)) {
            $this->tabsHelper->addTab($this->getWikiConfig());
        }

        if ($this->checkAccess(Acl::LDAP_CONFIG)) {
            $this->tabsHelper->addTab($this->getLdapConfig());
        }

        if ($this->checkAccess(Acl::MAIL_CONFIG)) {
            $this->tabsHelper->addTab($this->getMailConfig());
        }

        if ($this->checkAccess(Acl::ENCRYPTION_CONFIG)) {
            $this->tabsHelper->addTab($this->getEncryptionConfig());
        }

        if ($this->checkAccess(Acl::BACKUP_CONFIG)) {
            $this->tabsHelper->addTab($this->getBackupConfig());
        }

        if ($this->checkAccess(Acl::IMPORT_CONFIG)) {
            $this->tabsHelper->addTab($this->getImportConfig());
        }

        if ($this->checkAccess(Acl::CONFIG_GENERAL)) {
            $this->tabsHelper->addTab($this->getInfo());
        }


        $this->eventDispatcher->notifyEvent('show.config', new Event($this));

        $this->tabsHelper->renderTabs(Acl::getActionRoute(Acl::CONFIG), Request::analyzeInt('tabIndex', 0));

        $this->view();
    }

    /**
     * @return DataTab
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getConfigGeneral()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('general');

        $template->assign('langs', SelectItemAdapter::factory(Language::getAvailableLanguages())->getItemsFromArraySelected([$this->configData->getSiteLang()]));
        $template->assign('themes', SelectItemAdapter::factory($this->theme->getThemesAvailable())->getItemsFromArraySelected([$this->configData->getSiteTheme()]));
        $template->assign('isDemoMode', $this->configData->isDemoEnabled() && !$this->userData->getIsAdminApp());
        $template->assign('isDisabled', $this->configData->isDemoEnabled() && !$this->userData->getIsAdminApp() ? 'disabled' : '');

        $template->assign('users', SelectItemAdapter::factory(UserService::getItemsBasic())->getItemsFromModel());
        $template->assign('userGroups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel());
        $template->assign('userProfiles', SelectItemAdapter::factory(UserProfileService::getItemsBasic())->getItemsFromModel());

        $template->assign('logEvents', SelectItemAdapter::factory(LogHandler::EVENTS)
            ->getItemsFromArraySelected($this->configData->getLogEvents(), true)
        );

        return new DataTab(__('General'), $template);
    }

    /**
     * @return DataTab
     */
    protected function getAccountConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('accounts');

        return new DataTab(__('Cuentas'), $template);
    }

    /**
     * @return DataTab
     */
    protected function getWikiConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('wiki');

        return new DataTab(__('Wiki'), $template);
    }

    /**
     * @return DataTab
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getLdapConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('ldap');

        $template->assign('ldapIsAvailable', Checks::ldapIsAvailable());
        $template->assign('userGroups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel());
        $template->assign('userProfiles', SelectItemAdapter::factory(UserProfileService::getItemsBasic())->getItemsFromModel());

        return new DataTab(__('LDAP'), $template);
    }

    /**
     * @return DataTab
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getMailConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('mail');

        $template->assign('mailSecurity', ['SSL', 'TLS']);
        $template->assign('userGroups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel());
        $template->assign('userProfiles', SelectItemAdapter::factory(UserProfileService::getItemsBasic())->getItemsFromModel());
        $template->assign('mailEvents', SelectItemAdapter::factory(MailHandler::EVENTS)
            ->getItemsFromArraySelected($this->configData->getMailEvents(), true)
        );

        return new DataTab(__('Correo'), $template);
    }

    /**
     * @return DataTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\Config\ParameterNotFoundException
     */
    protected function getEncryptionConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('encryption');

        $numAccounts = $this->dic->get(AccountService::class)->getTotalNumAccounts();
        $template->assign('numAccounts', $numAccounts);

        if ($numAccounts > 500) {
            $template->assign('taskId', Task::genTaskId('masterpass'));
        }

        $configService = $this->dic->get(ConfigService::class);

        $template->assign('lastUpdateMPass', $configService->getByParam('lastupdatempass', 0));
        $template->assign('tempMasterPassTime', $configService->getByParam('tempmaster_passtime', 0));
        $template->assign('tempMasterMaxTime', $configService->getByParam('tempmaster_maxtime', 0));

        $tempMasterAttempts = sprintf('%d/%d', $configService->getByParam('tempmaster_attempts', 0), TemporaryMasterPassService::MAX_ATTEMPTS);

        $template->assign('tempMasterAttempts', $tempMasterAttempts);
        $template->assign('tempMasterPass', $this->session->getTemporaryMasterPass());

        $template->assign('userGroups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel());

        return new DataTab(__('Encriptación'), $template);
    }

    /**
     * @return DataTab
     */
    protected function getBackupConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('backup');

        $template->assign('siteName', Util::getAppInfo('appname'));
        $template->assign('backupDir', BACKUP_PATH);
        $template->assign('backupPath', Bootstrap::$WEBROOT . '/backup');

        $backupHash = $this->configData->getBackupHash();
        $exportHash = $this->configData->getExportHash();

        $backupFile = $template->siteName . '-' . $backupHash . '.tar.gz';

        $template->assign('backupFile', [
            'absolute' => BACKUP_PATH . DIRECTORY_SEPARATOR . $backupFile,
            'relative' => $template->backupPath . '/' . $backupFile,
            'filename' => $backupFile
        ]);

        $backupDbFile = $template->siteName . '_db-' . $backupHash . '.sql';

        $template->assign('backupDbFile', [
            'absolute' => BACKUP_PATH . DIRECTORY_SEPARATOR . $backupDbFile,
            'relative' => $template->backupPath . '/' . $backupDbFile,
            'filename' => $backupDbFile
        ]);

        clearstatcache(true, $template->backupFile['absolute']);
        clearstatcache(true, $template->backupDbFile['absolute']);

        $template->assign('lastBackupTime', file_exists($template->backupFile['absolute']) ? __('Último backup') . ': ' . date('r', filemtime($template->backupFile['absolute'])) : __('No se encontraron backups'));

        $exportFile = $template->siteName . '-' . $exportHash . '.xml';

        $template->assign('exportFile', [
            'absolute' => BACKUP_PATH . DIRECTORY_SEPARATOR . $exportFile,
            'relative' => $template->backupPath . '/' . $exportFile,
            'filename' => $exportFile
        ]);

        clearstatcache(true, $template->exportFile['absolute']);

        $template->assign('lastExportTime', file_exists($template->exportFile['absolute']) ? __('Última exportación') . ': ' . date('r', filemtime($template->exportFile['absolute'])) : __('No se encontró archivo de exportación'));

        return new DataTab(__('Copia de Seguridad'), $template);
    }

    /**
     * @return DataTab
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getImportConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('import');

        $template->assign('userGroups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModelSelected([$this->userData->getUserGroupId()]));
        $template->assign('users', SelectItemAdapter::factory(UserService::getItemsBasic())->getItemsFromModelSelected([$this->userData->getId()]));

        return new DataTab(__('Importar Cuentas'), $template);
    }

    /**
     * @return DataTab
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getInfo()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('info');

        $template->assign('dbInfo', DBUtil::getDBinfo($this->dic->get(MySQLHandler::class)));
        $template->assign('dbName', $this->configData->getDbName() . '@' . $this->configData->getDbHost());
        $template->assign('configBackupDate', date('r', $this->dic->get(ConfigService::class)->getByParam('config_backupdate', 0)));
        $template->assign('plugins', PluginUtil::getLoadedPlugins());
        $template->assign('locale', Language::$localeStatus ?: sprintf('%s (%s)', $this->configData->getSiteLang(), __('No instalado')));
        $template->assign('securedSession', CryptSessionHandler::$isSecured);

        return new DataTab(__('Información'), $template);
    }

    /**
     * @return TabsHelper
     */
    public function getTabsHelper()
    {
        return $this->tabsHelper;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Services\Auth\AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();
    }
}