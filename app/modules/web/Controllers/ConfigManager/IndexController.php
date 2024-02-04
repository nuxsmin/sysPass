<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\ConfigManager;

use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Core\Events\Event;
use SP\Core\Language;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\File\MimeType;
use SP\Domain\Core\File\MimeTypesService;
use SP\Domain\Crypt\Services\TemporaryMasterPass;
use SP\Domain\Export\Services\BackupFileHelper;
use SP\Domain\Export\Services\XmlExportService;
use SP\Domain\Task\Services\Task;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Domain\User\Ports\UserServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\TabsHelper;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\Components\DataTab;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Plugin\PluginManager;
use SP\Providers\Auth\Ldap\LdapMsAds;
use SP\Providers\Auth\Ldap\LdapStd;
use SP\Providers\Auth\Ldap\LdapTypeEnum;
use SP\Providers\Log\LogInterface;
use SP\Providers\Mail\MailHandler;
use SP\Util\Util;

/**
 * Class ConfigManagerController
 */
final class IndexController extends ControllerBase
{
    protected TabsHelper                $tabsHelper;
    private UserServiceInterface        $userService;
    private UserGroupServiceInterface   $userGroupService;
    private UserProfileServiceInterface $userProfileService;
    private MimeTypesService $mimeTypes;
    private DatabaseUtil     $databaseUtil;
    private ConfigService  $configService;
    private AccountService $accountService;
    private PluginManager  $pluginManager;

