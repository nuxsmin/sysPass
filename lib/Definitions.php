<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

use Monolog\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\Actions;
use SP\Core\Application;
use SP\Core\Context\ContextFactory;
use SP\Core\Context\ContextInterface;
use SP\Core\Crypt\CryptPKI;
use SP\Core\Crypt\CSRF;
use SP\Core\Language;
use SP\Core\LanguageInterface;
use SP\Core\MimeTypes;
use SP\Core\MimeTypesInterface;
use SP\Core\UI\Theme;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Config\ConfigInterface;
use SP\Domain\Config\In\ConfigDataInterface;
use SP\Domain\Config\Services\ConfigBackupService;
use SP\Domain\Config\Services\ConfigFileService;
use SP\Domain\Providers\MailerInterface;
use SP\Domain\Providers\MailProviderInterface;
use SP\Http\Client;
use SP\Http\Request;
use SP\Http\RequestInterface;
use SP\Infrastructure\Database\Database;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\DBStorageInterface;
use SP\Infrastructure\Database\MySQLHandler;
use SP\Infrastructure\File\FileCache;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\XmlHandler;
use SP\Mvc\View\Template;
use SP\Mvc\View\TemplateInterface;
use SP\Providers\Auth\AuthProvider;
use SP\Providers\Auth\AuthProviderInterface;
use SP\Providers\Auth\Browser\BrowserAuth;
use SP\Providers\Auth\Browser\BrowserAuthInterface;
use SP\Providers\Auth\Database\DatabaseAuth;
use SP\Providers\Auth\Database\DatabaseAuthInterface;
use SP\Providers\Auth\Ldap\Ldap;
use SP\Providers\Auth\Ldap\LdapAuth;
use SP\Providers\Auth\Ldap\LdapAuthInterface;
use SP\Providers\Auth\Ldap\LdapParams;
use SP\Providers\Mail\MailProvider;
use SP\Providers\Mail\PhpMailerWrapper;
use function DI\autowire;
use function DI\create;
use function DI\factory;
use function DI\get;

