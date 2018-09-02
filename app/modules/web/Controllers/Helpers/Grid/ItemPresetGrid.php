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
use SP\Services\ItemPreset\ItemPresetInterface;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountDefaultPermissionGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class ItemPresetGrid extends GridBase
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

        $grid->setDataActions($this->getCreatePermissionAction(), true);
        $grid->setDataActions($this->getCreatePrivateAction(), true);
        $grid->setDataActions($this->getCreateSessionTimeoutAction(), true);
        $grid->setDataActions($this->getCreateAccountPasswordAction(), true);
        $grid->setDataActions($this->getEditAction());
        $grid->setDataActions($this->getDeleteAction());
        $grid->setDataActions($this->getDeleteAction()
            ->setTitle(__('Eliminar Seleccionados'))
            ->setName(__('Eliminar Seleccionados')), true);

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
        $gridTab->setId('tblItemPreset');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Valores Predeterminados'));

        return $gridTab;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Tipo'));
        $gridHeader->addHeader(__('Usuario'));
        $gridHeader->addHeader(__('Grupo'));
        $gridHeader->addHeader(__('Perfil'));
        $gridHeader->addHeader(__('Prioridad'));
        $gridHeader->addHeader(__('Forzado'));

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
        $gridData->addDataRowSource('type');
        $gridData->addDataRowSource('userName');
        $gridData->addDataRowSource('userGroupName');
        $gridData->addDataRowSource('userProfileName');
        $gridData->addDataRowSource('priority');
        $gridData->addDataRowSourceWithIcon('fixed', $this->icons->getIconEnabled());
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
        $gridActionSearch->setId(ActionsInterface::ITEMPRESET_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchItemPreset');
        $gridActionSearch->setTitle(__('Buscar Valor'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::ITEMPRESET_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getCreatePermissionAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Valor de Permiso'));
        $gridAction->setTitle(__('Nuevo Valor de Permiso'));

        $icon = clone $this->icons->getIconAdd();

        $gridAction->setIcon($icon->setIcon('add_circle'));
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');

        $route = Acl::getActionRoute(ActionsInterface::ITEMPRESET_CREATE) . '/' . ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION;

        $gridAction->addData('action-route', $route);

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getCreatePrivateAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Valor de Cuenta Privada'));
        $gridAction->setTitle(__('Nuevo Valor de Cuenta Privada'));

        $icon = clone $this->icons->getIconAdd();

        $gridAction->setIcon($icon->setIcon('add_circle'));
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');

        $route = Acl::getActionRoute(ActionsInterface::ITEMPRESET_CREATE) . '/' . ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE;

        $gridAction->addData('action-route', $route);

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateSessionTimeoutAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Valor de Timeout de Sesión'));
        $gridAction->setTitle(__('Nuevo Valor de Timeout de Sesión'));

        $icon = clone $this->icons->getIconAdd();

        $gridAction->setIcon($icon->setIcon('add_circle'));
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');

        $route = Acl::getActionRoute(ActionsInterface::ITEMPRESET_CREATE) . '/' . ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT;

        $gridAction->addData('action-route', $route);

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateAccountPasswordAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Valor de Clave de Cuentas'));
        $gridAction->setTitle(__('Nuevo Valor de Clave de Cuentas'));

        $icon = clone $this->icons->getIconAdd();

        $gridAction->setIcon($icon->setIcon('add_circle'));
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');

        $route = Acl::getActionRoute(ActionsInterface::ITEMPRESET_CREATE) . '/' . ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD;

        $gridAction->addData('action-route', $route);

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getEditAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_EDIT);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Editar Valor'));
        $gridAction->setTitle(__('Editar Valor'));
        $gridAction->setIcon($this->icons->getIconEdit());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ITEMPRESET_EDIT));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Eliminar Valor'));
        $gridAction->setTitle(__('Eliminar Valor'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ITEMPRESET_DELETE));

        return $gridAction;
    }
}