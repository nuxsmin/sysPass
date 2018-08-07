<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Acl\Acl;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\ItemSearchData;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridPager;
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
     * @var \SP\Core\Acl\Acl
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
    protected function getPager(DataGridActionSearch $sourceAction)
    {
        $GridPager = new DataGridPager();
        $GridPager->setSourceAction($sourceAction);
        $GridPager->setOnClickFunction('appMgmt/nav');
        $GridPager->setLimitStart(0);
        $GridPager->setLimitCount($this->configData->getAccountCount());
        $GridPager->setIconPrev($this->icons->getIconNavPrev());
        $GridPager->setIconNext($this->icons->getIconNavNext());
        $GridPager->setIconFirst($this->icons->getIconNavFirst());
        $GridPager->setIconLast($this->icons->getIconNavLast());

        return $GridPager;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
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