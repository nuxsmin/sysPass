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
 * Class PublicLinkGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class PublicLinkGrid extends GridBase
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

        $grid->addDataAction($this->getCreateAction());
        $grid->addDataAction($this->getViewAction());
        $grid->addDataAction($this->getRefreshAction());
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
        $gridTab->setId('tblLinks');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Links'));

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
        $gridHeader->addHeader(__('Creation Date'));
        $gridHeader->addHeader(__('Expiry Date '));
        $gridHeader->addHeader(__('User'));
        $gridHeader->addHeader(__('Notify'));
        $gridHeader->addHeader(__('Visits'));

        return $gridHeader;
    }

    /**
     * @throws SPException
     */
    protected function getData(): DataGridData
    {
        // Grid Data
        $gridData = new DataGridData();
        $gridData->setDataRowSourceId('id');
        $gridData->addDataRowSource('accountName');
        $gridData->addDataRowSource('clientName');
        $gridData->addDataRowSource('getDateAddFormat', true);
        $gridData->addDataRowSource('getDateExpireFormat', true);
        $gridData->addDataRowSource('userLogin');
        $gridData->addDataRowSource(
            'notify',
            false,
            function ($value) {
                return $value ? __('ON') : __('OFF');
            }
        );
        $gridData->addDataRowSource('getCountViewsString', true);
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
        $gridActionSearch->setId(AclActionsInterface::PUBLICLINK_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchLink');
        $gridActionSearch->setTitle(__('Search for Link'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::PUBLICLINK_SEARCH)
        );

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::PUBLICLINK_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('New Link'));
        $gridAction->setTitle(__('New Link'));
        $gridAction->setIcon($this->icons->add());
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::PUBLICLINK_CREATE)
        );

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getViewAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::PUBLICLINK_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('View Link'));
        $gridAction->setTitle(__('View Link'));
        $gridAction->setIcon($this->icons->view());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::PUBLICLINK_VIEW)
        );

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getRefreshAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::PUBLICLINK_REFRESH);
        $gridAction->setName(__('Renew Link'));
        $gridAction->setTitle(__('Renew Link'));
        $gridAction->setIcon($this->icons->refresh());
        $gridAction->setOnClickFunction('link/refresh');
        $gridAction->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::PUBLICLINK_REFRESH)
        );

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::PUBLICLINK_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Delete Link'));
        $gridAction->setTitle(__('Delete Link'));
        $gridAction->setIcon($this->icons->delete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::PUBLICLINK_DELETE)
        );

        return $gridAction;
    }
}
