<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

/**
 * Created by PhpStorm.
 * User: rdb
 * Date: 24/11/15
 * Time: 14:14
 */

namespace SP\Controller;

defined('APP_ROOT') || die();

use SP\DataModel\ItemSearchData;
use SP\Html\DataGrid\DataGridPagerInterface;

/**
 * Class GridItemsSearch para construcción de clases que usen el Grid de búsqueda de registros
 *
 * @package SP\Controller
 */
abstract class GridItemsSearchController extends ControllerBase
{
    /**
     * @var mixed
     */
    protected $grids;

    /**
     * Actualizar los datos del paginador
     *
     * @param DataGridPagerInterface $Pager
     * @param ItemSearchData         $SearchData
     */
    protected function updatePager(DataGridPagerInterface $Pager, ItemSearchData $SearchData)
    {
        $Pager->setLimitStart($SearchData->getLimitStart());
        $Pager->setLimitCount($SearchData->getLimitCount());
        $Pager->setFilterOn($SearchData->getSeachString() !== '');
    }
}