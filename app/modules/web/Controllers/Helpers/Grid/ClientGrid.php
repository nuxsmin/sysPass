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
 * Class ClientGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class ClientGrid extends GridBase
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
        $grid->setDataActions($this->getEditAction());

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
        $gridTab->setId('tblCustomers');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Clientes'));

        return $gridTab;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Nombre'));
        $gridHeader->addHeader(__('Descripción'));
        $gridHeader->addHeader(__('Global'));

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
        $gridData->addDataRowSource('isGlobal', false, function ($value) {
            return $value ? __('SI') : __('NO');
        });
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
        $gridActionSearch->setId(ActionsInterface::CLIENT_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchCustomer');
        $gridActionSearch->setTitle(__('Buscar Cliente'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::CLIENT_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::CLIENT_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Nuevo Cliente'));
        $gridAction->setTitle(__('Nuevo Cliente'));
        $gridAction->setIcon($this->icons->getIconAdd());
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::CLIENT_CREATE));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getEditAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::CLIENT_EDIT);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Editar Cliente'));
        $gridAction->setTitle(__('Editar Cliente'));
        $gridAction->setIcon($this->icons->getIconEdit());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::CLIENT_EDIT));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::CLIENT_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Eliminar Cliente'));
        $gridAction->setTitle(__('Eliminar Cliente'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::CLIENT_DELETE));

        return $gridAction;
    }
}