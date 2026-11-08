<?php
declare(strict_types=1);
/**
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

namespace SP\Domain\Core;

/**
 * Interface ItemsTypeInterface para la definición de tipos de elementos
 *
 * @package SP\Core
 */
interface ItemsTypeInterface
{
    public const ITEM_CATEGORIES = 1;
    public const ITEM_CUSTOMERS = 2;
    public const ITEM_CUSTOMERS_USER = 52;
    public const ITEM_FILES = 3;
    public const ITEM_TAGS = 4;
    public const ITEM_USERS = 5;
    public const ITEM_GROUPS = 6;
    public const ITEM_PROFILES = 7;
    public const ITEM_ACCOUNTS = 8;
    public const ITEM_ACCOUNTS_USER = 58;
}
