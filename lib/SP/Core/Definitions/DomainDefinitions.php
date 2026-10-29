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

namespace SP\Core\Definitions;

use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Common\Providers\Image;
use SP\Domain\Export\Ports\XmlVerifyService;
use SP\Domain\Export\Services\XmlVerify;
use SP\Domain\Account\Adapters\Account;
use SP\Domain\Account\Ports\AccountAdapter;
use SP\Domain\Category\Adapters\Category;
use SP\Domain\Category\Ports\CategoryAdapter;
use SP\Domain\Client\Adapters\Client;
use SP\Domain\Client\Ports\ClientAdapter;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Image\Ports\ImageService;
use SP\Domain\Import\Ports\ImportHelperInterface;
use SP\Domain\Import\Services\ImportHelper;
use SP\Infrastructure\Common\Repositories\SimpleRepository;
use SP\Infrastructure\File\FileSystem;

use function DI\autowire;
use function DI\factory;

/**
 * Class DomainDefinitions
 */
final class DomainDefinitions
{
    /**
     * Explicit port bindings.
     *
     * One entry per port that the container wires to a single implementation.
     * Ports that need module or runtime data, or that have multiple
     * implementations, stay unresolved and are documented in
     * ContainerWiringTest.
     *
     * @var array<class-string, class-string>
     */
    private const PORTS = [
        // Account
        'SP\Domain\Account\Ports\AccountAclService' => 'SP\Domain\Account\Services\AccountAcl',
        'SP\Domain\Account\Ports\AccountCacheService' => 'SP\Domain\Account\Services\AccountCache',
        'SP\Domain\Account\Ports\AccountCryptService' => 'SP\Domain\Account\Services\AccountCrypt',
        'SP\Domain\Account\Ports\AccountFileRepository' => 'SP\Infrastructure\Account\Repositories\AccountFile',
        'SP\Domain\Account\Ports\AccountFileService' => 'SP\Domain\Account\Services\AccountFile',
        'SP\Domain\Account\Ports\AccountFilterBuilder' => 'SP\Domain\Account\Services\Builders\AccountFilter',
        'SP\Domain\Account\Ports\AccountHistoryRepository' => 'SP\Infrastructure\Account\Repositories\AccountHistory',
        'SP\Domain\Account\Ports\AccountHistoryService' => 'SP\Domain\Account\Services\AccountHistory',
        'SP\Domain\Account\Ports\AccountItemsService' => 'SP\Domain\Account\Services\AccountItems',
        'SP\Domain\Account\Ports\AccountMasterPasswordService' => 'SP\Domain\Account\Services\AccountMasterPassword',
        'SP\Domain\Account\Ports\AccountPresetService' => 'SP\Domain\Account\Services\AccountPreset',
        'SP\Domain\Account\Ports\AccountRepository' => 'SP\Infrastructure\Account\Repositories\Account',
        'SP\Domain\Account\Ports\AccountSearchRepository' => 'SP\Infrastructure\Account\Repositories\AccountSearch',
        'SP\Domain\Account\Ports\AccountSearchService' => 'SP\Domain\Account\Services\AccountSearch',
        'SP\Domain\Account\Ports\AccountService' => 'SP\Domain\Account\Services\Account',
        'SP\Domain\Account\Ports\AccountToFavoriteRepository' => 'SP\Infrastructure\Account\Repositories\AccountToFavorite',
        'SP\Domain\Account\Ports\AccountToFavoriteService' => 'SP\Domain\Account\Services\AccountToFavorite',
        'SP\Domain\Account\Ports\AccountToTagRepository' => 'SP\Infrastructure\Account\Repositories\AccountToTag',
        'SP\Domain\Account\Ports\AccountToTagService' => 'SP\Domain\Account\Services\AccountToTag',
        'SP\Domain\Account\Ports\AccountToUserGroupRepository' => 'SP\Infrastructure\Account\Repositories\AccountToUserGroup',
        'SP\Domain\Account\Ports\AccountToUserGroupService' => 'SP\Domain\Account\Services\AccountToUserGroup',
        'SP\Domain\Account\Ports\AccountToUserRepository' => 'SP\Infrastructure\Account\Repositories\AccountToUser',
        'SP\Domain\Account\Ports\AccountToUserService' => 'SP\Domain\Account\Services\AccountToUser',
        'SP\Domain\Account\Ports\PublicLinkRepository' => 'SP\Infrastructure\Account\Repositories\PublicLink',
        'SP\Domain\Account\Ports\PublicLinkService' => 'SP\Domain\Account\Services\PublicLink',

        // Api
        'SP\Domain\Api\Ports\ApiService' => 'SP\Domain\Api\Services\Api',

        // Auth
        'SP\Domain\Auth\Ports\AuthTokenActionService' => 'SP\Domain\Auth\Services\AuthTokenAction',
        'SP\Domain\Auth\Ports\AuthTokenRepository' => 'SP\Infrastructure\Auth\Repositories\AuthToken',
        'SP\Domain\Auth\Ports\AuthTokenService' => 'SP\Domain\Auth\Services\AuthToken',
        'SP\Domain\Auth\Ports\LdapCheckService' => 'SP\Domain\Auth\Services\LdapCheck',
        'SP\Domain\Auth\Ports\LoginAuthHandlerService' => 'SP\Domain\Auth\Services\LoginAuthHandler',
        'SP\Domain\Auth\Ports\LoginMasterPassService' => 'SP\Domain\Auth\Services\LoginMasterPass',
        'SP\Domain\Auth\Ports\LoginService' => 'SP\Domain\Auth\Services\Login',
        'SP\Domain\Auth\Ports\LoginUserService' => 'SP\Domain\Auth\Services\LoginUser',

        // Category
        'SP\Domain\Category\Ports\CategoryRepository' => 'SP\Infrastructure\Category\Repositories\Category',
        'SP\Domain\Category\Ports\CategoryService' => 'SP\Domain\Category\Services\Category',

        // Client
        'SP\Domain\Client\Ports\ClientRepository' => 'SP\Infrastructure\Client\Repositories\Client',
        'SP\Domain\Client\Ports\ClientService' => 'SP\Domain\Client\Services\Client',

        // Config
        'SP\Domain\Config\Ports\ConfigBackupService' => 'SP\Domain\Config\Services\ConfigBackup',
        'SP\Domain\Config\Ports\ConfigRepository' => 'SP\Infrastructure\Config\Repositories\Config',
        'SP\Domain\Config\Ports\ConfigService' => 'SP\Domain\Config\Services\Config',

        // Crypt
        'SP\Domain\Crypt\Ports\MasterPassService' => 'SP\Domain\Crypt\Services\MasterPass',
        'SP\Domain\Crypt\Ports\TemporaryMasterPassService' => 'SP\Domain\Crypt\Services\TemporaryMasterPass',

        // CustomField
        'SP\Domain\CustomField\Ports\CustomFieldAdapter' => 'SP\Domain\CustomField\Adapters\CustomField',
        'SP\Domain\CustomField\Ports\CustomFieldCryptService' => 'SP\Domain\CustomField\Services\CustomFieldCrypt',
        'SP\Domain\CustomField\Ports\CustomFieldDataRepository' => 'SP\Infrastructure\CustomField\Repositories\CustomFieldData',
        'SP\Domain\CustomField\Ports\CustomFieldDataService' => 'SP\Domain\CustomField\Services\CustomFieldData',
        'SP\Domain\CustomField\Ports\CustomFieldDefinitionRepository' => 'SP\Infrastructure\CustomField\Repositories\CustomFieldDefinition',
        'SP\Domain\CustomField\Ports\CustomFieldDefinitionService' => 'SP\Domain\CustomField\Services\CustomFieldDefinition',
        'SP\Domain\CustomField\Ports\CustomFieldTypeRepository' => 'SP\Infrastructure\CustomField\Repositories\CustomFieldType',
        'SP\Domain\CustomField\Ports\CustomFieldTypeService' => 'SP\Domain\CustomField\Services\CustomFieldType',

        // Export
        'SP\Domain\Export\Ports\XmlAccountExportService' => 'SP\Domain\Export\Services\XmlAccountExport',
        'SP\Domain\Export\Ports\XmlCategoryExportService' => 'SP\Domain\Export\Services\XmlCategoryExport',
        'SP\Domain\Export\Ports\XmlClientExportService' => 'SP\Domain\Export\Services\XmlClientExport',
        'SP\Domain\Export\Ports\XmlExportService' => 'SP\Domain\Export\Services\XmlExport',
        'SP\Domain\Export\Ports\XmlTagExportService' => 'SP\Domain\Export\Services\XmlTagExport',

        // Import
        'SP\Domain\Import\Ports\ImportService' => 'SP\Domain\Import\Services\Import',
        'SP\Domain\Import\Ports\ImportStrategyService' => 'SP\Domain\Import\Services\ImportStrategy',
        'SP\Domain\Import\Ports\LdapImportService' => 'SP\Domain\Import\Services\LdapImport',
        'SP\Domain\Import\Ports\XmlFileService' => 'SP\Domain\Import\Services\XmlFile',

        // Install
        'SP\Domain\Install\Ports\InstallerService' => 'SP\Domain\Install\Services\Installer',

        // ItemPreset
        'SP\Domain\ItemPreset\Ports\ItemPresetRepository' => 'SP\Infrastructure\ItemPreset\Repositories\ItemPreset',
        'SP\Domain\ItemPreset\Ports\ItemPresetService' => 'SP\Domain\ItemPreset\Services\ItemPreset',

        // Notification
        'SP\Domain\Notification\Ports\MailService' => 'SP\Domain\Notification\Services\Mail',
        'SP\Domain\Notification\Ports\NotificationRepository' => 'SP\Infrastructure\Notification\Repositories\Notification',
        'SP\Domain\Notification\Ports\NotificationService' => 'SP\Domain\Notification\Services\Notification',

        // Plugin
        'SP\Domain\Plugin\Ports\PluginCompatilityService' => 'SP\Domain\Plugin\Services\PluginCompatility',
        'SP\Domain\Plugin\Ports\PluginDataRepository' => 'SP\Infrastructure\Plugin\Repositories\PluginData',
        'SP\Domain\Plugin\Ports\PluginDataService' => 'SP\Domain\Plugin\Services\PluginData',
        'SP\Domain\Plugin\Ports\PluginLoaderService' => 'SP\Domain\Plugin\Services\PluginLoader',
        'SP\Domain\Plugin\Ports\PluginManagerService' => 'SP\Domain\Plugin\Services\PluginManager',
        'SP\Domain\Plugin\Ports\PluginRegisterService' => 'SP\Domain\Plugin\Services\PluginRegister',
        'SP\Domain\Plugin\Ports\PluginRepository' => 'SP\Infrastructure\Plugin\Repositories\Plugin',

        // Security
        'SP\Domain\Security\Ports\EventlogRepository' => 'SP\Infrastructure\Security\Repositories\Eventlog',
        'SP\Domain\Security\Ports\EventlogService' => 'SP\Domain\Security\Services\Eventlog',
        'SP\Domain\Security\Ports\TrackRepository' => 'SP\Infrastructure\Security\Repositories\Track',
        'SP\Domain\Security\Ports\TrackService' => 'SP\Domain\Security\Services\Track',

        // Tag
        'SP\Domain\Tag\Ports\TagRepository' => 'SP\Infrastructure\Tag\Repositories\Tag',
        'SP\Domain\Tag\Ports\TagService' => 'SP\Domain\Tag\Services\Tag',

        // User
        'SP\Domain\User\Ports\UserGroupRepository' => 'SP\Infrastructure\User\Repositories\UserGroup',
        'SP\Domain\User\Ports\UserGroupService' => 'SP\Domain\User\Services\UserGroup',
        'SP\Domain\User\Ports\UserMasterPassService' => 'SP\Domain\User\Services\UserMasterPass',
        'SP\Domain\User\Ports\UserPassRecoverRepository' => 'SP\Infrastructure\User\Repositories\UserPassRecover',
        'SP\Domain\User\Ports\UserPassRecoverService' => 'SP\Domain\User\Services\UserPassRecover',
        'SP\Domain\User\Ports\UserPassService' => 'SP\Domain\User\Services\UserPass',
        'SP\Domain\User\Ports\UserProfileRepository' => 'SP\Infrastructure\User\Repositories\UserProfile',
        'SP\Domain\User\Ports\UserProfileService' => 'SP\Domain\User\Services\UserProfile',
        'SP\Domain\User\Ports\UserRepository' => 'SP\Infrastructure\User\Repositories\User',
        'SP\Domain\User\Ports\UserService' => 'SP\Domain\User\Services\User',
        'SP\Domain\User\Ports\UserToUserGroupRepository' => 'SP\Infrastructure\User\Repositories\UserToUserGroup',
        'SP\Domain\User\Ports\UserToUserGroupService' => 'SP\Domain\User\Services\UserToUserGroup',
    ];

