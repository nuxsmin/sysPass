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

namespace SP\Modules\Web\Controllers\Helpers\Grid;

use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\Action\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridTab;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Infrastructure\Database\QueryResult;

use function SP\__;
use function SP\getElapsedTime;

/**
 * Class CategoryGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class CategoryGrid extends GridBase
{
    private ?QueryResult $queryResult = null;

    /**
     * @param QueryResult $queryResult
     *
     * @return DataGridInterface
     * @throws SPException
     */
    public function getGrid(QueryResult $queryResult): DataGridInterface
    {
        $this->queryResult = $queryResult;

        $grid = $this->getGridLayout();

        $searchAction = $this->getSearchAction();

        $grid->addDataAction($searchAction);
        $grid->setPager($this->getPager($searchAction));

        $grid->addDataAction($this->getCreateAction());
        $grid->addDataAction($this->getEditAction());
        $grid->addDataAction($this->getDeleteAction());
        $grid->addDataAction(
            $this->getDeleteAction()
                 ->setName(__('Delete Selected'))
                 ->setTitle(__('Delete Selected'))
                 ->setIsSelection(true),
            true
        );

        $grid->setTime(round(getElapsedTime($this->queryTimeStart), 5));

        return $grid;
    }

    /**
     * @return DataGridInterface
     */
    protected function getGridLayout(): DataGridInterface
    {
        // Grid
        $gridTab = new DataGridTab($this->theme);
        $gridTab->setId('tblCategories');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Categories'));

        return $gridTab;
    }

    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Name'));
        $gridHeader->addHeader(__('Description'));

        return $gridHeader;
    }

    /**
     * @return DataGridData
     */
    protected function getData(): DataGridData
    {
        // Grid Data
        $gridData = new DataGridData();
        $gridData->setDataRowSourceId('id');
        $gridData->addDataRowSource('name');
        $gridData->addDataRowSource('description');
        $gridData->setData($this->queryResult);

        return $gridData;
    }

    private function getSearchAction(): DataGridActionSearch
    {
        // Grid Actions
        $gridActionSearch = new DataGridActionSearch();
        $gridActionSearch->setId(AclActionsInterface::CATEGORY_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchCategory');
        $gridActionSearch->setTitle(__('Search for Category'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData(
            'action-route',
            $this->acl->getRouteFor(AclActionsInterface::CATEGORY_SEARCH)
        );

        return $gridActionSearch;
    }

    private function getCreateAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::CATEGORY_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('New Category'));
        $gridAction->setTitle(__('New Category'));
        $gridAction->setIcon($this->icons->add());
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData(
            'action-route',
            $this->acl->getRouteFor(AclActionsInterface::CATEGORY_CREATE)
        );

        return $gridAction;
    }

    private function getEditAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::CATEGORY_EDIT);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Edit Category'));
        $gridAction->setTitle(__('Edit Category'));
        $gridAction->setIcon($this->icons->edit());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData(
            'action-route',
            $this->acl->getRouteFor(AclActionsInterface::CATEGORY_EDIT)
        );

        return $gridAction;
    }

    private function getDeleteAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::CATEGORY_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Delete Category'));
        $gridAction->setTitle(__('Delete Category'));
        $gridAction->setIcon($this->icons->delete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData(
            'action-route',
            $this->acl->getRouteFor(AclActionsInterface::CATEGORY_DELETE)
        );

        return $gridAction;
    }
}
