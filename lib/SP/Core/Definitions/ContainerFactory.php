<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\Container;
use DI\ContainerBuilder;
use DI\Definition\Source\DefinitionArray;
use DI\Definition\Source\ReflectionBasedAutowiring;
use SP\Domain\Common\Providers\Version;
use SP\Infrastructure\File\FileSystem;

final class ContainerFactory
{
    public static function getCompilationPath(string $cacheRoot, string $module): string
    {
        return FileSystem::buildPath(
            $cacheRoot,
            'container',
            Version::getVersionStringNormalized(),
            $module
        );
    }

    /**
     * @param array<string, mixed> $moduleDefinitions
     *
     * @return ContainerBuilder<Container>
     */
    public static function createBuilder(
        string $rootPath,
        string $module,
        array  $moduleDefinitions,
        ?string $compilationPath = null
    ): ContainerBuilder {
        $coreDefinitions = CoreDefinitions::getDefinitions($rootPath, $module);
        $domainDefinitions = DomainDefinitions::getDefinitions();
        $autowiring = new OptionalObjectDefaultAutowiring(
            new ReflectionBasedAutowiring(),
            array_fill_keys(
                array_keys($coreDefinitions + $domainDefinitions + $moduleDefinitions),
                true
            )
        );

        $builder = new ContainerBuilder();

        if ($compilationPath !== null) {
            $builder->enableCompilation($compilationPath);
            $builder->writeProxiesToFile(true, FileSystem::buildPath($compilationPath, 'proxies'));
        }

        return $builder->addDefinitions(
            $autowiring,
            new DefinitionArray($coreDefinitions, $autowiring),
            new DefinitionArray($domainDefinitions, $autowiring),
            new DefinitionArray($moduleDefinitions, $autowiring)
        );
    }
}
