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

use Aura\SqlQuery\QueryFactory;
use Klein\Klein;
use Klein\Request as KleinRequest;
use Klein\Response as KleinResponse;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\Actions;
use SP\Core\Application;
use SP\Core\Bootstrap\UriContext;
use SP\Core\Context\ContextFactory;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\CryptPKI;
use SP\Core\Crypt\Csrf;
use SP\Core\Crypt\RequestBasedPassword;
use SP\Core\Crypt\UuidCookie;
use SP\Core\Language;
use SP\Core\MimeTypes;
use SP\Core\ProvidersHelper;
use SP\Core\UI\Theme;
use SP\Core\UI\ThemeContext;
use SP\Core\UI\ThemeIcons;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapAuthService;
use SP\Domain\Auth\Ports\LdapConnectionInterface;
use SP\Domain\Auth\Providers\AclHandler;
use SP\Domain\Auth\Providers\AuthProvider;
use SP\Domain\Auth\Providers\AuthProviderService;
use SP\Domain\Auth\Providers\AuthType;
use SP\Domain\Auth\Providers\Browser\BrowserAuth;
use SP\Domain\Auth\Providers\Browser\BrowserAuthService;
use SP\Domain\Auth\Providers\Database\DatabaseAuth;
use SP\Domain\Auth\Providers\Database\DatabaseAuthService;
use SP\Domain\Auth\Providers\Ldap\LdapActions;
use SP\Domain\Auth\Providers\Ldap\LdapAuth;
use SP\Domain\Auth\Providers\Ldap\LdapBase;
use SP\Domain\Auth\Providers\Ldap\LdapConnection;
use SP\Domain\Auth\Providers\Ldap\LdapParams;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Services\ConfigBackup;
use SP\Domain\Config\Services\ConfigFile;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\CryptPKIInterface;
use SP\Domain\Core\Crypt\RequestBasedPasswordInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\File\MimeTypesService;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Export\Ports\BackupFileHelperService;
use SP\Domain\Export\Services\BackupFileHelper;
use SP\Domain\Html\Ports\MinifyService;
use SP\Domain\Html\Services\Minify;
use SP\Domain\Http\Client;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Http\Services\Request;
use SP\Domain\Install\Adapters\InstallDataFactory;
use SP\Domain\Install\Services\DatabaseSetupInterface;
use SP\Domain\Install\Services\MysqlSetupBuilder;
use SP\Domain\Log\Providers\DatabaseHandler;
use SP\Domain\Log\Providers\FileHandler as LogFileHandler;
use SP\Domain\Log\Providers\RemoteSyslogHandler;
use SP\Domain\Log\Providers\SyslogHandler;
use SP\Domain\Notification\Ports\MailerInterface;
use SP\Domain\Notification\Providers\MailHandler;
use SP\Domain\Notification\Providers\NotificationHandler;
use SP\Domain\Notification\Providers\PhpMailerWrapper;
use SP\Domain\Notification\Services\Mail;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\Database\MysqlHandler;
use SP\Infrastructure\File\DirectoryHandler;
use SP\Infrastructure\File\FileCache;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\XmlFileStorage;
use SP\Mvc\View\Template;
use SP\Mvc\View\TemplateInterface;

use function DI\autowire;
use function DI\create;
use function DI\factory;
use function DI\get;
use function SP\__u;

/**
 * Class CoreDefinitions
 */