return [
    RequestInterface::class                          => create(Request::class)
        ->constructor(\Klein\Request::createFromGlobals(), autowire(CryptPKI::class)),
    ContextInterface::class                          =>
        static fn() => ContextFactory::getForModule(APP_MODULE),
    ConfigInterface::class                           => create(ConfigFileService::class)
        ->constructor(
            create(XmlHandler::class)
                ->constructor(create(FileHandler::class)->constructor(CONFIG_FILE)),
            create(FileCache::class)->constructor(ConfigFileService::CONFIG_CACHE_FILE),
            get(ContextInterface::class),
            autowire(ConfigBackupService::class)->lazy()
        ),
    ConfigDataInterface::class                       =>
        static fn(ConfigInterface $config) => $config->getConfigData(),
    DBStorageInterface::class                        => create(MySQLHandler::class)
        ->constructor(factory([DatabaseConnectionData::class, 'getFromConfig'])),
    Actions::class                                   =>
        static fn() => new Actions(
            new FileCache(Actions::ACTIONS_CACHE_FILE),
            new XmlHandler(new FileHandler(ACTIONS_FILE))
        ),
    MimeTypesInterface::class                        =>
        static fn() => new MimeTypes(
            new FileCache(MimeTypes::MIME_CACHE_FILE),
            new XmlHandler(new FileHandler(MIMETYPES_FILE))
        ),
    Acl::class                                       => autowire(Acl::class)
        ->constructorParameter('actions', get(Actions::class)),
    ThemeInterface::class                            => autowire(Theme::class)
        ->constructorParameter('module', APP_MODULE)
        ->constructorParameter(
            'fileCache',
            create(FileCache::class)->constructor(Theme::ICONS_CACHE_FILE)
        ),
    TemplateInterface::class                         => autowire(Template::class),
    DatabaseAuthInterface::class                     => autowire(DatabaseAuth::class),
    BrowserAuthInterface::class                      => autowire(BrowserAuth::class),
    LdapAuthInterface::class                         => autowire(LdapAuth::class)
        ->constructorParameter(
            'ldap',
            factory([Ldap::class, 'factory'])
                ->parameter('ldapParams', factory([LdapParams::class, 'getFrom']))
        ),
    AuthProviderInterface::class                     => static function (
        ContainerInterface $c,
        ConfigDataInterface $configData
    ) {
        $provider = new AuthProvider($c->get(Application::class), $c->get(DatabaseAuthInterface::class));

        if ($configData->isLdapEnabled()) {
            $provider->withLdapAuth($c->get(LdapAuthInterface::class));
        }

        if ($configData->isAuthBasicEnabled()) {
            $provider->withBrowserAuth($c->get(BrowserAuthInterface::class));
        }

        return $provider;
    },
    Logger::class                                    => create(Logger::class)
        ->constructor('syspass'),
    \GuzzleHttp\Client::class                        => create(GuzzleHttp\Client::class)
        ->constructor(factory([Client::class, 'getOptions'])),
    CSRF::class                                      => autowire(CSRF::class),
    LanguageInterface::class                         => autowire(Language::class),
    DatabaseInterface::class                         => autowire(Database::class),
    MailProviderInterface::class                     => autowire(MailProvider::class),
    MailerInterface::class                           => autowire(PhpMailerWrapper::class)->constructor(
        create(PHPMailer::class)->constructor(true)
    ),
    'SP\Domain\Account\*ServiceInterface'            => autowire('SP\Domain\Account\Services\*Service'),
    'SP\Domain\Account\In\*RepositoryInterface'      => autowire('SP\Infrastructure\Account\Repositories\*Repository'),
    'SP\Domain\Category\*ServiceInterface'           => autowire('SP\Domain\Category\Services\*Service'),
    'SP\Domain\Category\In\*RepositoryInterface'     => autowire('SP\Infrastructure\Category\Repositories\*Repository'),
    'SP\Domain\Client\*ServiceInterface'             => autowire('SP\Domain\Client\Services\*Service'),
    'SP\Domain\Client\In\*RepositoryInterface'       => autowire('SP\Infrastructure\Client\Repositories\*Repository'),
    'SP\Domain\Tag\*ServiceInterface'                => autowire('SP\Domain\Tag\Services\*Service'),
    'SP\Domain\Tag\In\*RepositoryInterface'          => autowire('SP\Infrastructure\Tag\Repositories\*Repository'),
    'SP\Domain\User\*ServiceInterface'               => autowire('SP\Domain\User\Services\*Service'),
    'SP\Domain\User\In\*RepositoryInterface'         => autowire('SP\Infrastructure\User\Repositories\*Repository'),
    'SP\Domain\Auth\*ServiceInterface'               => autowire('SP\Domain\Auth\Services\*Service'),
    'SP\Domain\Auth\In\*RepositoryInterface'         => autowire('SP\Infrastructure\Auth\Repositories\*Repository'),
    'SP\Domain\CustomField\*ServiceInterface'        => autowire('SP\Domain\CustomField\Services\*Service'),
    'SP\Domain\CustomField\In\*RepositoryInterface'  => autowire(
        'SP\Infrastructure\CustomField\Repositories\*Repository'
    ),
    'SP\Domain\Export\*ServiceInterface'             => autowire('SP\Domain\Export\Services\*Service'),
    'SP\Domain\Import\*ServiceInterface'             => autowire('SP\Domain\Import\Services\*Service'),
    'SP\Domain\Install\*ServiceInterface'            => autowire('SP\Domain\Install\Services\*Service'),
    'SP\Domain\Crypt\*ServiceInterface'              => autowire('SP\Domain\Crypt\Services\*Service'),
    'SP\Domain\Plugin\*ServiceInterface'             => autowire('SP\Domain\Plugin\Services\*Service'),
    'SP\Domain\ItemPreset\*ServiceInterface'         => autowire('SP\Domain\ItemPreset\Services\*Service'),
    'SP\Domain\ItemPreset\In\*RepositoryInterface'   => autowire(
        'SP\Infrastructure\ItemPreset\Repositories\*Repository'
    ),
    'SP\Domain\Notification\*ServiceInterface'       => autowire('SP\Domain\Notification\Services\*Service'),
    'SP\Domain\Notification\In\*RepositoryInterface' => autowire(
        'SP\Infrastructure\Notification\Repositories\*Repository'
    ),
    'SP\Domain\Security\*ServiceInterface'           => autowire('SP\Domain\Security\Services\*Service'),
    'SP\Domain\Security\In\*RepositoryInterface'     => autowire(
        'SP\Infrastructure\Security\Repositories\*Repository'
    ),
    'SP\Domain\Config\*ServiceInterface'             => autowire('SP\Domain\Config\Services\*Service'),
    'SP\Domain\Config\In\*RepositoryInterface'       => autowire(
        'SP\Infrastructure\Config\Repositories\*Repository'
    ),
    'SP\Domain\Plugin\In\*RepositoryInterface'       => autowire(
        'SP\Infrastructure\Plugin\Repositories\*Repository'
    ),
];