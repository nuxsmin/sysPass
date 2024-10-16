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
use phpseclib\Crypt\RSA;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use SessionHandlerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\Actions;
use SP\Core\AppLock;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Bootstrap\RouteContext;
use SP\Core\Bootstrap\UriContext;
use SP\Core\Context\Stateless;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\CryptPKI;
use SP\Core\Crypt\CryptSessionHandler;
use SP\Core\Crypt\RequestBasedPassword;
use SP\Core\Crypt\UuidCookie;
use SP\Core\Events\EventDispatcher;
use SP\Core\Language;
use SP\Core\MimeTypes;
use SP\Core\PhpExtensionChecker;
use SP\Core\ProvidersHelper;
use SP\Core\UI\Theme;
use SP\Core\UI\ThemeContext;
use SP\Core\UI\ThemeIcons;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapAuthService;
use SP\Domain\Auth\Ports\LdapConnectionHandler;
use SP\Domain\Auth\Ports\LdapService;
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
use SP\Domain\Core\Crypt\RequestBasedPasswordInterface;
use SP\Domain\Core\Crypt\UuidCookieInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\File\MimeTypesService;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Crypt\Ports\SecureSessionService;
use SP\Domain\Crypt\Services\SecureSession;
use SP\Domain\Database\Ports\DatabaseFileInterface;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Domain\Export\Dtos\BackupFile as BackupFileDto;
use SP\Domain\Export\Dtos\BackupFiles;
use SP\Domain\Export\Dtos\BackupType;
use SP\Domain\Export\Ports\BackupFileService;
use SP\Domain\Export\Services\BackupFile;
use SP\Domain\Http\Client;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Http\Services\Request;
use SP\Domain\Install\Adapters\InstallData;
use SP\Domain\Install\Adapters\InstallDataFactory;
use SP\Domain\Install\Services\DatabaseSetupService;
use SP\Domain\Install\Services\MysqlSetup;
use SP\Domain\Log\Providers\DatabaseHandler;
use SP\Domain\Log\Providers\LogHandler;
use SP\Domain\Notification\Ports\MailerInterface;
use SP\Domain\Notification\Services\Mail;
use SP\Domain\Notification\Services\MailEvent;
use SP\Domain\Notification\Services\NotificationEvent;
use SP\Domain\Notification\Services\PhpMailerService;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Domain\Upgrade\Services\UpgradeDatabase;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\Database\MysqlFileParser;
use SP\Infrastructure\Database\MysqlHandler;
use SP\Infrastructure\File\ArchiveHandler;
use SP\Infrastructure\File\FileCache;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\FileSystem;
use SP\Infrastructure\File\XmlFileStorage;
use SP\Infrastructure\File\YamlFileStorage;
use SP\Mvc\View\OutputHandler;
use SP\Mvc\View\OutputHandlerInterface;
use SP\Mvc\View\Template;
use SP\Mvc\View\TemplateInterface;
use SP\Mvc\View\TemplateResolver;
use SP\Mvc\View\TemplateResolverInterface;

use function DI\autowire;
use function DI\create;
use function DI\factory;
use function DI\get;
use function SP\getFromEnv;

/**
 * Class CoreDefinitions
 */