final class CoreDefinitions
{
    public static function getDefinitions(): array
    {
        return [
            Klein::class => autowire(Klein::class),
            KleinRequest::class => factory([KleinRequest::class, 'createFromGlobals']),
            KleinResponse::class => create(KleinResponse::class),
            RequestService::class => autowire(Request::class),
            UriContextInterface::class => autowire(UriContext::class),
            Context::class =>
                static fn() => ContextFactory::getForModule(APP_MODULE),
            ConfigFileService::class => create(ConfigFile::class)
                ->constructor(
                    create(XmlFileStorage::class)
                        ->constructor(create(LogFileHandler::class)->constructor(CONFIG_FILE)),
                    create(FileCache::class)->constructor(ConfigFile::CONFIG_CACHE_FILE),
                    get(Context::class),
                    autowire(ConfigBackup::class)
                ),
            ConfigDataInterface::class => factory([ConfigFileService::class, 'getConfigData']),
            DatabaseConnectionData::class => factory([DatabaseConnectionData::class, 'getFromConfig']),
            DbStorageHandler::class => autowire(MysqlHandler::class),
            ActionsInterface::class =>
                static fn() => new Actions(
                    new FileCache(Actions::ACTIONS_CACHE_FILE),
                    new XmlFileStorage(new FileHandler(ACTIONS_FILE))
                ),
            MimeTypesService::class =>
                static fn() => new MimeTypes(
                    new FileCache(MimeTypes::MIME_CACHE_FILE),
                    new XmlFileStorage(new FileHandler(MIMETYPES_FILE))
                ),
            Acl::class => autowire(Acl::class)
                ->constructorParameter('actions', get(ActionsInterface::class)),
            ThemeContextInterface::class => autowire(ThemeContext::class)
                ->constructorParameter('basePath', VIEW_PATH)
                ->constructorParameter('baseUri', factory([UriContextInterface::class, 'getWebRoot']))
                ->constructorParameter('module', APP_MODULE)
                ->constructorParameter('name', factory([Theme::class, 'getThemeName'])),
            ThemeIconsInterface::class => factory([ThemeIcons::class, 'loadIcons'])
                ->parameter(
                    'iconsCache',
                    create(FileCache::class)->constructor(ThemeIcons::ICONS_CACHE_FILE)
                ),
            ThemeInterface::class => autowire(Theme::class),
            TemplateInterface::class => autowire(Template::class),
            DatabaseAuthService::class => autowire(DatabaseAuth::class),
            BrowserAuthService::class => autowire(BrowserAuth::class),
            LdapParams::class => factory([LdapParams::class, 'getFrom']),
            LdapConnectionInterface::class => autowire(LdapConnection::class),
            LdapActionsService::class => autowire(LdapActions::class),
            LdapAuthService::class => autowire(LdapAuth::class)
                ->constructorParameter(
                    'ldap',
                    factory([LdapBase::class, 'factory'])
                ),
            AuthProviderService::class => factory(
                static function (
                    AuthProvider        $authProvider,
                    ConfigDataInterface $configData,
                    LdapAuthService     $ldapAuth,
                    BrowserAuthService  $browserAuth,
                    DatabaseAuthService $databaseAuth,
                ) {
                    if ($configData->isLdapEnabled()) {
                        $authProvider->registerAuth($ldapAuth, AuthType::Ldap);
                    }

                    if ($configData->isAuthBasicEnabled()) {
                        $authProvider->registerAuth($browserAuth, AuthType::Browser);
                    }

                    $authProvider->registerAuth($databaseAuth, AuthType::Database);

                    return $authProvider;
                }
            )->parameter('authProvider', autowire(AuthProvider::class)),
            Logger::class => create(Logger::class)
                ->constructor('syspass'),
            \GuzzleHttp\Client::class => create(\GuzzleHttp\Client::class)
                ->constructor(factory([Client::class, 'getOptions'])),
            Csrf::class => autowire(Csrf::class),
            LanguageInterface::class => autowire(Language::class),
            DatabaseInterface::class => autowire(Database::class),
            PhpMailerWrapper::class => autowire(PhpMailerWrapper::class),
            MailerInterface::class => factory([PhpMailerWrapper::class, 'configure'])
                ->parameter(
                    'mailParams',
                    factory([Mail::class, 'getParamsFromConfig'])
                        ->parameter('configData', get(ConfigDataInterface::class))
                ),
            DatabaseSetupInterface::class => static function (RequestService $request) {
                $installData = InstallDataFactory::buildFromRequest($request);

                if ($installData->getBackendType() === 'mysql') {
                    return MysqlSetupBuilder::build($installData);
                }

                throw SPException::error(__u('Unimplemented'), __u('Wrong backend type'));
            },
            ProvidersHelper::class => factory(static function (ContainerInterface $c) {
                $configData = $c->get(ConfigDataInterface::class);

                if (!$configData->isInstalled()) {
                    return new ProvidersHelper($c->get(LogFileHandler::class));
                }

                return new ProvidersHelper(
                    $c->get(LogFileHandler::class),
                    $c->get(DatabaseHandler::class),
                    $c->get(MailHandler::class),
                    $c->get(SyslogHandler::class),
                    $c->get(RemoteSyslogHandler::class),
                    $c->get(AclHandler::class),
                    $c->get(NotificationHandler::class)
                );
            }),
            QueryFactory::class => create(QueryFactory::class)
                ->constructor('mysql', QueryFactory::COMMON),
            CryptInterface::class => create(Crypt::class),
            CryptPKIInterface::class => autowire(CryptPKI::class)
                ->constructorParameter('publicKeyFile', new FileHandler(CryptPKI::PUBLIC_KEY_FILE))
                ->constructorParameter('privateKeyFile', new FileHandler(CryptPKI::PRIVATE_KEY_FILE)),
            FileCacheService::class => create(FileCache::class),
            Application::class => autowire(Application::class),
            UuidCookie::class => factory([UuidCookie::class, 'factory'])
                ->parameter(
                    'request',
                    get(RequestService::class)
                ),
            RequestBasedPasswordInterface::class => autowire(RequestBasedPassword::class),
            MinifyService::class => autowire(Minify::class),
            BackupFileHelperService::class => autowire(BackupFileHelper::class)
                ->constructorParameter('path', new DirectoryHandler(BACKUP_PATH))
        ];
    }
}
