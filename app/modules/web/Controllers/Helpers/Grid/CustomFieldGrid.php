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
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\Action\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridTab;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Services\CustomField\CustomFieldDefService;
use SP\Storage\Database\QueryResult;

/**
 * Class CustomFieldGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class CustomFieldGrid extends GridBase
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

        $grid->addDataAction($this->getCreateAction());
        $grid->addDataAction($this->getEditAction());
        $grid->addDataAction($this->getDeleteAction());
        $grid->addDataAction(
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
        $gridTab->setId('tblCustomFields');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Campos Personalizados'));

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
        $gridHeader->addHeader(__('Módulo'));
        $gridHeader->addHeader(__('Tipo'));
        $gridHeader->addHeader(__('Propiedades'));

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
        $gridData->addDataRowSource('moduleId', false, function ($value) {
            return CustomFieldDefService::getFieldModuleById($value);
        });
        $gridData->addDataRowSource('typeName');
        $gridData->addDataRowSourceWithIcon('isEncrypted', $this->icons->getIconEditPass()->setTitle(__('Encriptado')));
        $gridData->setData($this->queryResult);

        return $gridData;
    }

    /**
     * @return \SP\Html\DataGrid\Action\DataGridActionSearch
     */
    private function getSearchAction()
    {
        // Grid Actions
        $gridActionSearch = new DataGridActionSearch();
        $gridActionSearch->setId(ActionsInterface::CUSTOMFIELD_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchCustomField');
        $gridActionSearch->setTitle(__('Buscar Campo'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::CUSTOMFIELD_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return \SP\Html\DataGrid\Action\DataGridAction
     */
    private function getCreateAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::CUSTOMFIELD_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Nuevo Campo'));
        $gridAction->setTitle(__('Nuevo Campo'));
        $gridAction->setIcon($this->icons->getIconAdd());
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::CUSTOMFIELD_CREATE));

        return $gridAction;
    }

    /**
     * @return \SP\Html\DataGrid\Action\DataGridAction
     */
    private function getEditAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::CUSTOMFIELD_EDIT);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Editar Campo'));
        $gridAction->setTitle(__('Editar Campo'));
        $gridAction->setIcon($this->icons->getIconEdit());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::CUSTOMFIELD_EDIT));

        return $gridAction;
    }

    /**
     * @return \SP\Html\DataGrid\Action\DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::CUSTOMFIELD_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Eliminar Campo'));
        $gridAction->setTitle(__('Eliminar Campo'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::CUSTOMFIELD_DELETE));

        return $gridAction;
    }
}