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
use SP\Domain\Image\Ports\ImageService;
use SP\Infrastructure\Common\Repositories\SimpleRepository;
use SP\Infrastructure\File\FileSystem;

use function DI\autowire;
use function DI\factory;

/**
 * Class DomainDefinitions
 */
final class DomainDefinitions
{
    private const DOMAINS = [
        'Account',
        'Api',
        'Auth',
        'Category',
        'Client',
        'Config',
        'Crypt',
        'CustomField',
        'Export',
        'Import',
        'Install',
        'ItemPreset',
        'Notification',
        'Plugin',
        'Security',
        'Tag',
        'User',
    ];

    private const PORTS = [
        'Service' => 'SP\Domain\%s\Services\*',
        'Repository' => 'SP\Infrastructure\%s\Repositories\*',
        'Adapter' => 'SP\Domain\%s\Adapters\*',
        'Builder' => 'SP\Domain\%s\Services\Builders\*'
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
            )
        ];

        foreach (self::DOMAINS as $domain) {
            foreach (self::PORTS as $suffix => $target) {
                $key = sprintf('SP\Domain\%s\Ports\*%s', $domain, $suffix);

                if (!array_key_exists($key, $sources)) {
                    $sources[$key] = autowire(sprintf($target, $domain));
                }
            }
        }

        return [
            ...$sources
        ];
    }
}
