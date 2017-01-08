<?php
/**
 * Created by PhpStorm.
 * User: rdb
 * Date: 24/11/15
 * Time: 14:14
 */

namespace SP\Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Controller\Grids\Items;
use SP\Core\Template;
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