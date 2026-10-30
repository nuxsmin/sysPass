<?php

declare(strict_types=1);
/**
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

namespace SP\Modules\Web\Presenters;

use RuntimeException;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Core\Language;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Auth\Enums\LdapTypeEnum;
use SP\Domain\Auth\Providers\Ldap\LdapMsAds;
use SP\Domain\Auth\Providers\Ldap\LdapStd;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\File\MimeType;
use SP\Domain\Core\File\MimeTypesService;
use SP\Domain\Core\Exceptions\FileException;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Crypt\Ports\TemporaryMasterPassService;
use SP\Domain\Export\Dtos\BackupFile as BackupFileDto;
use SP\Domain\Export\Dtos\BackupFiles;
use SP\Domain\Export\Dtos\BackupType;
use SP\Domain\Log\Providers\LogInterface;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\Ports\UserService;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\File\FileHandler;
use SP\Modules\Web\Mvc\View\Components\DataTab;
use SP\Modules\Web\Mvc\View\Components\SelectItemAdapter;
use SP\Modules\Web\Mvc\View\TemplateInterface;
use SP\Util\Util;

use function SP\__;

/**
 * Builds the presentation data for the configuration manager tabs.
 */
final readonly class ConfigManagerPresenter
{
    public function __construct(
        private TemplateInterface        $view,
        private ThemeInterface           $theme,
        private ConfigDataInterface      $configData,
        private Context                  $context,
        private PhpExtensionCheckerService $extensionChecker,
        private UserService              $userService,
        private UserGroupService         $userGroupService,
        private UserProfileService       $userProfileService,
        private MimeTypesService         $mimeTypes,
        private DatabaseUtil             $databaseUtil,
        private ConfigService            $configService,
        private AccountService           $accountService,
        private BackupFiles              $backupFiles,
        private PathsContext             $pathsContext
    ) {
    }

    public function getGeneral(): DataTab
    {
        $template = clone $this->view;
        $template->addTemplate('general');

        $userDto = clone $this->context->getUserData();

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
            && !$userDto->isAdminApp
        );
        $template->assign(
            'isDisabled',
            $this->configData->isDemoEnabled()
            && !$userDto->isAdminApp ? 'disabled' : ''
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

    public function getAccounts(): DataTab
    {
        $template = clone $this->view;
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

    public function getWiki(): DataTab
    {
        $template = clone $this->view;
        $template->addTemplate('wiki');

        $template->assign(
            'curlIsAvailable',
            $this->extensionChecker->checkCurl()
        );

        return new DataTab(__('Wiki'), $template);
    }

    public function getLdap(): DataTab
    {
        $template = clone $this->view;
        $template->addTemplate('ldap');

        $template->assign(
            'ldapIsAvailable',
            $this->extensionChecker->checkLdap()
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

    public function getMail(): DataTab
    {
        $template = clone $this->view;
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

        $mailEvents = $this->configData->getMailEvents() ?? [];

        $events = array_merge(MailService::EVENTS, $mailEvents);

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

    public function getEncryption(): DataTab
    {
        $template = clone $this->view;
        $template->addTemplate('encryption');

        $numAccounts = $this->accountService->getTotalNumAccounts();
        $template->assign('numAccounts', $numAccounts);

        $template->assign(
            'lastUpdateMPass',
            $this->configService->getByParam('lastupdatempass', '0')
        );

        $template->assign(
            'tempMasterPassTime',
            $this->configService->getByParam(TemporaryMasterPassService::PARAM_TIME, '0')
        );
        $template->assign(
            'tempMasterMaxTime',
            $this->configService->getByParam(TemporaryMasterPassService::PARAM_MAX_TIME, '0')
        );

        $tempMasterAttempts = sprintf(
            '%d/%d',
            $this->configService->getByParam(TemporaryMasterPassService::PARAM_ATTEMPTS, '0'),
            TemporaryMasterPassService::MAX_ATTEMPTS
        );

        $template->assign('tempMasterAttempts', $tempMasterAttempts);
        $template->assign('tempMasterPass', $this->getTemporaryMasterPass());

        $template->assign(
            'userGroups',
            SelectItemAdapter::factory($this->userGroupService->getAll())->getItemsFromModel()
        );

        return new DataTab(__('Encryption'), $template);
    }

    public function getBackup(): DataTab
    {
        $template = clone $this->view;
        $template->addTemplate('backup');
        $template->assign(
            'pharIsAvailable',
            $this->extensionChecker->checkPhar()
        );

        $template->assign('siteName', AppInfoInterface::APP_NAME);

        $backupFiles = $this->backupFiles->withHash($this->configData->getBackupHash() ?? '');

        try {
            $backupAppFile = new FileHandler((string)$backupFiles->getAppBackupFile());

            $template->assign('hasBackup', true);
            $template->assign(
                'lastBackupTime',
                date('r', $backupAppFile->getFileTime())
            );
        } catch (RuntimeException|FileException) {
            $template->assign('hasBackup', false);
            $template->assign(
                'lastBackupTime',
                __('There aren\'t any backups available')
            );
        }

        try {
            $exportFile = new FileHandler(
                (string)new BackupFileDto(
                    BackupType::export,
                    $this->configData->getExportHash() ?: '',
                    $this->pathsContext[Path::BACKUP],
                    'gz'
                )
            );

            $template->assign('hasExport', true);
            $template->assign(
                'lastExportTime',
                date('r', $exportFile->getFileTime())
            );
        } catch (RuntimeException|FileException) {
            $template->assign('hasExport', false);
            $template->assign(
                'lastExportTime',
                __('No export file found')
            );
        }

        return new DataTab(__('Backup'), $template);
    }

    public function getImport(): DataTab
    {
        $template = clone $this->view;
        $template->addTemplate('import');

        $userDto = clone $this->context->getUserData();

        $template->assign(
            'userGroups',
            SelectItemAdapter::factory($this->userGroupService->getAll())
                             ->getItemsFromModelSelected([$userDto->userGroupId])
        );
        $template->assign(
            'users',
            SelectItemAdapter::factory($this->userService->getAll())
                             ->getItemsFromModelSelected([$userDto->id])
        );

        return new DataTab(__('Import Accounts'), $template);
    }

    public function getInfo(): DataTab
    {
        $template = clone $this->view;
        $template->addTemplate('info');

        $userDto = clone $this->context->getUserData();

        $template->assign('dbInfo', $this->databaseUtil->getDBinfo());
        $template->assign('dbName', $this->configData->getDbName() . '@' . $this->configData->getDbHost());
        $template->assign(
            'configBackupDate',
            date('r', (int)$this->configService->getByParam('config_backup_date', '0'))
        );
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
            !$isDemo && $userDto->isAdminApp
        );
        $template->assign(
            'downloadLog',
            !$isDemo && is_readable($this->pathsContext[Path::LOG_FILE]) && $userDto->isAdminApp
        );

        return new DataTab(__('Information'), $template);
    }

    private function getTemporaryMasterPass(): ?string
    {
        return $this->context instanceof SessionContext
            ? $this->context->getTemporaryMasterPass()
            : null;
    }
}