    public static function getDefinitions(): array
    {
        $sources = [
            ImageService::class => autowire(Image::class)
                ->constructorParameter(
                    'font',
                    factory(
                        static fn(PathsContext $p) => FileSystem::buildPath(
                            $p[Path::PUBLIC],
                            'vendor',
                            'fonts',
                            'NotoSans-Regular-webfont.ttf'
                        )
                    )
                )
                ->constructorParameter(
                    'tempPath',
                    factory(static fn(PathsContext $p) => $p[Path::TMP])
                ),
            Repository::class => autowire(SimpleRepository::class),
            XmlVerifyService::class => autowire(XmlVerify::class)->constructorParameter(
                'schema',
                factory(static fn(PathsContext $p) => $p[Path::XML_SCHEMA])
            ),
            ImportHelperInterface::class => autowire(ImportHelper::class),
            AccountAdapter::class => autowire(Account::class)
                ->constructorParameter('baseUrl', factory([UriContextInterface::class, 'getWebUri'])),
            CategoryAdapter::class => autowire(Category::class)
                ->constructorParameter('baseUrl', factory([UriContextInterface::class, 'getWebUri'])),
            ClientAdapter::class => autowire(Client::class)
                ->constructorParameter('baseUrl', factory([UriContextInterface::class, 'getWebUri'])),
        ];

        foreach (self::PORTS as $port => $implementation) {
            $sources[$port] = autowire($implementation);
        }

        return [
            ...$sources
        ];
    }
}