    public function __construct(
        Application                 $application,
        WebControllerHelper         $webControllerHelper,
        TabsHelper                  $tabsHelper,
        UserServiceInterface        $userService,
        UserGroupServiceInterface   $userGroupService,
        UserProfileServiceInterface $userProfileService,
        MimeTypesService $mimeTypes,
        DatabaseUtil                $databaseUtil,
        ConfigService    $configService,
        AccountService   $accountService,
        PluginManager               $pluginManager
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->tabsHelper = $tabsHelper;
        $this->userService = $userService;
        $this->userGroupService = $userGroupService;
        $this->userProfileService = $userProfileService;
        $this->mimeTypes = $mimeTypes;
        $this->databaseUtil = $databaseUtil;
        $this->configService = $configService;
        $this->accountService = $accountService;
        $this->pluginManager = $pluginManager;
    }


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
        if ($this->checkAccess(AclActionsInterface::CONFIG_GENERAL)) {
            $this->tabsHelper->addTab($this->getConfigGeneral());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_ACCOUNT)) {
            $this->tabsHelper->addTab($this->getAccountConfig());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_WIKI)) {
            $this->tabsHelper->addTab($this->getWikiConfig());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_LDAP)) {
            $this->tabsHelper->addTab($this->getLdapConfig());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_MAIL)) {
            $this->tabsHelper->addTab($this->getMailConfig());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_CRYPT)) {
            $this->tabsHelper->addTab($this->getEncryptionConfig());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_BACKUP)) {
            $this->tabsHelper->addTab($this->getBackupConfig());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_IMPORT)) {
            $this->tabsHelper->addTab($this->getImportConfig());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_GENERAL)) {
            $this->tabsHelper->addTab($this->getInfo());
        }


        $this->eventDispatcher->notify(
            'show.config',
            new Event($this)
        );

        $this->tabsHelper->renderTabs(
            Acl::getActionRoute(AclActionsInterface::CONFIG),
            $this->request->analyzeInt('tabIndex', 0)
        );

        $this->view();
    }

    /**
     * @return DataTab
     * @throws CheckException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getConfigGeneral(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('general');

        $template->assign(
            'langs',
            SelectItemAdapter::factory(
                Language::getAvailableLanguages()
            )->getItemsFromArraySelected([$this->configData->getSiteLang()])
        );
        $template->assign(
            'themes',
            SelectItemAdapter::factory(
                $this->theme->getAvailable()
            )->getItemsFromArraySelected([$this->configData->getSiteTheme()])
        );
        $template->assign(
            'isDemoMode',
            $this->configData->isDemoEnabled()
            && !$this->userData->getIsAdminApp()
        );
        $template->assign(
            'isDisabled',
            $this->configData->isDemoEnabled()
            && !$this->userData->getIsAdminApp() ? 'disabled' : ''
        );
        $template->assign(
            'users',
            SelectItemAdapter::factory($this->userService->getAll())->getItemsFromModel()
        );
        $template->assign(
            'userGroups',
            SelectItemAdapter::factory($this->userGroupService->getAll())->getItemsFromModel()
        );
        $template->assign(
            'userProfiles',
            SelectItemAdapter::factory($this->userProfileService->getAll())->getItemsFromModel()
        );

        $template->assign('curlIsAvailable', $this->extensionChecker->checkCurl());

        $events = array_merge(LogInterface::EVENTS, $this->configData->getLogEvents());

        sort($events, SORT_STRING);

        $template->assign(
            'logEvents',
            SelectItemAdapter::factory($events)
                             ->getItemsFromArraySelected(
                                 $this->configData->getLogEvents(),
                                 true
                             )
        );

        return new DataTab(__('General'), $template);
    }

    /**
     * @return DataTab
     * @throws CheckException
     */
    protected function getAccountConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('accounts');
        $template->assign('gdIsAvailable', $this->extensionChecker->checkGd());

        $mimeTypesAvailable = array_map(
            static fn(MimeType $mimeType) => $mimeType->getType(),
            $this->mimeTypes->getMimeTypes()
        );

        $mimeTypes = SelectItemAdapter::factory(
            array_merge($mimeTypesAvailable, $this->configData->getFilesAllowedMime())
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
            $this->extensionChecker->checkCurl()
        );

        return new DataTab(__('Wiki'), $template);
    }

    /**
     * @return DataTab
     * @throws CheckException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getLdapConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('ldap');

        $template->assign(
            'ldapIsAvailable',
            $this->extensionChecker->checkLdap('ldap')
        );
        $template->assign(
            'userGroups',
            SelectItemAdapter::factory($this->userGroupService->getAll())->getItemsFromModel()
        );
        $template->assign(
            'userProfiles',
            SelectItemAdapter::factory($this->userProfileService->getAll())->getItemsFromModel()
        );

        $serverTypes = [
            LdapTypeEnum::STD->value => 'Standard',
            LdapTypeEnum::ADS->value => 'Active Directory',
        ];

        $template->assign(
            'serverTypes',
            SelectItemAdapter::factory($serverTypes)
                             ->getItemsFromArraySelected([$this->configData->getLdapType()])
        );

        $userAttributes = array_merge(
            LdapStd::DEFAULT_FILTER_USER_ATTRIBUTES,
            LdapMsAds::DEFAULT_FILTER_USER_ATTRIBUTES,
            $this->configData->getLdapFilterUserAttributes()
        );

        $template->assign(
            'userAttributes',
            SelectItemAdapter::factory($userAttributes)
                             ->getItemsFromArraySelected($this->configData->getLdapFilterUserAttributes())
        );

        $groupAttributes = array_merge(
            LdapStd::DEFAULT_FILTER_GROUP_ATTRIBUTES,
            LdapMsAds::DEFAULT_FILTER_GROUP_ATTRIBUTES,
            $this->configData->getLdapFilterGroupAttributes()
        );

        $template->assign(
            'groupAttributes',
            SelectItemAdapter::factory($groupAttributes)
                             ->getItemsFromArraySelected($this->configData->getLdapFilterGroupAttributes())
        );

        return new DataTab(__('LDAP'), $template);
    }

    /**
     * @return DataTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getMailConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('mail');

        $template->assign('mailSecurity', ['SSL', 'TLS']);
        $template->assign(
            'userGroups',
            SelectItemAdapter::factory($this->userGroupService->getAll())->getItemsFromModel()
        );
        $template->assign(
            'userProfiles',
            SelectItemAdapter::factory($this->userProfileService->getAll())->getItemsFromModel()
        );

        $mailEvents = $this->configData->getMailEvents();

        $events = array_merge(MailHandler::EVENTS, $mailEvents);

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

        $numAccounts = $this->accountService->getTotalNumAccounts();
        $template->assign('numAccounts', $numAccounts);

        if ($numAccounts > 150) {
            $template->assign('taskId', Task::genTaskId('masterpass'));
        }

        $template->assign(
            'lastUpdateMPass',
            $this->configService->getByParam('lastupdatempass', 0)
        );

        $template->assign(
            'tempMasterPassTime',
            $this->configService->getByParam(TemporaryMasterPass::PARAM_TIME, 0)
        );
        $template->assign(
            'tempMasterMaxTime',
            $this->configService->getByParam(TemporaryMasterPass::PARAM_MAX_TIME, 0)
        );

        $tempMasterAttempts = sprintf(
            '%d/%d',
            $this->configService->getByParam(TemporaryMasterPass::PARAM_ATTEMPTS, 0),
            TemporaryMasterPass::MAX_ATTEMPTS
        );

        $template->assign('tempMasterAttempts', $tempMasterAttempts);
        $template->assign('tempMasterPass', $this->session->getTemporaryMasterPass());

        $template->assign(
            'userGroups',
            SelectItemAdapter::factory($this->userGroupService->getAll())->getItemsFromModel()
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
            $this->extensionChecker->checkPhar()
        );

        $template->assign('siteName', AppInfoInterface::APP_NAME);

        $backupAppFile = new FileHandler(
            BackupFileHelper::getAppBackupFilename(
                BACKUP_PATH,
                $this->configData->getBackupHash() ?: '',
                true
            )
        );
        $backupDbFile = new FileHandler(
            BackupFileHelper::getDbBackupFilename(
                BACKUP_PATH,
                $this->configData->getBackupHash() ?: '',
                true
            )
        );
        $exportFile = new FileHandler(
            XmlExportService::getExportFilename(
                BACKUP_PATH,
                $this->configData->getExportHash() ?: '',
                true
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
            $template->assign(
                'lastExportTime',
                date('r', $exportFile->getFileTime())
            );
        } catch (FileException $e) {
            $template->assign('hasExport', false);
            $template->assign(
                'lastExportTime',
                __('No export file found')
            );
        }

        return new DataTab(__('Backup'), $template);
    }

    /**
     * @return DataTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getImportConfig(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('import');

        $template->assign(
            'userGroups',
            SelectItemAdapter::factory($this->userGroupService->getAll())
                             ->getItemsFromModelSelected([$this->userData->getUserGroupId()])
        );
        $template->assign(
            'users',
            SelectItemAdapter::factory($this->userService->getAll())
                             ->getItemsFromModelSelected([$this->userData->getId()])
        );

        return new DataTab(__('Import Accounts'), $template);
    }

    /**
     * @return DataTab
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    protected function getInfo(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('info');

        $template->assign('dbInfo', $this->databaseUtil->getDBinfo());
        $template->assign('dbName', $this->configData->getDbName() . '@' . $this->configData->getDbHost());
        $template->assign(
            'configBackupDate',
            date('r', $this->configService->getByParam('config_backup_date', 0))
        );
        $template->assign('plugins', $this->pluginManager->getLoadedPlugins());
        $template->assign(
            'locale',
            Language::$localeStatus ?: sprintf('%s (%s)', $this->configData->getSiteLang(), __('Not installed'))
        );
        $template->assign('securedSession', CryptSessionHandler::$isSecured);
        $template->assign(
            'missingExtensions',
            $this->extensionChecker->getMissing()
        );
        $template->assign('downloadRate', round(Util::getMaxDownloadChunk() / 1024 / 1024));

        $isDemo = $this->configData->isDemoEnabled();

        $template->assign(
            'downloadConfigBackup',
            !$isDemo && $this->userData->getIsAdminApp()
        );
        $template->assign(
            'downloadLog',
            !$isDemo && is_readable(LOG_FILE) && $this->userData->getIsAdminApp()
        );

        return new DataTab(__('Information'), $template);
    }

    public function getTabsHelper(): TabsHelper
    {
        return $this->tabsHelper;
    }
}
