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

use SP\Core\Context\ContextBase;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\UI\ThemeContextInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;
use SP\Domain\Storage\Ports\FileCacheService;
use SP\Html\Assets\FontIcon;
use SP\Html\Assets\IconInterface;
use SP\Infrastructure\File\FileException;
use SP\Util\FileSystemUtil;

use function SP\logger;
use function SP\processException;

/**
 * Class ThemeIcons
 *
 */
final class ThemeIcons implements ThemeIconsInterface
{
    public const CACHE_EXPIRE     = 86400;
    public const ICONS_CACHE_FILE = CACHE_PATH . DIRECTORY_SEPARATOR . 'icons.cache';

    /**
     * @var IconInterface[]
     */
    private array $icons = [];

    /**
     * @param Context $context
     * @param FileCacheService $cache
     * @param ThemeContextInterface $themeContext
     * @return ThemeIconsInterface
     * @throws InvalidClassException
     * @throws FileException
     */
    public static function loadIcons(
        Context          $context,
        FileCacheService $cache,
        ThemeContextInterface $themeContext
    ): ThemeIconsInterface {
        try {
            if ($context->getAppStatus() !== ContextBase::APP_STATUS_RELOADED
                && $cache->isExpired(self::CACHE_EXPIRE)
            ) {
                return $cache->load();
                // logger('Loaded icons cache', 'INFO');
            }

            $icons = FileSystemUtil::require(
                FileSystemUtil::buildPath($themeContext->getFullPath(), 'inc', 'Icons.php'),
                ThemeIconsInterface::class
            );

            $cache->save($icons);

            logger('Saved icons cache', 'INFO');

            return $icons;
        } catch (FileException $e) {
            processException($e);

            throw $e;
        }
    }

    public function __call(string $name, ?array $arguments = null): IconInterface
    {
        return $this->getIconByName($name);
    }

    /**
     * @param string $name
     *
     * @return IconInterface
     */
    public function getIconByName(string $name): IconInterface
    {
        return $this->icons[$name] ?? new FontIcon($name, 'mdl-color-text--indigo-A200');
    }

    /**
     * @param string $alias
     * @param IconInterface $icon
     */
    public function addIcon(string $alias, IconInterface $icon): void
    {
        $this->icons[$alias] = $icon;
    }
}
