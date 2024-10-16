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
use SP\Html\DataGrid\Action\DataGridActionHelp;
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
 * Class AccountGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class AccountGrid extends GridBase
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

        $grid->addDataAction(new DataGridActionHelp('help_account_search'));

        $grid->addDataAction($this->getViewAction());
        $grid->addDataAction($this->getDeleteAction());

        $grid->addDataAction(
            $this->getBulkEditAction()
                 ->setIsSelection(true),
            true
        );

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
        $gridTab->setId('tblAccounts');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Accounts'));

        return $gridTab;
    }

    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Name'));
        $gridHeader->addHeader(__('Client'));
        $gridHeader->addHeader(__('Category'));
        $gridHeader->addHeader(__('Owner'));
        $gridHeader->addHeader(__('Main Group'));

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
        $gridData->addDataRowSource('clientName');
        $gridData->addDataRowSource('categoryName');
        $gridData->addDataRowSource('userName');
        $gridData->addDataRowSource('userGroupName');
        $gridData->setData($this->queryResult);

        return $gridData;
    }

    private function getSearchAction(): DataGridActionSearch
    {
        $gridActionSearch = new DataGridActionSearch();
        $gridActionSearch->setId(AclActionsInterface::ACCOUNTMGR_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchAccount');
        $gridActionSearch->setTitle(__('Search for Account'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData(
            'action-route',
            $this->acl->getRouteFor(AclActionsInterface::ACCOUNTMGR_SEARCH)
        );

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    public function getViewAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::ACCOUNT_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('Account Details'));
        $gridAction->setTitle(__('Account Details'));
        $gridAction->setIcon($this->icons->view());
        $gridAction->setOnClickFunction($this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW));
        $gridAction->addData(
            'action-route',
            $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW)
        );

        return $gridAction;
    }

    private function getDeleteAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::ACCOUNTMGR_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Remove Account'));
        $gridAction->setTitle(__('Remove Account'));
        $gridAction->setIcon($this->icons->delete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData(
            'action-route',
            $this->acl->getRouteFor(AclActionsInterface::ACCOUNTMGR_DELETE)
        );

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    public function getBulkEditAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::ACCOUNTMGR_BULK_EDIT);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Bulk Update'));
        $gridAction->setTitle(__('Bulk Update'));
        $gridAction->setIcon($this->icons->edit());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData(
            'action-route',
            $this->acl->getRouteFor(AclActionsInterface::ACCOUNTMGR_BULK_EDIT)
        );

        return $gridAction;
    }
}
