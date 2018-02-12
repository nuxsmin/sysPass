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

use function DI\get;
use function DI\object;

return [
    \Klein\Klein::class => object(\Klein\Klein::class),
    \SP\Core\Session\Session::class => object(\SP\Core\Session\Session::class),
    \SP\Config\Config::class => object(\SP\Config\Config::class)
        ->constructor(object(\SP\Storage\XmlHandler::class)
            ->constructor(CONFIG_FILE)),
    \SP\Core\Language::class => object(\SP\Core\Language::class),
    \SP\Config\ConfigData::class => function (\SP\Config\Config $config) {
        return $config->getConfigData();
    },
    \SP\Storage\DatabaseConnectionData::class => function (\SP\Config\ConfigData $configData) {
        return \SP\Storage\DatabaseConnectionData::getFromConfig($configData);
    },
    \SP\Storage\Database::class => object(\SP\Storage\Database::class)
        ->constructor(object(\SP\Storage\MySQLHandler::class)
            ->constructor(get(\SP\Storage\DatabaseConnectionData::class))),
    \SP\Core\Acl\Actions::class => object(\SP\Core\Acl\Actions::class)
        ->constructor(object(\SP\Storage\FileCache::class), object(\SP\Storage\XmlHandler::class)
            ->constructor(ACTIONS_FILE)),
    \SP\Core\Acl\Acl::class => object(\SP\Core\Acl\Acl::class)
        ->constructor(get(\SP\Core\Session\Session::class), get(\SP\Core\Acl\Actions::class)),
    \SP\Core\UI\Theme::class => object(\SP\Core\UI\Theme::class)
        ->constructor(APP_MODULE),
    \SP\Core\Events\EventDispatcher::class => object(\SP\Core\Events\EventDispatcher::class),
    \SP\Log\Log::class => object(\SP\Log\Log::class)->scope(\DI\Scope::PROTOTYPE)
];