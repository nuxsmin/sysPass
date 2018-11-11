<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

use Monolog\Logger;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
use SP\Core\Acl\Actions;
use SP\Core\Context\ContextInterface;
use SP\Core\MimeTypes;
use SP\Core\UI\Theme;
use SP\Core\UI\ThemeInterface;
use SP\Http\Request;
use SP\Services\Account\AccountAclService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Storage\Database\DBStorageInterface;
use SP\Storage\Database\MySQLHandler;
use SP\Storage\File\FileCache;
use SP\Storage\File\FileHandler;
use SP\Storage\File\XmlHandler;
use function DI\get;

return [
    Request::class => \DI\create(Request::class)
        ->constructor(\Klein\Request::createFromGlobals()),
    ContextInterface::class => function (ContainerInterface $c) {
        switch (APP_MODULE) {
            case 'web':
                return $c->get(\SP\Core\Context\SessionContext::class);
            default:
                return $c->get(\SP\Core\Context\StatelessContext::class);
        }
    },
    Config::class => function (ContainerInterface $c) {
        return new Config(new XmlHandler(new FileHandler(CONFIG_FILE)), $c->get(ContextInterface::class), $c);
    },
    ConfigData::class => function (Config $config) {
        return $config->getConfigData();
    },
    DBStorageInterface::class => \DI\create(MySQLHandler::class)
        ->constructor(\DI\factory([DatabaseConnectionData::class, 'getFromConfig'])),
    Actions::class => function (ContainerInterface $c) {
        return new Actions(
            new FileCache(Actions::ACTIONS_CACHE_FILE),
            new XmlHandler(new FileHandler(ACTIONS_FILE))
        );
    },
    MimeTypes::class => function (ContainerInterface $c) {
        return new MimeTypes(
            new FileCache(MimeTypes::MIME_CACHE_FILE),
            new XmlHandler(new FileHandler(MIMETYPES_FILE))
        );
    },
    Acl::class => \DI\autowire(Acl::class)
        ->constructorParameter('action', get(Actions::class)),
    ThemeInterface::class => \DI\autowire(Theme::class)
        ->constructorParameter('module', APP_MODULE)
        ->constructorParameter('fileCache', new FileCache(Theme::ICONS_CACHE_FILE)),
    PHPMailer::class => \DI\create(PHPMailer::class)
        ->constructor(true),
    Logger::class => \DI\create(Logger::class)
        ->constructor('syspass'),
    AccountAclService::class => \DI\autowire(AccountAclService::class),
    \GuzzleHttp\Client::class => \DI\create(GuzzleHttp\Client::class)
        ->constructor(\DI\factory([\SP\Http\Client::class, 'getOptions']))
];