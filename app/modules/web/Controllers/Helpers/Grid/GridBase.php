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

namespace SP\Modules\Web\Controllers\Helpers\Grid;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\ItemSearchData;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Html\DataGrid\Layout\DataGridPager;
use SP\Modules\Web\Controllers\Helpers\HelperBase;

/**
 * Class GridBase
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
abstract class GridBase extends HelperBase implements GridInterface
{
    /**
     * @var float
     */
    protected $queryTimeStart;
    /**
     * @var ThemeIcons
     */
    protected $icons;
    /**
     * @var Acl
     */
    protected $acl;

    /**
     * Actualizar los datos del paginador
     *
     * @param DataGridInterface $dataGrid
     * @param ItemSearchData    $itemSearchData
     *
     * @return DataGridInterface
     */
    public function updatePager(DataGridInterface $dataGrid, ItemSearchData $itemSearchData)
    {
        $dataGrid->getPager()
            ->setLimitStart($itemSearchData->getLimitStart())
            ->setLimitCount($itemSearchData->getLimitCount())
            ->setFilterOn($itemSearchData->getSeachString() !== '');

        $dataGrid->updatePager();

        return $dataGrid;
    }

    /**
     * Devolver el paginador por defecto
     *
     * @param DataGridActionSearch $sourceAction
     *
     * @return DataGridPager
     */
    final protected function getPager(DataGridActionSearch $sourceAction)
    {
        $gridPager = new DataGridPager();
        $gridPager->setSourceAction($sourceAction);
        $gridPager->setOnClickFunction('appMgmt/nav');
        $gridPager->setLimitStart(0);
        $gridPager->setLimitCount($this->configData->getAccountCount());
        $gridPager->setIconPrev($this->icons->getIconNavPrev());
        $gridPager->setIconNext($this->icons->getIconNavNext());
        $gridPager->setIconFirst($this->icons->getIconNavFirst());
        $gridPager->setIconLast($this->icons->getIconNavLast());

        return $gridPager;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final protected function initialize()
    {
        $this->queryTimeStart = microtime(true);
        $this->acl = $this->dic->get(Acl::class);
        $this->icons = $this->view->getTheme()->getIcons();
    }

    /**
     * @return DataGridInterface
     */
    protected abstract function getGridLayout(): DataGridInterface;

    /**
     * @return DataGridHeader
     */
    protected abstract function getHeader(): DataGridHeader;

    /**
     * @return DataGridData
     */
    protected abstract function getData(): DataGridData;
}