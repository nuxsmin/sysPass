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

namespace SP\Core\Definitions;

use function DI\autowire;

/**
 * Class DomainDefinitions
 */
final class DomainDefinitions
{
    public static function getDefinitions(): array
    {
        return [
            'SP\Domain\Account\Ports\*ServiceInterface'         => autowire('SP\Domain\Account\Services\*Service'),
            'SP\Domain\Account\Adapters\*AdapterInterface'      => autowire('SP\Domain\Account\Adapters\*Adapter'),
            'SP\Domain\Account\Ports\*RepositoryInterface'      => autowire(
                'SP\Infrastructure\Account\Repositories\*Repository'
            ),
            'SP\Domain\Category\Ports\*ServiceInterface'        => autowire('SP\Domain\Category\Services\*Service'),
            'SP\Domain\Category\Adapters\*AdapterInterface'     => autowire('SP\Domain\Category\Out\*Adapter'),
            'SP\Domain\Category\Ports\*RepositoryInterface'     => autowire(
                'SP\Infrastructure\Category\Repositories\*Repository'
            ),
            'SP\Domain\Client\Ports\*ServiceInterface'          => autowire('SP\Domain\Client\Services\*Service'),
            'SP\Domain\Client\Adapters\*AdapterInterface'       => autowire('SP\Domain\Client\Out\*Adapter'),
            'SP\Domain\Client\Ports\*RepositoryInterface'       => autowire(
                'SP\Infrastructure\Client\Repositories\*Repository'
            ),
            'SP\Domain\Tag\Ports\*ServiceInterface'             => autowire('SP\Domain\Tag\Services\*Service'),
            'SP\Domain\Tag\Ports\*RepositoryInterface'          => autowire(
                'SP\Infrastructure\Tag\Repositories\*Repository'
            ),
            'SP\Domain\User\Ports\*ServiceInterface'            => autowire('SP\Domain\User\Services\*Service'),
            'SP\Domain\User\Ports\*RepositoryInterface'         => autowire(
                'SP\Infrastructure\User\Repositories\*Repository'
            ),
            'SP\Domain\Auth\Ports\*ServiceInterface'            => autowire('SP\Domain\Auth\Services\*Service'),
            'SP\Domain\Auth\Ports\*RepositoryInterface'         => autowire(
                'SP\Infrastructure\Auth\Repositories\*Repository'
            ),
            'SP\Domain\CustomField\Ports\*ServiceInterface'     => autowire('SP\Domain\CustomField\Services\*Service'),
            'SP\Domain\CustomField\Ports\*RepositoryInterface'  => autowire(
                'SP\Infrastructure\CustomField\Repositories\*Repository'
            ),
            'SP\Domain\Export\Ports\*ServiceInterface'          => autowire('SP\Domain\Export\Services\*Service'),
            'SP\Domain\Import\Ports\*ServiceInterface'          => autowire('SP\Domain\Import\Services\*Service'),
            'SP\Domain\Install\Ports\*ServiceInterface'         => autowire('SP\Domain\Install\Services\*Service'),
            'SP\Domain\Crypt\Ports\*ServiceInterface'           => autowire('SP\Domain\Crypt\Services\*Service'),
            'SP\Domain\Plugin\Ports\*ServiceInterface'          => autowire('SP\Domain\Plugin\Services\*Service'),
            'SP\Domain\ItemPreset\Ports\*ServiceInterface'      => autowire('SP\Domain\ItemPreset\Services\*Service'),
            'SP\Domain\ItemPreset\Ports\*RepositoryInterface'   => autowire(
                'SP\Infrastructure\ItemPreset\Repositories\*Repository'
            ),
            'SP\Domain\Notification\Ports\*ServiceInterface'    => autowire('SP\Domain\Notification\Services\*Service'),
            'SP\Domain\Notification\Ports\*RepositoryInterface' => autowire(
                'SP\Infrastructure\Notification\Repositories\*Repository'
            ),
            'SP\Domain\Security\Ports\*ServiceInterface'        => autowire('SP\Domain\Security\Services\*Service'),
            'SP\Domain\Security\Ports\*RepositoryInterface'     => autowire(
                'SP\Infrastructure\Security\Repositories\*Repository'
            ),
            'SP\Domain\Config\Ports\*ServiceInterface'          => autowire('SP\Domain\Config\Services\*Service'),
            'SP\Domain\Config\Ports\*RepositoryInterface'       => autowire(
                'SP\Infrastructure\Config\Repositories\*Repository'
            ),
            'SP\Domain\Plugin\Ports\*RepositoryInterface'       => autowire(
                'SP\Infrastructure\Plugin\Repositories\*Repository'
            ),
        ];
    }
}
