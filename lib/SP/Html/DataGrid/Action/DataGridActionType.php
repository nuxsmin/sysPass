<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Html\DataGrid\Action;

defined('APP_ROOT') || die();

/**
 * Class DataGridActionType para definir los tipos de acciones
 *
 * @package SP\Html\DataGrid
 */
interface DataGridActionType
{
    const MENUBAR_ITEM = 1;
    const VIEW_ITEM = 2;
    const EDIT_ITEM = 3;
    const DELETE_ITEM = 4;
    const SEARCH_ITEM = 5;
    const SELECT_ITEM = 6;
    const HELP_ITEM = 7;
}