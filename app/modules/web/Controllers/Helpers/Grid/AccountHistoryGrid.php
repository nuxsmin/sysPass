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
 * Class AccountHistoryGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class AccountHistoryGrid extends GridBase
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

        $grid->setDataActions($searchAction);
        $grid->setPager($this->getPager($searchAction));

        $grid->setDataActions($this->getRestoreAction());
        $grid->setDataActions($this->getDeleteAction());
        $grid->setDataActions(
            $this->getDeleteAction()
                ->setName(__('Eliminar Seleccionados'))
                ->setTitle(__('Eliminar Seleccionados')),
            true);

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
        $gridTab->setId('tblAccountsHistory');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Cuentas (H)'));

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
        $gridHeader->addHeader(__('Cliente'));
        $gridHeader->addHeader(__('Categoría'));
        $gridHeader->addHeader(__('Fecha'));
        $gridHeader->addHeader(__('Estado'));

        return $gridHeader;
    }

    /**
     * @return DataGridData
     */
    protected function getData(): DataGridData
    {
        // Grid Data
        $iconEdit = clone $this->icons->getIconEdit();
        $iconDelete = clone $this->icons->getIconDelete();

        // Grid Data
        $gridData = new DataGridData();
        $gridData->setDataRowSourceId('id');
        $gridData->addDataRowSource('name');
        $gridData->addDataRowSource('clientName');
        $gridData->addDataRowSource('categoryName');
        $gridData->addDataRowSource('date');
        $gridData->addDataRowSourceWithIcon('isModify', $iconEdit->setTitle(__('Modificada'))->setClass('opacity50'));
        $gridData->addDataRowSourceWithIcon('isDeleted', $iconDelete->setTitle(__('Eliminada'))->setClass('opacity50'));
        $gridData->setData($this->queryResult);

        return $gridData;
    }

    /**
     * @return DataGridActionSearch
     */
    private function getSearchAction()
    {
        $gridActionSearch = new DataGridActionSearch();
        $gridActionSearch->setId(ActionsInterface::ACCOUNTMGR_HISTORY_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchAccountHistory');
        $gridActionSearch->setTitle(__('Buscar Cuenta'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_HISTORY_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getRestoreAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ACCOUNTMGR_HISTORY_RESTORE);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Restaurar Cuenta'));
        $gridAction->setTitle(__('Restaurar Cuenta'));
        $gridAction->setIcon($this->icons->getIconRestore());
        $gridAction->setOnClickFunction('accountManager/restore');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_HISTORY_RESTORE));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ACCOUNTMGR_HISTORY_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Eliminar Cuenta'));
        $gridAction->setTitle(__('Eliminar Cuenta'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_HISTORY_DELETE));

        return $gridAction;
    }
}