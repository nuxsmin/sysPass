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

use SP\Core\Acl\Acl;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\Action\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridTab;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class FileGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class FileGrid extends GridBase
{
    private ?QueryResult $queryResult = null;

    /**
     * @param QueryResult $queryResult
     *
     * @return DataGridInterface
     */
    public function getGrid(QueryResult $queryResult): DataGridInterface
    {
        $this->queryResult = $queryResult;

        $grid = $this->getGridLayout();

        $searchAction = $this->getSearchAction();

        $grid->addDataAction($searchAction);
        $grid->setPager($this->getPager($searchAction));

        $grid->addDataAction($this->getViewAction());
        $grid->addDataAction($this->getDownloadAction());
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
        $gridTab = new DataGridTab($this->view->getTheme());
        $gridTab->setId('tblFiles');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Files'));

        return $gridTab;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Account'));
        $gridHeader->addHeader(__('Client'));
        $gridHeader->addHeader(__('Name'));
        $gridHeader->addHeader(__('Type'));
        $gridHeader->addHeader(__('Size'));

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
        $gridData->addDataRowSource('accountName');
        $gridData->addDataRowSource('clientName');
        $gridData->addDataRowSource('name');
        $gridData->addDataRowSource('type');
        $gridData->addDataRowSource(
            'size',
            false,
            function ($value) {
                return sprintf('%.2f KB', $value / 1000);
            }
        );
        $gridData->setData($this->queryResult);

        return $gridData;
    }

    /**
     * @return DataGridActionSearch
     */
    private function getSearchAction(): DataGridActionSearch
    {
        // Grid Actions
        $gridActionSearch = new DataGridActionSearch();
        $gridActionSearch->setId(AclActionsInterface::ACCOUNT_FILE_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchFile');
        $gridActionSearch->setTitle(__('Search for File'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::ACCOUNT_FILE_SEARCH)
        );

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getViewAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::ACCOUNT_FILE_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('View File'));
        $gridAction->setTitle(__('View File'));
        $gridAction->setIcon($this->icons->view());
        $gridAction->setOnClickFunction('file/view');
        $gridAction->setFilterRowSource('type', 'application/pdf');
        $gridAction->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::ACCOUNT_FILE_VIEW)
        );

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDownloadAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::ACCOUNT_FILE_DOWNLOAD);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('Download File'));
        $gridAction->setTitle(__('Download File'));
        $gridAction->setIcon($this->icons->download());
        $gridAction->setOnClickFunction('file/download');
        $gridAction->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::ACCOUNT_FILE_DOWNLOAD)
        );
        $gridAction->setRuntimeData(
            function ($dataItem) {
                return ['item-type' => $dataItem->type];
            }
        );

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::ACCOUNT_FILE_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Delete File'));
        $gridAction->setTitle(__('Delete File'));
        $gridAction->setIcon($this->icons->delete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::ACCOUNT_FILE_DELETE)
        );

        return $gridAction;
    }
}
