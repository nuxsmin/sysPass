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
 * Class FileGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class FileGrid extends GridBase
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

        $grid->setDataActions($this->getViewAction());
        $grid->setDataActions($this->getDownloadAction());
        $grid->setDataActions($this->getDeleteAction());
        $grid->setDataActions($this->getDeleteAction()->setTitle(__('Eliminar Seleccionados')), true);


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
        $gridTab->setTitle(__('Archivos'));

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
        $gridHeader->addHeader(__('Nombre'));
        $gridHeader->addHeader(__('Tipo'));
        $gridHeader->addHeader(__('Tamaño'));

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
        $gridData->addDataRowSource('size', false, function ($value) {
            return sprintf('%.2f KB', $value / 1000);
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
        $gridActionSearch->setId(ActionsInterface::ACCOUNT_FILE_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchFile');
        $gridActionSearch->setTitle(__('Buscar Archivo'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getViewAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ACCOUNT_FILE_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('Ver Archivo'));
        $gridAction->setTitle(__('Ver Archivo'));
        $gridAction->setIcon($this->icons->getIconView());
        $gridAction->setOnClickFunction('file/view');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_VIEW));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDownloadAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ACCOUNT_FILE_DOWNLOAD);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('Descargar Archivo'));
        $gridAction->setTitle(__('Descargar Archivo'));
        $gridAction->setIcon($this->icons->getIconDownload());
        $gridAction->setOnClickFunction('file/download');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DOWNLOAD));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ACCOUNT_FILE_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Eliminar Archivo'));
        $gridAction->setTitle(__('Eliminar Archivo'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DELETE));

        return $gridAction;
    }
}