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
use SP\Core\Acl\ActionsInterface;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridTab;
use SP\Storage\Database\QueryResult;

/**
 * Class PublicLinkGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class PublicLinkGrid extends GridBase
{
    /**
     * @var QueryResult
     */
    private $queryResult;

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

        $grid->setDataActions($this->getSearchAction());
        $grid->setPager($this->getPager($searchAction));

        $grid->setDataActions($this->getCreateAction());
        $grid->setDataActions($this->getViewAction());
        $grid->setDataActions($this->getRefreshAction());

        $deleteAction = $this->getDeleteAction();

        $grid->setDataActions($deleteAction);
        $grid->setDataActions($deleteAction, true);

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
        $gridTab->setId('tblLinks');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Enlaces'));

        return $gridTab;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Cuenta'));
        $gridHeader->addHeader(__('Cliente'));
        $gridHeader->addHeader(__('Fecha Creación'));
        $gridHeader->addHeader(__('Fecha Caducidad'));
        $gridHeader->addHeader(__('Usuario'));
        $gridHeader->addHeader(__('Notificar'));
        $gridHeader->addHeader(__('Visitas'));

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
        $gridData->addDataRowSource('getDateAddFormat', true);
        $gridData->addDataRowSource('getDateExpireFormat', true);
        $gridData->addDataRowSource('userLogin');
        $gridData->addDataRowSource('getNotifyString', true);
        $gridData->addDataRowSource('getCountViewsString', true);
        $gridData->setData($this->queryResult);

        return $gridData;
    }

    /**
     * @return DataGridActionSearch
     */
    private function getSearchAction()
    {
        // Grid Actions
        $gridActionSearch = new DataGridActionSearch();
        $gridActionSearch->setId(ActionsInterface::PUBLICLINK_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchLink');
        $gridActionSearch->setTitle(__('Buscar Enlace'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::PUBLICLINK_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Nuevo Enlace'));
        $gridAction->setTitle(__('Nuevo Enlace'));
        $gridAction->setIcon($this->icons->getIconAdd());
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_CREATE));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getViewAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::PUBLICLINK_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('Ver Enlace'));
        $gridAction->setTitle(__('Ver Enlace'));
        $gridAction->setIcon($this->icons->getIconView());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_VIEW));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getRefreshAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::PUBLICLINK_REFRESH);
        $gridAction->setName(__('Renovar Enlace'));
        $gridAction->setTitle(__('Renovar Enlace'));
        $gridAction->setIcon($this->icons->getIconRefresh());
        $gridAction->setOnClickFunction('link/refresh');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_REFRESH));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::PUBLICLINK_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Eliminar Enlace'));
        $gridAction->setTitle(__('Eliminar Enlace'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_DELETE));

        return $gridAction;
    }
}