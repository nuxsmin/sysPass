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
use Klein\Request as KleinRequest;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler as MSyslogHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SessionHandlerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\Actions;
use SP\Core\Bootstrap\RouteContext;
use SP\Core\Bootstrap\UriContext;
use SP\Core\Context\Stateless;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\CryptPKI;
use SP\Core\Crypt\CryptSessionHandler;
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
use SP\Domain\Common\Providers\Filter;
use SP\Domain\Config\Ports\ConfigBackupService;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Config\Services\ConfigFile;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Bootstrap\RouteContextData;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\CryptPKIHandler;
use SP\Domain\Core\Crypt\CsrfHandler;
use SP\Domain\Core\Crypt\RequestBasedPasswordInterface;
use SP\Domain\Core\Crypt\UuidCookieInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\File\MimeTypesService;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Crypt\Ports\SecureSessionService;
use SP\Domain\Crypt\Services\SecureSession;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Export\Ports\BackupFileHelperService;
use SP\Domain\Export\Services\BackupFileHelper;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Html\Ports\MinifyService;
use SP\Domain\Html\Services\Minify;
use SP\Domain\Http\Client;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Http\Services\Request;
use SP\Domain\Install\Adapters\InstallDataFactory;
use SP\Domain\Install\Services\DatabaseSetupInterface;
use SP\Domain\Install\Services\MysqlSetupBuilder;
use SP\Domain\Log\Providers\DatabaseHandler;
use SP\Domain\Log\Providers\LogHandler;
use SP\Domain\Notification\Ports\MailerInterface;
use SP\Domain\Notification\Services\Mail;
use SP\Domain\Notification\Services\MailEvent;
use SP\Domain\Notification\Services\NotificationEvent;
use SP\Domain\Notification\Services\PhpMailerService;
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
            KleinRequest::class => factory([KleinRequest::class, 'createFromGlobals']),
            RequestService::class => autowire(Request::class),
            UriContextInterface::class => autowire(UriContext::class),
            Context::class => create(Stateless::class),
            ConfigFileService::class => create(ConfigFile::class)
                ->constructor(
                    create(XmlFileStorage::class)
                        ->constructor(create(FileHandlerInterface::class)->constructor(CONFIG_FILE)),
                    create(FileCacheService::class)->constructor(ConfigFile::CONFIG_CACHE_FILE),
                    get(Context::class),
                    autowire(ConfigBackupService::class)
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
            AclInterface::class => autowire(Acl::class)
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
            TemplateInterface::class => autowire(Template::class)
                ->constructorParameter('base', factory([RouteContextData::class, 'getController'])),
            DatabaseAuthService::class => autowire(DatabaseAuth::class),
            BrowserAuthService::class => autowire(BrowserAuth::class),
            LdapParams::class => factory([LdapParams::class, 'getFrom']),
            LdapConnectionInterface::class => autowire(LdapConnection::class),
            LdapActionsService::class => autowire(LdapActions::class),
            LdapAuthService::class => autowire(LdapAuth::class)
                ->constructorParameter('ldap', factory([LdapBase::class, 'factory'])),
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
            ),
            LoggerInterface::class => factory(function (ConfigDataInterface $configData) {
                $handlers = [];
                $handlers[] = new StreamHandler(LOG_FILE);

                if ($configData->isInstalled()) {
                    if ($configData->isSyslogRemoteEnabled()
                        && $configData->getSyslogServer()
                        && $configData->getSyslogPort()
                    ) {
                        $handlers[] = new SyslogUdpHandler(
                            $configData->getSyslogServer(),
                            $configData->getSyslogPort(),
                            LOG_USER,
                            Logger::DEBUG,
                            true,
                            'syspass'
                        );
                    }

                    if ($configData->isSyslogEnabled()) {
                        $handlers[] = new MSyslogHandler('syspass');
                    }
                }

                return new Logger('syspass', $handlers);
            }),
            \GuzzleHttp\Client::class => create(\GuzzleHttp\Client::class)
                ->constructor(factory([Client::class, 'getOptions'])),
            CsrfHandler::class => autowire(Csrf::class),
            LanguageInterface::class => autowire(Language::class),
            DatabaseInterface::class => autowire(Database::class),
            PhpMailerService::class => autowire(PhpMailerService::class),
            MailerInterface::class => factory([PhpMailerService::class, 'configure'])
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
                    return new ProvidersHelper($c->get(LogHandler::class));
                }

                return new ProvidersHelper(
                    $c->get(LogHandler::class),
                    $c->get(DatabaseHandler::class),
                    $c->get(MailEvent::class),
                    $c->get(AclHandler::class),
                    $c->get(NotificationEvent::class)
                );
            }),
            QueryFactory::class => create(QueryFactory::class)
                ->constructor('mysql', QueryFactory::COMMON),
            CryptInterface::class => create(Crypt::class),
            CryptPKIHandler::class => autowire(CryptPKI::class)
                ->constructorParameter('publicKeyFile', new FileHandler(CryptPKI::PUBLIC_KEY_FILE))
                ->constructorParameter('privateKeyFile', new FileHandler(CryptPKI::PRIVATE_KEY_FILE)),
            FileCacheService::class => create(FileCache::class),
            RequestBasedPasswordInterface::class => autowire(RequestBasedPassword::class),
            MinifyService::class => autowire(Minify::class),
            BackupFileHelperService::class => autowire(BackupFileHelper::class)
                ->constructorParameter('path', new DirectoryHandler(BACKUP_PATH)),
            RouteContextData::class => factory(static function (KleinRequest $request) {
                return RouteContext::getRouteContextData(Filter::getString($request->param('r', 'index/index')));
            }),
            SecureSessionService::class => autowire(SecureSession::class)
                ->constructorParameter(
                    'fileCache',
                    factory(
                        static function (UuidCookieInterface $uuidCookie, ConfigDataInterface $configData) {
                            return new FileCache(
                                SecureSession::getFileNameFrom($uuidCookie, $configData->getPasswordSalt())
                            );
                        }
                    )->parameter('uuidCookie', factory([UuidCookie::class, 'factory']))
                ),
            SessionHandlerInterface::class => autowire(CryptSessionHandler::class)
                ->constructorParameter('key', [SecureSessionService::class, 'getKey'])
        ];
    }
}
