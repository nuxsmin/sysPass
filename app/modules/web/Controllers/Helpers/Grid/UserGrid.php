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
use SP\Html\Assets\FontIcon;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridTab;
use SP\Storage\Database\QueryResult;

/**
 * Class UserGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class UserGrid extends GridBase
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

        $grid->setDataActions($this->getCreateAction());

        if ($this->acl->checkUserAccess(ActionsInterface::CONFIG_IMPORT)
            && $this->configData->isLdapEnabled()
        ) {
            $grid->setDataActions($this->getLdapSyncAction());
        }

        $grid->setDataActions($this->getViewAction());
        $grid->setDataActions($this->getEditAction());
        $grid->setDataActions($this->getEditPassAction());
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
        $gridTab->setId('tblUsers');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Usuarios'));

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
        $gridHeader->addHeader(__('Login'));
        $gridHeader->addHeader(__('Perfil'));
        $gridHeader->addHeader(__('Grupo'));
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
        $gridData->addDataRowSource('login');
        $gridData->addDataRowSource('userProfileName');
        $gridData->addDataRowSource('userGroupName');
        $gridData->addDataRowSourceWithIcon('isAdminApp', $this->icons->getIconAppAdmin());
        $gridData->addDataRowSourceWithIcon('isAdminAcc', $this->icons->getIconAccAdmin());
        $gridData->addDataRowSourceWithIcon('isLdap', $this->icons->getIconLdapUser());
        $gridData->addDataRowSourceWithIcon('isDisabled', $this->icons->getIconDisabled());
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
        $gridActionSearch->setId(ActionsInterface::USER_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchUser');
        $gridActionSearch->setTitle(__('Buscar Usuario'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Nuevo Usuario'));
        $gridAction->setTitle(__('Nuevo Usuario'));
        $gridAction->setIcon($this->icons->getIconAdd());
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_CREATE));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getEditAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_EDIT);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Editar Usuario'));
        $gridAction->setTitle(__('Editar Usuario'));
        $gridAction->setIcon($this->icons->getIconEdit());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_EDIT));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Eliminar Usuario'));
        $gridAction->setTitle(__('Eliminar Usuario'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_DELETE));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getLdapSyncAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::CONFIG_LDAP_SYNC);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Importar usuarios de LDAP'));
        $gridAction->setTitle(__('Importar usuarios de LDAP'));
        $gridAction->setIcon(new FontIcon('get_app'));
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/ldapSync');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::CONFIG_LDAP_SYNC));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getViewAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('Ver Detalles de Usuario'));
        $gridAction->setTitle(__('Ver Detalles de Usuario'));
        $gridAction->setIcon($this->icons->getIconView());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_VIEW));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getEditPassAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_EDIT_PASS);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Cambiar Clave de Usuario'));
        $gridAction->setTitle(__('Cambiar Clave de Usuario'));
        $gridAction->setIcon($this->icons->getIconEditPass());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->setFilterRowSource('isLdap');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_EDIT_PASS));

        return $gridAction;
    }
}