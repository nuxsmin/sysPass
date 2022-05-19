<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\AppInfoInterface;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Core\Events\Event;
use SP\Core\Exceptions\CheckException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Core\MimeTypes;
use SP\Modules\Web\Controllers\Helpers\TabsHelper;
use SP\Mvc\View\Components\DataTab;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Plugin\PluginManager;
use SP\Providers\Auth\Ldap\LdapMsAds;
use SP\Providers\Auth\Ldap\LdapStd;
use SP\Providers\Auth\Ldap\LdapTypeInterface;
use SP\Providers\Log\LogInterface;
use SP\Providers\Mail\MailHandler;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountService;
use SP\Services\Auth\AuthException;
use SP\Services\Backup\BackupFiles;
use SP\Services\Config\ConfigService;
use SP\Services\Crypt\TemporaryMasterPassService;
use SP\Services\Export\XmlExportService;
use SP\Services\ServiceException;
use SP\Services\Task\Task;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;
use SP\Storage\Database\DatabaseUtil;
use SP\Storage\File\FileException;
use SP\Storage\File\FileHandler;
use SP\Util\Util;

/**
 * Class ConfigManagerController
 */
final class ConfigManagerController extends ControllerBase
{
    protected ?TabsHelper $tabsHelper = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SPException
     */
    public function indexAction(): void
    {
        $this->getTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws SPException
     */
    protected function getTabs(): void
    {
        $this->tabsHelper = $this->dic->get(TabsHelper::class);

        if ($this->checkAccess(ActionsInterface::CONFIG_GENERAL)) {
            $this->tabsHelper->addTab($this->getConfigGeneral());
        }

        if ($this->checkAccess(ActionsInterface::CONFIG_ACCOUNT)) {
            $this->tabsHelper->addTab($this->getAccountConfig());
        }

        if ($this->checkAccess(ActionsInterface::CONFIG_WIKI)) {
            $this->tabsHelper->addTab($this->getWikiConfig());
        }

        if ($this->checkAccess(ActionsInterface::CONFIG_LDAP)) {
            $this->tabsHelper->addTab($this->getLdapConfig());
        }

        if ($this->checkAccess(ActionsInterface::CONFIG_MAIL)) {
            $this->tabsHelper->addTab($this->getMailConfig());
        }

        if ($this->checkAccess(ActionsInterface::CONFIG_CRYPT)) {
            $this->tabsHelper->addTab($this->getEncryptionConfig());
        }

        if ($this->checkAccess(ActionsInterface::CONFIG_BACKUP)) {
            $this->tabsHelper->addTab($this->getBackupConfig());
        }

        if ($this->checkAccess(ActionsInterface::CONFIG_IMPORT)) {
            $this->tabsHelper->addTab($this->getImportConfig());
        }

        if ($this->checkAccess(ActionsInterface::CONFIG_GENERAL)) {
            $this->tabsHelper->addTab($this->getInfo());
        }


        $this->eventDispatcher->notifyEvent(
            'show.config',
            new Event($this)
        );

        $this->tabsHelper->renderTabs(
            Acl::getActionRoute(ActionsInterface::CONFIG),
            $this->request->analyzeInt('tabIndex', 0)
        );

        $this->view();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws CheckException
     */
    protected function getConfigGeneral(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('general');

        $template->assign('langs',
            SelectItemAdapter::factory(
                Language::getAvailableLanguages()
            )->getItemsFromArraySelected([$this->configData->getSiteLang()])
        );
        $template->assign('themes',
            SelectItemAdapter::factory(
                $this->theme->getThemesAvailable()
            )->getItemsFromArraySelected([$this->configData->getSiteTheme()])
        );
        $template->assign('isDemoMode',
            $this->configData->isDemoEnabled()
            && !$this->userData->getIsAdminApp()
        );
        $template->assign('isDisabled',
            $this->configData->isDemoEnabled()
            && !$this->userData->getIsAdminApp() ? 'disabled' : ''
        );
        $template->assign('users',
            SelectItemAdapter::factory(
                UserService::getItemsBasic()
            )->getItemsFromModel()
        );
        $template->assign('userGroups',
            SelectItemAdapter::factory(
                UserGroupService::getItemsBasic()
            )->getItemsFromModel()
        );
        $template->assign('userProfiles',
            SelectItemAdapter::factory(
                UserProfileService::getItemsBasic()
            )->getItemsFromModel()
        );

        $template->assign('curlIsAvailable',
            $this->extensionChecker->checkCurlAvailable());

        $events = array_merge(
            LogInterface::EVENTS,
            $this->configData->getLogEvents()
        );

        sort($events, SORT_STRING);

        $template->assign('logEvents',
            SelectItemAdapter::factory($events)
                ->getItemsFromArraySelected(
                    $this->configData->getLogEvents(),
                    true
                )
        );

        return new DataTab(__('General'), $template);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws CheckException
     * @throws SPException
     */
    protected function getAccountConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('accounts');
        $template->assign(
            'gdIsAvailable',
            $this->extensionChecker->checkGdAvailable()
        );

        $mimeTypesAvailable = array_map(
            static function ($value) {
                return $value['type'];
            },
            $this->dic->get(MimeTypes::class)->getMimeTypes()
        );

        $mimeTypes = SelectItemAdapter::factory(
            array_merge(
                $mimeTypesAvailable,
                $this->configData->getFilesAllowedMime()
            )
        );

        $template->assign(
            'mimeTypes',
            $mimeTypes->getItemsFromArraySelected(
                $this->configData->getFilesAllowedMime(),
                true
            )
        );

        return new DataTab(__('Accounts'), $template);
    }

    /**
     * @return DataTab
     * @throws CheckException
     */
    protected function getWikiConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('wiki');

        $template->assign(
            'curlIsAvailable',
            $this->extensionChecker->checkCurlAvailable()
        );

        return new DataTab(__('Wiki'), $template);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws CheckException
     */
    protected function getLdapConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('ldap');

        $template->assign('ldapIsAvailable',
            $this->extensionChecker->checkIsAvailable('ldap'));
        $template->assign('userGroups',
            SelectItemAdapter::factory(UserGroupService::getItemsBasic())
                ->getItemsFromModel());
        $template->assign('userProfiles',
            SelectItemAdapter::factory(UserProfileService::getItemsBasic())
                ->getItemsFromModel());

        $serverTypes = [
            LdapTypeInterface::LDAP_STD => 'Standard',
            LdapTypeInterface::LDAP_ADS => 'Active Directory'
        ];

        $template->assign('serverTypes',
            SelectItemAdapter::factory($serverTypes)
                ->getItemsFromArraySelected([$this->configData->getLdapType()]));

        $userAttributes = array_merge(
            LdapStd::DEFAULT_FILTER_USER_ATTRIBUTES,
            LdapMsAds::DEFAULT_FILTER_USER_ATTRIBUTES,
            $this->configData->getLdapFilterUserAttributes()
        );

        $template->assign('userAttributes',
            SelectItemAdapter::factory($userAttributes)
                ->getItemsFromArraySelected($this->configData->getLdapFilterUserAttributes()));

        $groupAttributes = array_merge(
            LdapStd::DEFAULT_FILTER_GROUP_ATTRIBUTES,
            LdapMsAds::DEFAULT_FILTER_GROUP_ATTRIBUTES,
            $this->configData->getLdapFilterGroupAttributes()
        );

        $template->assign('groupAttributes',
            SelectItemAdapter::factory($groupAttributes)
                ->getItemsFromArraySelected($this->configData->getLdapFilterGroupAttributes()));

        return new DataTab(__('LDAP'), $template);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getMailConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('mail');

        $template->assign('mailSecurity', ['SSL', 'TLS']);
        $template->assign('userGroups',
            SelectItemAdapter::factory(
                UserGroupService::getItemsBasic()
            )->getItemsFromModel()
        );
        $template->assign('userProfiles',
            SelectItemAdapter::factory(
                UserProfileService::getItemsBasic()
            )->getItemsFromModel()
        );

        $mailEvents = $this->configData->getMailEvents();

        $events = array_merge(
            MailHandler::EVENTS,
            $mailEvents
        );

        sort($events, SORT_STRING);

        $template->assign(
            'mailEvents',
            SelectItemAdapter::factory($events)
                ->getItemsFromArraySelected(
                    $mailEvents,
                    true
                )
        );

        return new DataTab(__('Mail'), $template);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    protected function getEncryptionConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('encryption');

        $numAccounts = $this->dic->get(AccountService::class)->getTotalNumAccounts();
        $template->assign('numAccounts', $numAccounts);

        if ($numAccounts > 150) {
            $template->assign(
                'taskId',
                Task::genTaskId('masterpass')
            );
        }

        $configService = $this->dic->get(ConfigService::class);

        $template->assign(
            'lastUpdateMPass',
            $configService->getByParam('lastupdatempass', 0)
        );

        $template->assign(
            'tempMasterPassTime',
            $configService->getByParam(TemporaryMasterPassService::PARAM_TIME, 0)
        );
        $template->assign(
            'tempMasterMaxTime',
            $configService->getByParam(TemporaryMasterPassService::PARAM_MAX_TIME, 0)
        );

        $tempMasterAttempts = sprintf(
            '%d/%d',
            $configService->getByParam(TemporaryMasterPassService::PARAM_ATTEMPTS, 0),
            TemporaryMasterPassService::MAX_ATTEMPTS
        );

        $template->assign('tempMasterAttempts', $tempMasterAttempts);
        $template->assign(
            'tempMasterPass',
            $this->session->getTemporaryMasterPass()
        );

        $template->assign(
            'userGroups',
            SelectItemAdapter::factory(UserGroupService::getItemsBasic())
                ->getItemsFromModel()
        );

        return new DataTab(__('Encryption'), $template);
    }

    /**
     * @throws CheckException
     */
    protected function getBackupConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('backup');
        $template->assign(
            'pharIsAvailable',
            $this->extensionChecker->checkPharAvailable()
        );

        $template->assign('siteName', AppInfoInterface::APP_NAME);

        $backupAppFile = new FileHandler(
            BackupFiles::getAppBackupFilename(
                BACKUP_PATH,
                $this->configData->getBackupHash() ?: '',
                true
            )
        );
        $backupDbFile = new FileHandler(
            BackupFiles::getDbBackupFilename(
                BACKUP_PATH,
                $this->configData->getBackupHash() ?: '',
                true
            )
        );
        $exportFile = new FileHandler(
            XmlExportService::getExportFilename(
                BACKUP_PATH,
                $this->configData->getExportHash() ?: '', true
            )
        );

        try {
            $backupAppFile->checkFileExists();
            $backupDbFile->checkFileExists();

            $template->assign('hasBackup', true);
            $template->assign(
                'lastBackupTime',
                date('r', $backupAppFile->getFileTime())
            );
        } catch (FileException $e) {
            $template->assign('hasBackup', false);
            $template->assign(
                'lastBackupTime',
                __('There aren\'t any backups available')
            );
        }

        try {
            $exportFile->checkFileExists();

            $template->assign('hasExport', true);
            $template->assign('lastExportTime',
                date('r', $exportFile->getFileTime())
            );
        } catch (FileException $e) {
            $template->assign('hasExport', false);
            $template->assign('lastExportTime',
                __('No export file found')
            );
        }

        return new DataTab(__('Backup'), $template);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getImportConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('import');

        $template->assign(
            'userGroups',
            SelectItemAdapter::factory(UserGroupService::getItemsBasic())
                ->getItemsFromModelSelected([$this->userData->getUserGroupId()])
        );
        $template->assign(
            'users',
            SelectItemAdapter::factory(UserService::getItemsBasic())
                ->getItemsFromModelSelected([$this->userData->getId()])
        );

        return new DataTab(__('Import Accounts'), $template);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    protected function getInfo(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('info');

        $databaseUtil = $this->dic->get(DatabaseUtil::class);

        $template->assign('dbInfo', $databaseUtil->getDBinfo());
        $template->assign(
            'dbName',
            $this->configData->getDbName() . '@' . $this->configData->getDbHost()
        );
        $template->assign(
            'configBackupDate',
            date('r', $this->dic->get(ConfigService::class)->getByParam('config_backup_date', 0))
        );
        $template->assign(
            'plugins',
            $this->dic->get(PluginManager::class)->getLoadedPlugins()
        );
        $template->assign(
            'locale',
            Language::$localeStatus ?: sprintf('%s (%s)', $this->configData->getSiteLang(), __('Not installed'))
        );
        $template->assign(
            'securedSession',
            CryptSessionHandler::$isSecured
        );
        $template->assign(
            'missingExtensions',
            $this->extensionChecker->getMissing()
        );
        $template->assign(
            'downloadRate',
            round(Util::getMaxDownloadChunk() / 1024 / 1024)
        );

        $isDemo = $this->configData->isDemoEnabled();

        $template->assign(
            'downloadConfigBackup',
            !$isDemo && $this->userData->getIsAdminApp()
        );
        $template->assign(
            'downloadLog',
            !$isDemo
            && is_readable(LOG_FILE)
            && $this->userData->getIsAdminApp()
        );

        return new DataTab(__('Information'), $template);
    }

    public function getTabsHelper(): TabsHelper
    {
        return $this->tabsHelper;
    }

    /**
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checkLoggedIn();
    }
}