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

namespace SP\Modules\Web\Controllers\Helpers\Grid;

use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\UI\ThemeIcons;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Http\RequestInterface;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Html\DataGrid\Layout\DataGridPager;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Mvc\View\TemplateInterface;

/**
 * Class GridBase
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
abstract class GridBase extends HelperBase implements GridInterface
{
    protected float      $queryTimeStart;
    protected ThemeIcons $icons;
    protected Acl        $acl;

    public function __construct(
        Application $application,
        TemplateInterface $template,
        RequestInterface $request,
        AclInterface $acl
    ) {
        parent::__construct($application, $template, $request);

        $this->queryTimeStart = microtime(true);
        $this->acl = $acl;
        $this->icons = $this->view->getTheme()->getIcons();
    }


    /**
     * Actualizar los datos del paginador
     *
     * @param DataGridInterface $dataGrid
     * @param ItemSearchDto $itemSearchData
     *
     * @return DataGridInterface
     */
    public function updatePager(
        DataGridInterface $dataGrid,
        ItemSearchDto $itemSearchData
    ): DataGridInterface {
        $dataGrid->getPager()
                 ->setLimitStart($itemSearchData->getLimitStart())
                 ->setLimitCount($itemSearchData->getLimitCount())
                 ->setFilterOn(!empty($itemSearchData->getSeachString()));

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
    final protected function getPager(
        DataGridActionSearch $sourceAction
    ): DataGridPager {
        $gridPager = new DataGridPager();
        $gridPager->setSourceAction($sourceAction);
        $gridPager->setOnClickFunction('appMgmt/nav');
        $gridPager->setLimitStart(0);
        $gridPager->setLimitCount($this->configData->getAccountCount());
        $gridPager->setIconPrev($this->icons->navPrev());
        $gridPager->setIconNext($this->icons->navNext());
        $gridPager->setIconFirst($this->icons->navFirst());
        $gridPager->setIconLast($this->icons->navLast());

        return $gridPager;
    }

    /**
     * @return DataGridInterface
     */
    abstract protected function getGridLayout(): DataGridInterface;

    /**
     * @return DataGridHeader
     */
    abstract protected function getHeader(): DataGridHeader;

    /**
     * @return DataGridData
     */
    abstract protected function getData(): DataGridData;
}