final class CoreDefinitions
{
    public static function getDefinitions(string $rootPath, string $module): array
    {
        return [
            'paths' => self::getPaths($rootPath),
            PathsContext::class => factory(static function (ContainerInterface $container) {
                $pathsContext = new PathsContext();
                $pathsContext->addPaths($container->get('paths'));

                return $pathsContext;
            }),
            KleinRequest::class => factory([KleinRequest::class, 'createFromGlobals']),
            RequestService::class => autowire(Request::class),
            UriContextInterface::class => autowire(UriContext::class),
            Context::class => create(Stateless::class),
            EventDispatcherInterface::class => create(EventDispatcher::class),
            ConfigFileService::class => create(ConfigFile::class)
                ->constructor(
                    create(XmlFileStorage::class)
                        ->constructor(
                            create(FileHandler::class)
                                ->constructor(
                                    factory(static fn(PathsContext $p) => $p[Path::CONFIG_FILE])
                                )
                        ),
                    create(FileCache::class)
                        ->constructor(
                            factory(
                                static fn(PathsContext $p) => FileSystem::buildPath($p[Path::CACHE], 'config.cache')
                            )
                        ),
                    get(Context::class)
                ),
            ConfigDataInterface::class => factory([ConfigFileService::class, 'getConfigData']),
            InstallData::class => factory([InstallDataFactory::class, 'buildFromRequest']),
            DatabaseConnectionData::class => factory(
                static function (ConfigDataInterface $configData, InstallData $installData) {
                    if (!$configData->isInstalled()) {
                        return DatabaseConnectionData::getFromInstallData($installData);
                    }

                    // TODO: get from env vars
                    return DatabaseConnectionData::getFromConfig($configData);
                }
            ),
            DatabaseFileInterface::class => create(MysqlFileParser::class)
                ->constructor(
                    create(FileHandler::class)
                        ->constructor(factory(static fn(PathsContext $pathsContext) => $pathsContext[Path::SQL_FILE]))
                ),
            DbStorageHandler::class => autowire(MysqlHandler::class),
            DatabaseSetupService::class => autowire(MysqlSetup::class),
            ActionsInterface::class =>
                static fn(PathsContext $pathsContext) => new Actions(
                    new FileCache(FileSystem::buildPath($pathsContext[Path::CACHE], 'actions.cache')),
                    new YamlFileStorage(new FileHandler($pathsContext[Path::ACTIONS_FILE]))
                ),
            MimeTypesService::class =>
                static fn(PathsContext $pathsContext) => new MimeTypes(
                    new FileCache(FileSystem::buildPath($pathsContext[Path::CACHE], 'mime.cache')),
                    new YamlFileStorage(new FileHandler($pathsContext[Path::MIMETYPES_FILE]))
                ),
            AclInterface::class => autowire(Acl::class)
                ->constructorParameter('actions', get(ActionsInterface::class)),
            ThemeContextInterface::class => autowire(ThemeContext::class)
                ->constructorParameter(
                    'basePath',
                    factory(static fn(PathsContext $p) => $p[Path::VIEW])
                )
                ->constructorParameter('baseUri', factory([UriContextInterface::class, 'getWebRoot']))
                ->constructorParameter('module', $module)
                ->constructorParameter('name', factory([Theme::class, 'getThemeName'])),
            ThemeIconsInterface::class => factory([ThemeIcons::class, 'loadIcons'])
                ->parameter(
                    'cache',
                    create(FileCache::class)
                        ->constructor(
                            factory(
                                static fn(PathsContext $p) => FileSystem::buildPath(
                                    $p[Path::CACHE],
                                    ThemeIcons::ICONS_CACHE_FILE
                                )
                            )
                        )
                ),
            ThemeInterface::class => autowire(Theme::class),
            TemplateInterface::class => autowire(Template::class)
                ->constructorParameter('base', factory(static fn(RouteContextData $r) => $r->controller)),
            DatabaseAuthService::class => autowire(DatabaseAuth::class),
            BrowserAuthService::class => autowire(BrowserAuth::class),
            LdapParams::class => factory([LdapParams::class, 'fromConfig']),
            LdapService::class => factory([LdapBase::class, 'factory']),
            LdapConnectionHandler::class => autowire(LdapConnection::class),
            LdapActionsService::class => autowire(LdapActions::class),
            LdapAuthService::class => autowire(LdapAuth::class),
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
            LoggerInterface::class => factory(function (ConfigDataInterface $configData, PathsContext $pathsContext) {
                $handlers = [];
                $handlers[] = new StreamHandler($pathsContext[Path::LOG_FILE]);

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
            LanguageInterface::class => autowire(Language::class)->constructorParameter(
                'localesPath',
                factory(
                    static fn(PathsContext $p) => $p[Path::LOCALES]
                )
            ),
            DatabaseInterface::class => autowire(Database::class),
            PhpMailerService::class => autowire(PhpMailerService::class),
            MailerInterface::class => factory([PhpMailerService::class, 'configure'])
                ->parameter(
                    'mailParams',
                    factory([Mail::class, 'getParamsFromConfig'])
                ),
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
            CryptPKIHandler::class => factory(static function (PathsContext $pathsContext) {
                $publicKeyPath = FileSystem::buildPath(
                    $pathsContext[Path::CONFIG],
                    CryptPKI::PUBLIC_KEY_FILE
                );

                $privateKeyPath = FileSystem::buildPath(
                    $pathsContext[Path::CONFIG],
                    CryptPKI::PRIVATE_KEY_FILE
                );

                return new CryptPKI(
                    new RSA(),
                    new FileHandler($publicKeyPath, 'w'),
                    new FileHandler($privateKeyPath, 'w')
                );
            }),
            FileCacheService::class => create(FileCache::class),
            RequestBasedPasswordInterface::class => autowire(RequestBasedPassword::class),
            PhpExtensionCheckerService::class => create(PhpExtensionChecker::class),
            BackupFiles::class => factory(static function (PathsContext $pathsContext) {
                $hash = BackupFiles::buildHash();
                $appBackupFile = new BackupFileDto(BackupType::app, $hash, $pathsContext[Path::BACKUP], 'tar');
                $dbBackupFile = new BackupFileDto(BackupType::db, $hash, $pathsContext[Path::BACKUP], 'sql');

                return new BackupFiles($appBackupFile, $dbBackupFile);
            }),
            'backup.dbArchiveHandler' => autowire(ArchiveHandler::class)
                ->constructorParameter(
                    'archive',
                    factory(
                        static fn(BackupFiles $backupFiles) => (string)$backupFiles->getDbBackupFile()
                    )
                ),
            'backup.appArchiveHandler' => autowire(ArchiveHandler::class)
                ->constructorParameter(
                    'archive',
                    factory(
                        static fn(BackupFiles $backupFiles) => (string)$backupFiles->getAppBackupFile()
                    )
                )
            ,
            BackupFileService::class => autowire(BackupFile::class)
                ->constructorParameter(
                    'dbBackupFile',
                    create(FileHandler::class)
                        ->constructor(
                            factory(
                                static fn(PathsContext $pathsContext) => FileSystem::buildPath(
                                    $pathsContext[Path::BACKUP],
                                    'database.sql'
                                )
                            ),
                            'wb+'
                        )
                )
                ->constructorParameter('dbArchiveHandler', get('backup.dbArchiveHandler'))
                ->constructorParameter('appArchiveHandler', get('backup.appArchiveHandler')),
            RouteContextData::class => factory(static function (KleinRequest $request) {
                return RouteContext::getRouteContextData(Filter::getString($request->param('r', 'index/index')));
            }),
            SecureSessionService::class => autowire(SecureSession::class)
                ->constructorParameter(
                    'fileCache',
                    factory(
                        static function (
                            UuidCookieInterface $uuidCookie,
                            ConfigDataInterface $configData,
                            PathsContext        $pathsContext
                        ) {
                            return new FileCache(
                                SecureSession::getFileNameFrom(
                                    $uuidCookie,
                                    $configData->getPasswordSalt(),
                                    $pathsContext[Path::CACHE]
                                )
                            );
                        }
                    )->parameter('uuidCookie', factory([UuidCookie::class, 'factory']))
                ),
            CryptSessionHandler::class => autowire(CryptSessionHandler::class)
                ->constructorParameter('key', factory([SecureSessionService::class, 'getKey'])),
            SessionHandlerInterface::class => factory(
                function (ContainerInterface $c, ConfigDataInterface $configData) {
                    if ($configData->isEncryptSession()) {
                        return $c->get(CryptSessionHandler::class);
                    }

                    return null;
                }
            ),
            OutputHandlerInterface::class => create(OutputHandler::class),
            TemplateResolverInterface::class => autowire(TemplateResolver::class),
            UpgradeDatabase::class => autowire(UpgradeDatabase::class)
                ->constructorParameter(
                    'sqlPath',
                    factory(static fn(PathsContext $p) => $p[Path::SQL])
                ),
            AppLockHandler::class => create(AppLock::class)
                ->constructor(
                    factory(static fn(PathsContext $p) => FileSystem::buildPath($p[Path::CONFIG], '.lock'))
                )
        ];
    }

    private static function getPaths(string $rootPath): array
    {
        $appPath = FileSystem::buildPath($rootPath, 'app');
        $sqlPath = FileSystem::buildPath($rootPath, 'schemas');
        $resourcesPath = FileSystem::buildPath($appPath, 'resources');
        $configPath = getFromEnv('CONFIG_PATH', FileSystem::buildPath($appPath, 'config'));
        $logFile = getFromEnv('LOG_FILE', FileSystem::buildPath($configPath, 'syspass.log'));

        return [
            [Path::APP, $appPath],
            [Path::SQL, $sqlPath],
            [Path::PUBLIC, FileSystem::buildPath($rootPath, 'public')],
            [Path::XML_SCHEMA, FileSystem::buildPath($sqlPath, 'syspass.xsd')],
            [Path::RESOURCES, $resourcesPath],
            [Path::MODULES, FileSystem::buildPath($appPath, 'modules')],
            [Path::LOCALES, FileSystem::buildPath($appPath, 'locales')],
            [Path::BACKUP, FileSystem::buildPath($appPath, 'backup')],
            [Path::CACHE, FileSystem::buildPath($appPath, 'cache')],
            [Path::TMP, FileSystem::buildPath($appPath, 'temp')],
            [Path::CONFIG, $configPath],
            [Path::SQL_FILE, FileSystem::buildPath($sqlPath, 'dbstructure.sql')],
            [Path::LOG_FILE, $logFile],
            [
                Path::CONFIG_FILE,
                getFromEnv('CONFIG_FILE', FileSystem::buildPath($configPath, 'config.xml'))
            ],
            [
                Path::ACTIONS_FILE,
                getFromEnv('ACTIONS_FILE', FileSystem::buildPath($resourcesPath, 'actions.yaml'))
            ],
            [
                Path::MIMETYPES_FILE,
                getFromEnv(
                    'MIMETYPES_FILE',
                    FileSystem::buildPath($resourcesPath, 'mimetypes.yaml')
                )
            ],
        ];
    }
}
