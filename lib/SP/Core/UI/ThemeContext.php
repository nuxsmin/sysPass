<?php
/*
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

namespace SP\Core\UI;

use Directory;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Infrastructure\File\FileSystem;

/**
 * Class ThemeContext
 */
final class ThemeContext implements ThemeContextInterface
{

    private string $fullPath;
    private string $path;
    private string $viewsPath;
    private string $uri;

    public function __construct(
        string                  $basePath,
        string                  $baseUri,
        private readonly string $module,
        private readonly string $name
    ) {
        $this->fullPath = FileSystem::buildPath($basePath, $name);
        $this->path = FileSystem::buildPath(str_replace(APP_ROOT, '', $basePath), $name);
        $this->viewsPath = FileSystem::buildPath($this->fullPath, 'views');
        $this->uri = sprintf(
            '%s/app/modules/%s/themes/%s',
            $baseUri,
            $module,
            $name
        );
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getViewsPath(): string
    {
        return $this->viewsPath;
    }

    public function getViewsDirectory(): Directory
    {
        return dir($this->viewsPath);
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}
