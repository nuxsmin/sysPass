<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Helpers;

defined('APP_ROOT') || die();

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\UI\ThemeIconsBase;
use SP\Html\Assets\FontIcon;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridPager;
use SP\Html\DataGrid\DataGridTab;

/**
 * Class Grids con las plantillas de tablas de datos
 *
 * @package SP\Controller
 */
class ItemsGridHelper extends HelperBase
{
    protected $queryTimeStart;
    /**
     * @var ThemeIconsBase
     */
    protected $icons;
    /**
     * @var \SP\Core\Acl\Acl
     */
    protected $acl;

    /**
     * @param \SP\Core\Acl\Acl $acl
     */
    public function inject(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getCategoriesGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));
        $GridHeaders->addHeader(__('Descripción'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('category_id');
        $GridData->addDataRowSource('category_name');
        $GridData->addDataRowSource('category_description');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblCategories');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Categorías'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::CATEGORY_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchCategory');
        $GridActionSearch->setTitle(__('Buscar Categoría'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::CATEGORY_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::CATEGORY_CREATE);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nueva Categoría'));
        $GridActionNew->setTitle(__('Nueva Categoría'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::CATEGORY_CREATE));

        $Grid->setDataActions($GridActionNew);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::CATEGORY_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Categoría'));
        $GridActionEdit->setTitle(__('Editar Categoría'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::CATEGORY_VIEW));


        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::CATEGORY_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Categoría'));
        $GridActionDel->setTitle(__('Eliminar Categoría'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::CATEGORY_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * Devolver el paginador por defecto
     *
     * @param DataGridActionSearch $sourceAction
     * @return DataGridPager
     */
    protected function getPager(DataGridActionSearch $sourceAction)
    {
        $GridPager = new DataGridPager();
        $GridPager->setSourceAction($sourceAction);
        $GridPager->setOnClickFunction('appMgmt/nav');
        $GridPager->setLimitStart(0);
        $GridPager->setLimitCount($this->configData->getAccountCount());
        $GridPager->setIconPrev($this->icons->getIconNavPrev());
        $GridPager->setIconNext($this->icons->getIconNavNext());
        $GridPager->setIconFirst($this->icons->getIconNavFirst());
        $GridPager->setIconLast($this->icons->getIconNavLast());

        return $GridPager;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getClientsGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));
        $GridHeaders->addHeader(__('Descripción'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('customer_id');
        $GridData->addDataRowSource('customer_name');
        $GridData->addDataRowSource('customer_description');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblCustomers');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Clientes'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::CLIENT_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchCustomer');
        $GridActionSearch->setTitle(__('Buscar Cliente'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::CLIENT_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::CLIENT_CREATE);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Cliente'));
        $GridActionNew->setTitle(__('Nuevo Cliente'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::CLIENT_CREATE));

        $Grid->setDataActions($GridActionNew);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::CLIENT_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Cliente'));
        $GridActionEdit->setTitle(__('Editar Cliente'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::CLIENT_VIEW));

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::CLIENT_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Cliente'));
        $GridActionDel->setTitle(__('Eliminar Cliente'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::CLIENT_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getCustomFieldsGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Módulo'));
        $GridHeaders->addHeader(__('Nombre'));
        $GridHeaders->addHeader(__('Tipo'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('moduleName');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('typeName');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblCustomFields');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Campos Personalizados'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::CUSTOMFIELD_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchCustomField');
        $GridActionSearch->setTitle(__('Buscar Campo'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::CUSTOMFIELD_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::CUSTOMFIELD_CREATE);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Campo'));
        $GridActionNew->setTitle(__('Nuevo Campo'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::CUSTOMFIELD_CREATE));

        $Grid->setDataActions($GridActionNew);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::CUSTOMFIELD_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Campo'));
        $GridActionEdit->setTitle(__('Editar Campo'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::CUSTOMFIELD_VIEW));

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::CUSTOMFIELD_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Campo'));
        $GridActionDel->setTitle(__('Eliminar Campo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::CUSTOMFIELD_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getFilesGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Cuenta'));
        $GridHeaders->addHeader(__('Cliente'));
        $GridHeaders->addHeader(__('Nombre'));
        $GridHeaders->addHeader(__('Tipo'));
        $GridHeaders->addHeader(__('Tamaño'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('accfile_id');
        $GridData->addDataRowSource('account_name');
        $GridData->addDataRowSource('customer_name');
        $GridData->addDataRowSource('accfile_name');
        $GridData->addDataRowSource('accfile_type');
        $GridData->addDataRowSource('accfile_size');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblFiles');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Archivos'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::FILE_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchFile');
        $GridActionSearch->setTitle(__('Buscar Archivo'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::FILE_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::FILE_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Archivo'));
        $GridActionView->setTitle(__('Ver Archivo'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('file/view');
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::FILE_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::FILE_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Archivo'));
        $GridActionDel->setTitle(__('Eliminar Archivo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::FILE_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getAccountsGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));
        $GridHeaders->addHeader(__('Cliente'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('account_id');
        $GridData->addDataRowSource('account_name');
        $GridData->addDataRowSource('customer_name');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblAccounts');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Cuentas'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::ACCOUNTMGR_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchAccount');
        $GridActionSearch->setTitle(__('Buscar Cuenta'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::ACCOUNTMGR_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Cuenta'));
        $GridActionDel->setTitle(__('Eliminar Cuenta'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getAccountsHistoryGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));
        $GridHeaders->addHeader(__('Cliente'));
        $GridHeaders->addHeader(__('Fecha'));
        $GridHeaders->addHeader(__('Estado'));

        $iconEdit = clone $this->icons->getIconEdit();
        $iconDelete = clone $this->icons->getIconDelete();

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('acchistory_id');
        $GridData->addDataRowSource('acchistory_name');
        $GridData->addDataRowSource('customer_name');
        $GridData->addDataRowSource('acchistory_date');
        $GridData->addDataRowSourceWithIcon('acchistory_isModify', $iconEdit->setTitle(__('Modificada'))->setClass('opacity50'));
        $GridData->addDataRowSourceWithIcon('acchistory_isDeleted', $iconDelete->setTitle(__('Eliminada'))->setClass('opacity50'));
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblAccountsHistory');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Cuentas (H)'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::ACCOUNTMGR_SEARCH_HISTORY);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchAccountHistory');
        $GridActionSearch->setTitle(__('Buscar Cuenta'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_SEARCH_HISTORY));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionRestore = new DataGridAction();
        $GridActionRestore->setId(ActionsInterface::ACCOUNTMGR_RESTORE);
        $GridActionRestore->setType(DataGridActionType::EDIT_ITEM);
        $GridActionRestore->setName(__('Restaurar Cuenta'));
        $GridActionRestore->setTitle(__('Restaurar Cuenta'));
        $GridActionRestore->setIcon($this->icons->getIconRestore());
        $GridActionRestore->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_RESTORE));

//        $Grid->setDataActions($GridActionRestore);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::ACCOUNTMGR_DELETE_HISTORY);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Cuenta'));
        $GridActionDel->setTitle(__('Eliminar Cuenta'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNTMGR_DELETE_HISTORY));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getUsersGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));
        $GridHeaders->addHeader(__('Login'));
        $GridHeaders->addHeader(__('Perfil'));
        $GridHeaders->addHeader(__('Grupo'));
        $GridHeaders->addHeader(__('Propiedades'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('user_id');
        $GridData->addDataRowSource('user_name');
        $GridData->addDataRowSource('user_login');
        $GridData->addDataRowSource('userprofile_name');
        $GridData->addDataRowSource('usergroup_name');
        $GridData->addDataRowSourceWithIcon('user_isAdminApp', $this->icons->getIconAppAdmin());
        $GridData->addDataRowSourceWithIcon('user_isAdminAcc', $this->icons->getIconAccAdmin());
        $GridData->addDataRowSourceWithIcon('user_isLdap', $this->icons->getIconLdapUser());
        $GridData->addDataRowSourceWithIcon('user_isDisabled', $this->icons->getIconDisabled());
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblUsers');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Usuarios'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::USER_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchUser');
        $GridActionSearch->setTitle(__('Buscar Usuario'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::USER_CREATE);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Usuario'));
        $GridActionNew->setTitle(__('Nuevo Usuario'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_CREATE));

        $Grid->setDataActions($GridActionNew);

        if ($this->acl->checkUserAccess(ActionsInterface::IMPORT_CONFIG)
            && $this->configData->isLdapEnabled()
        ) {
            $GridActionLdapSync = new DataGridAction();
            $GridActionLdapSync->setId(ActionsInterface::LDAP_SYNC);
            $GridActionLdapSync->setType(DataGridActionType::NEW_ITEM);
            $GridActionLdapSync->setName(__('Importar usuarios de LDAP'));
            $GridActionLdapSync->setTitle(__('Importar usuarios de LDAP'));
            $GridActionLdapSync->setIcon(new FontIcon('get_app'));
            $GridActionLdapSync->setSkip(true);
            $GridActionLdapSync->setOnClickFunction('appMgmt/ldapSync');
            $GridActionLdapSync->addData('action-route', Acl::getActionRoute(ActionsInterface::LDAP_SYNC));

            $Grid->setDataActions($GridActionLdapSync);
        }

        // Grid item's actions
        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::USER_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Detalles de Usuario'));
        $GridActionView->setTitle(__('Ver Detalles de Usuario'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::USER_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Usuario'));
        $GridActionEdit->setTitle(__('Editar Usuario'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_EDIT));

        $Grid->setDataActions($GridActionEdit);

        $GridActionEditPass = new DataGridAction();
        $GridActionEditPass->setId(ActionsInterface::USER_EDIT_PASS);
        $GridActionEditPass->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEditPass->setName(__('Cambiar Clave de Usuario'));
        $GridActionEditPass->setTitle(__('Cambiar Clave de Usuario'));
        $GridActionEditPass->setIcon($this->icons->getIconEditPass());
        $GridActionEditPass->setOnClickFunction('appMgmt/show');
        $GridActionEditPass->setFilterRowSource('user_isLdap');
        $GridActionEditPass->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_EDIT_PASS));

        $Grid->setDataActions($GridActionEditPass);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::USER_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Usuario'));
        $GridActionDel->setTitle(__('Eliminar Usuario'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getUserGroupsGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));
        $GridHeaders->addHeader(__('Descripción'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('usergroup_id');
        $GridData->addDataRowSource('usergroup_name');
        $GridData->addDataRowSource('usergroup_description');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblGroups');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Grupos'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::GROUP_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchGroup');
        $GridActionSearch->setTitle(__('Buscar Grupo'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::GROUP_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::GROUP_CREATE);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Grupo'));
        $GridActionNew->setTitle(__('Nuevo Grupo'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::GROUP_CREATE));

        $Grid->setDataActions($GridActionNew);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::GROUP_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Grupo'));
        $GridActionView->setTitle(__('Ver Grupo'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::GROUP_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::GROUP_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Grupo'));
        $GridActionEdit->setTitle(__('Editar Grupo'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::GROUP_EDIT));

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::GROUP_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Grupo'));
        $GridActionDel->setTitle(__('Eliminar Grupo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::GROUP_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getUserProfilesGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('userprofile_id');
        $GridData->addDataRowSource('userprofile_name');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblProfiles');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Perfiles'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::PROFILE_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchProfile');
        $GridActionSearch->setTitle(__('Buscar Perfil'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::PROFILE_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::PROFILE_CREATE);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Perfil'));
        $GridActionNew->setTitle(__('Nuevo Perfil'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::PROFILE_CREATE));

        $Grid->setDataActions($GridActionNew);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::PROFILE_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Detalles de Perfil'));
        $GridActionView->setTitle(__('Ver Detalles de Perfil'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::PROFILE_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::PROFILE_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Perfil'));
        $GridActionEdit->setTitle(__('Editar Perfil'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::PROFILE_EDIT));

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::PROFILE_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Perfil'));
        $GridActionDel->setTitle(__('Eliminar Perfil'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::PROFILE_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getApiTokensGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Usuario'));
        $GridHeaders->addHeader(__('Acción'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('authtoken_id');
        $GridData->addDataRowSource('user_login');
        $GridData->addDataRowSource('authtoken_actionId');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblTokens');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Autorizaciones API'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::APITOKEN_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchToken');
        $GridActionSearch->setTitle(__('Buscar Token'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::APITOKEN_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::APITOKEN_CREATE);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nueva Autorización'));
        $GridActionNew->setTitle(__('Nueva Autorización'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::APITOKEN_CREATE));

        $Grid->setDataActions($GridActionNew);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::APITOKEN_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver token de Autorización'));
        $GridActionView->setTitle(__('Ver token de Autorización'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::APITOKEN_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::APITOKEN_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Autorización'));
        $GridActionEdit->setTitle(__('Editar Autorización'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::APITOKEN_EDIT));

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::APITOKEN_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Autorización'));
        $GridActionDel->setTitle(__('Eliminar Autorización'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::APITOKEN_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getPublicLinksGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Cuenta'));
        $GridHeaders->addHeader(__('Fecha Creación'));
        $GridHeaders->addHeader(__('Fecha Caducidad'));
        $GridHeaders->addHeader(__('Usuario'));
        $GridHeaders->addHeader(__('Notificar'));
        $GridHeaders->addHeader(__('Visitas'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('publicLink_id');
        $GridData->addDataRowSource('account_name');
        $GridData->addDataRowSource('getDateAddFormat', true);
        $GridData->addDataRowSource('getDateExpireFormat', true);
        $GridData->addDataRowSource('user_login');
        $GridData->addDataRowSource('getNotifyString', true);
        $GridData->addDataRowSource('getCountViewsString', true);
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblLinks');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Enlaces'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::PUBLICLINK_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchLink');
        $GridActionSearch->setTitle(__('Buscar Enlace'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::PUBLICLINK_CREATE);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Enlace'));
        $GridActionNew->setTitle(__('Nuevo Enlace'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_CREATE));

        $Grid->setDataActions($GridActionNew);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::PUBLICLINK_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Enlace'));
        $GridActionView->setTitle(__('Ver Enlace'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionRefresh = new DataGridAction();
        $GridActionRefresh->setId(ActionsInterface::PUBLICLINK_REFRESH);
        $GridActionRefresh->setName(__('Renovar Enlace'));
        $GridActionRefresh->setTitle(__('Renovar Enlace'));
        $GridActionRefresh->setIcon($this->icons->getIconRefresh());
        $GridActionRefresh->setOnClickFunction('link/refresh');
        $GridActionRefresh->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_REFRESH));

        $Grid->setDataActions($GridActionRefresh);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::PUBLICLINK_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Enlace'));
        $GridActionDel->setTitle(__('Eliminar Enlace'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::PUBLICLINK_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getTagsGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('tag_id');
        $GridData->addDataRowSource('tag_name');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblTags');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Etiquetas'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::TAG_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchTag');
        $GridActionSearch->setTitle(__('Buscar Etiqueta'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::TAG_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::TAG_CREATE);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nueva Etiqueta'));
        $GridActionNew->setTitle(__('Nueva Etiqueta'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::TAG_CREATE));

        $Grid->setDataActions($GridActionNew);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::TAG_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Etiqueta'));
        $GridActionEdit->setTitle(__('Editar Etiqueta'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::TAG_VIEW));

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::TAG_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Etiqueta'));
        $GridActionDel->setTitle(__('Eliminar Etiqueta'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::TAG_DELETE));

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGridTab
     */
    public function getPluginsGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Plugin'));
        $GridHeaders->addHeader(__('Estado'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('plugin_id');
        $GridData->addDataRowSource('plugin_name');
        $GridData->addDataRowSourceWithIcon('plugin_enabled', $this->icons->getIconEnabled());
        $GridData->addDataRowSourceWithIcon('plugin_enabled', $this->icons->getIconDisabled(), 0);
        $GridData->addDataRowSourceWithIcon('plugin_available', $this->icons->getIconDelete()->setTitle(__('No disponible')), 0);
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblPlugins');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Plugins'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::PLUGIN_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchPlugin');
        $GridActionSearch->setTitle(__('Buscar Plugin'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::PLUGIN_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Plugin'));
        $GridActionView->setTitle(__('Ver Plugin'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');
        $GridActionView->setFilterRowSource('plugin_available', 0);
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionEnable = new DataGridAction();
        $GridActionEnable->setId(ActionsInterface::PLUGIN_ENABLE);
        $GridActionEnable->setName(__('Habilitar'));
        $GridActionEnable->setTitle(__('Habilitar'));
        $GridActionEnable->setIcon($this->icons->getIconEnabled());
        $GridActionEnable->setOnClickFunction('plugin/toggle');
        $GridActionEnable->setFilterRowSource('plugin_enabled');
        $GridActionEnable->setFilterRowSource('plugin_available', 0);
        $GridActionEnable->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_ENABLE));

        $Grid->setDataActions($GridActionEnable);

        $GridActionDisable = new DataGridAction();
        $GridActionDisable->setId(ActionsInterface::PLUGIN_DISABLE);
        $GridActionDisable->setName(__('Deshabilitar'));
        $GridActionDisable->setTitle(__('Deshabilitar'));
        $GridActionDisable->setIcon($this->icons->getIconDisabled());
        $GridActionDisable->setOnClickFunction('plugin/toggle');
        $GridActionDisable->setFilterRowSource('plugin_enabled', 0);
        $GridActionDisable->setFilterRowSource('plugin_available', 0);
        $GridActionDisable->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_DISABLE));

        $Grid->setDataActions($GridActionDisable);

        $GridActionReset = new DataGridAction();
        $GridActionReset->setId(ActionsInterface::PLUGIN_RESET);
        $GridActionReset->setName(__('Restablecer Datos'));
        $GridActionReset->setTitle(__('Restablecer Datos'));
        $GridActionReset->setIcon($this->icons->getIconRefresh());
        $GridActionReset->setOnClickFunction('plugin/reset');
        $GridActionReset->setFilterRowSource('plugin_available', 0);
        $GridActionReset->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_RESET));

        $Grid->setDataActions($GridActionReset);

        return $Grid;
    }

    protected function initialize()
    {
        $this->icons = $this->view->getTheme()->getIcons();
    }
}