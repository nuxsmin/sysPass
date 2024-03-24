<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Core\UI;

use SP\Domain\Core\Context\ContextInterface;
use SP\Html\Assets\IconInterface;
use SP\Infrastructure\File\FileCache;

/**
 * Class ThemeIcons
 *
 * @method IconInterface warning
 * @method IconInterface download
 * @method IconInterface clear
 * @method IconInterface play
 * @method IconInterface help
 * @method IconInterface publicLink
 * @method IconInterface back
 * @method IconInterface restore
 * @method IconInterface save
 * @method IconInterface up
 * @method IconInterface down
 * @method IconInterface viewPass
 * @method IconInterface copy
 * @method IconInterface clipboard
 * @method IconInterface email
 * @method IconInterface refresh
 * @method IconInterface editPass
 * @method IconInterface appAdmin
 * @method IconInterface accAdmin
 * @method IconInterface ldapUser
 * @method IconInterface disabled
 * @method IconInterface navPrev
 * @method IconInterface navNext
 * @method IconInterface navFirst
 * @method IconInterface navLast
 * @method IconInterface add
 * @method IconInterface view
 * @method IconInterface edit
 * @method IconInterface delete
 * @method IconInterface optional
 * @method IconInterface check
 * @method IconInterface search
 * @method IconInterface account
 * @method IconInterface group
 * @method IconInterface settings
 * @method IconInterface info
 * @method IconInterface enabled
 * @method IconInterface remove
 *
 */
interface ThemeIconsInterface
{
    public static function loadIcons(
        ContextInterface      $context,
        FileCache             $cache,
        ThemeContextInterface $themeContext
    ): ThemeIconsInterface;

    /**
     * @param string $name
     *
     * @return IconInterface
     */
    public function getIconByName(string $name): IconInterface;

    /**
     * @param string $alias
     * @param IconInterface $icon
     */
    public function addIcon(string $alias, IconInterface $icon): void;
}
