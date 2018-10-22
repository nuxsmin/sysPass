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
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionHelp;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\Action\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridTab;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class AccountGrid extends GridBase
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

        $grid->addDataAction($searchAction);
        $grid->setPager($this->getPager($searchAction));

        $grid->addDataAction(new DataGridActionHelp('help_account_search'));

        $grid->addDataAction($this->getViewAction());
        $grid->addDataAction($this->getDeleteAction());

        $grid->addDataAction(
            $this->getBulkEditAction()
                ->setIsSelection(true),
            true);

        $grid->addDataAction(
            $this->getDeleteAction()
                ->setName(__('Eliminar Seleccionados'))
                ->setTitle(__('Eliminar Seleccionados'))
                ->setIsSelection(true),
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
        $gridTab->setId('tblAccounts');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Cuentas'));

        return $gridTab;
    }

    /**
     * @return \SP\Html\DataGrid\Layout\DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Nombre'));
        $gridHeader->addHeader(__('Cliente'));
        $gridHeader->addHeader(__('Categoría'));
        $gridHeader->addHeader(__('Propietario'));
        $gridHeader->addHeader(__('Grupo Principal'));

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

    /**
     * @return DataGridActionSearch
     */
    private function getSearchAction()
    {
        $gridActionSearch = new DataGridActionSearch();
        $gridActionSearch->setId(ActionsInterface::ACCOUNTMGR_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchAccount');
        $gridActionSearch->setTitle(__('Buscar Cuenta'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return \SP\Html\DataGrid\Action\DataGridAction
     */
    public function getViewAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ACCOUNT_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('Detalles de Cuenta'));
        $gridAction->setTitle(__('Detalles de Cuenta'));
        $gridAction->setIcon($this->icons->getIconView());
        $gridAction->setOnClickFunction(Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ACCOUNTMGR_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Eliminar Cuenta'));
        $gridAction->setTitle(__('Eliminar Cuenta'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_DELETE));

        return $gridAction;
    }

    /**
     * @return \SP\Html\DataGrid\Action\DataGridAction
     */
    public function getBulkEditAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ACCOUNTMGR_BULK_EDIT);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Actualización Masiva'));
        $gridAction->setTitle(__('Actualización Masiva'));
        $gridAction->setIcon($this->icons->getIconEdit());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_BULK_EDIT));

        return $gridAction;
    }
}