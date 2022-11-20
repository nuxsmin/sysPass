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

namespace SP\Domain\Account\Search;

/**
 * Interface AccountSearchConstants
 */
interface AccountSearchConstants
{
    public const FILTER_OWNER              = 'owner';
    public const FILTER_MAIN_GROUP         = 'mainGroup';
    public const FILTER_CATEGORY_NAME      = 'categoryName';
    public const FILTER_FILE_NAME          = 'fileName';
    public const FILTER_ACCOUNT_ID         = 'accountId';
    public const FILTER_USER_NAME          = 'userName';
    public const FILTER_ACCOUNT_NAME_REGEX = 'accountNameRegex';
    public const FILTER_CLIENT_NAME        = 'clientName';
    public const FILTER_GROUP_NAME         = 'groupName';
    public const FILTER_CHAIN_AND          = 'and';
    public const FILTER_CHAIN_OR           = 'or';
    public const FILTER_IS_PRIVATE         = 'is:private';
    public const FILTER_NOT_PRIVATE        = 'not:private';
    public const FILTER_IS_EXPIRED         = 'is:expired';
    public const FILTER_NOT_EXPIRED        = 'is:expired';

    public const SORT_DIR_ASC  = 0;
    public const SORT_DIR_DESC = 1;
    public const SORT_CATEGORY = 2;
    public const SORT_DEFAULT  = 0;
    public const SORT_LOGIN    = 3;
    public const SORT_URL      = 4;
    public const SORT_NAME     = 1;
    public const SORT_CLIENT   = 5;
}
