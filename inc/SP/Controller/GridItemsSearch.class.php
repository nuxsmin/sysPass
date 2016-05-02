<?php
/**
 * Created by PhpStorm.
 * User: rdb
 * Date: 24/11/15
 * Time: 14:14
 */

namespace SP\Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\Template;
use SP\DataModel\ItemSearchData;
use SP\Html\DataGrid\DataGridPagerBase;
use SP\Html\DataGrid\DataGridPagerInterface;
use SP\Util\Checks;

/**
 * Class GridItemsSearch para construcción de clases que usen el Grid de búsqueda de registros
 *
 * @package SP\Controller
 */
abstract class GridItemsSearch extends Controller
{
    /**
     * @var Grids
     */
    protected $_grids;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('isDemo', Checks::demoIsEnabled());

        $this->_grids = new Grids();
        $this->_grids->setQueryTimeStart(microtime());
    }

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
        $Pager->setOnClickArgs($SearchData->getLimitCount());
        $Pager->setFilterOn($SearchData->getSeachString() !== '');
    }
}