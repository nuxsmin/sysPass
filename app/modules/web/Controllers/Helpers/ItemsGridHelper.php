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

namespace SP\Modules\Web\Controllers\Helpers;

defined('APP_ROOT') || die();

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\ItemSearchData;
use SP\Html\Assets\FontIcon;
use SP\Html\DataGrid\DataGrid;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridPager;
use SP\Html\DataGrid\DataGridTab;
use SP\Services\CustomField\CustomFieldDefService;
use SP\Util\DateUtil;

/**
 * Class Grids con las plantillas de tablas de datos
 *
 * @package SP\Controller
 */
class ItemsGridHelper extends HelperBase
{
    protected $queryTimeStart;
    /**
     * @var ThemeIcons
     */
    protected $icons;
    /**
     * @var \SP\Core\Acl\Acl
     */
    protected $acl;

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
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('description');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
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
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::CATEGORY_EDIT));


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
        $GridHeaders->addHeader(__('Global'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('description');
        $GridData->addDataRowSource('isGlobal', false, function ($value) {
            return $value ? __('SI') : __('NO');
        });
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
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
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::CLIENT_EDIT));

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
        $GridHeaders->addHeader(__('Nombre'));
        $GridHeaders->addHeader(__('Módulo'));
        $GridHeaders->addHeader(__('Tipo'));
        $GridHeaders->addHeader(__('Propiedades'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('moduleId', false, function ($value) {
            return CustomFieldDefService::getFieldModuleById($value);
        });
        $GridData->addDataRowSource('typeName');
        $GridData->addDataRowSourceWithIcon('isEncrypted', $this->icons->getIconEditPass()->setTitle(__('Encriptado')));
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
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
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::CUSTOMFIELD_EDIT));

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
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('accountName');
        $GridData->addDataRowSource('clientName');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('type');
        $GridData->addDataRowSource('size');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
        $Grid->setId('tblFiles');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Archivos'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::ACCOUNT_FILE_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchFile');
        $GridActionSearch->setTitle(__('Buscar Archivo'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::ACCOUNT_FILE_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Archivo'));
        $GridActionView->setTitle(__('Ver Archivo'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('file/view');
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_VIEW));

        $Grid->setDataActions($GridActionView);

        // Grid item's actions
        $GridActionDownload = new DataGridAction();
        $GridActionDownload->setId(ActionsInterface::ACCOUNT_FILE_DOWNLOAD);
        $GridActionDownload->setType(DataGridActionType::VIEW_ITEM);
        $GridActionDownload->setName(__('Descargar Archivo'));
        $GridActionDownload->setTitle(__('Descargar Archivo'));
        $GridActionDownload->setIcon($this->icons->getIconDownload());
        $GridActionDownload->setOnClickFunction('file/download');
        $GridActionDownload->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DOWNLOAD));

        $Grid->setDataActions($GridActionDownload);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::ACCOUNT_FILE_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Archivo'));
        $GridActionDel->setTitle(__('Eliminar Archivo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::ACCOUNT_FILE_DELETE));

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
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('clientName');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('clientName');
        $GridData->addDataRowSource('date');
        $GridData->addDataRowSourceWithIcon('isModify', $iconEdit->setTitle(__('Modificada'))->setClass('opacity50'));
        $GridData->addDataRowSourceWithIcon('isDeleted', $iconDelete->setTitle(__('Eliminada'))->setClass('opacity50'));
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('login');
        $GridData->addDataRowSource('userProfileName');
        $GridData->addDataRowSource('userGroupName');
        $GridData->addDataRowSourceWithIcon('isAdminApp', $this->icons->getIconAppAdmin());
        $GridData->addDataRowSourceWithIcon('isAdminAcc', $this->icons->getIconAccAdmin());
        $GridData->addDataRowSourceWithIcon('isLdap', $this->icons->getIconLdapUser());
        $GridData->addDataRowSourceWithIcon('isDisabled', $this->icons->getIconDisabled());
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
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
            $GridActionLdapSync->setType(DataGridActionType::MENUBAR_ITEM);
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
        $GridActionEditPass->setFilterRowSource('isLdap');
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
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('description');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
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
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
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
    public function getAuthTokensGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Usuario'));
        $GridHeaders->addHeader(__('Acción'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('userLogin');
        $GridData->addDataRowSource('actionId');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
        $Grid->setId('tblTokens');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Autorizaciones API'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::AUTHTOKEN_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchToken');
        $GridActionSearch->setTitle(__('Buscar Token'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::AUTHTOKEN_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(ActionsInterface::AUTHTOKEN_CREATE);
        $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
        $GridActionNew->setName(__('Nueva Autorización'));
        $GridActionNew->setTitle(__('Nueva Autorización'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');
        $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::AUTHTOKEN_CREATE));

        $Grid->setDataActions($GridActionNew);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::AUTHTOKEN_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver token de Autorización'));
        $GridActionView->setTitle(__('Ver token de Autorización'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::AUTHTOKEN_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::AUTHTOKEN_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Autorización'));
        $GridActionEdit->setTitle(__('Editar Autorización'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::AUTHTOKEN_EDIT));

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::AUTHTOKEN_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Autorización'));
        $GridActionDel->setTitle(__('Eliminar Autorización'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::AUTHTOKEN_DELETE));

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
        $GridHeaders->addHeader(__('Cliente'));
        $GridHeaders->addHeader(__('Fecha Creación'));
        $GridHeaders->addHeader(__('Fecha Caducidad'));
        $GridHeaders->addHeader(__('Usuario'));
        $GridHeaders->addHeader(__('Notificar'));
        $GridHeaders->addHeader(__('Visitas'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('accountName');
        $GridData->addDataRowSource('clientName');
        $GridData->addDataRowSource('getDateAddFormat', true);
        $GridData->addDataRowSource('getDateExpireFormat', true);
        $GridData->addDataRowSource('userLogin');
        $GridData->addDataRowSource('getNotifyString', true);
        $GridData->addDataRowSource('getCountViewsString', true);
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
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
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
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
        $GridActionEdit->setOnClickFunction('appMgmt/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::TAG_EDIT));

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
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSourceWithIcon('enabled', $this->icons->getIconEnabled());
        $GridData->addDataRowSourceWithIcon('enabled', $this->icons->getIconDisabled(), 0);
        $GridData->addDataRowSourceWithIcon('available', $this->icons->getIconDelete()->setTitle(__('No disponible')), 0);
        $GridData->setData($data);

        // Grid
        $Grid = new DataGridTab($this->view->getTheme());
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
        $GridActionView->setFilterRowSource('available', 0);
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionEnable = new DataGridAction();
        $GridActionEnable->setId(ActionsInterface::PLUGIN_ENABLE);
        $GridActionEnable->setName(__('Habilitar'));
        $GridActionEnable->setTitle(__('Habilitar'));
        $GridActionEnable->setIcon($this->icons->getIconEnabled());
        $GridActionEnable->setOnClickFunction('plugin/toggle');
        $GridActionEnable->setFilterRowSource('enabled');
        $GridActionEnable->setFilterRowSource('available', 0);
        $GridActionEnable->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_ENABLE));
        $GridActionEnable->addData('action-method', 'get');

        $Grid->setDataActions($GridActionEnable);

        $GridActionDisable = new DataGridAction();
        $GridActionDisable->setId(ActionsInterface::PLUGIN_DISABLE);
        $GridActionDisable->setName(__('Deshabilitar'));
        $GridActionDisable->setTitle(__('Deshabilitar'));
        $GridActionDisable->setIcon($this->icons->getIconDisabled());
        $GridActionDisable->setOnClickFunction('plugin/toggle');
        $GridActionDisable->setFilterRowSource('enabled', 0);
        $GridActionDisable->setFilterRowSource('available', 0);
        $GridActionDisable->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_DISABLE));
        $GridActionDisable->addData('action-method', 'get');

        $Grid->setDataActions($GridActionDisable);

        $GridActionReset = new DataGridAction();
        $GridActionReset->setId(ActionsInterface::PLUGIN_RESET);
        $GridActionReset->setName(__('Restablecer Datos'));
        $GridActionReset->setTitle(__('Restablecer Datos'));
        $GridActionReset->setIcon($this->icons->getIconRefresh());
        $GridActionReset->setOnClickFunction('plugin/reset');
        $GridActionReset->setFilterRowSource('available', 0);
        $GridActionReset->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_RESET));
        $GridActionReset->addData('action-method', 'get');

        $Grid->setDataActions($GridActionReset);

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGrid
     */
    public function getEventLogGrid(array $data)
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('ID'));
        $GridHeaders->addHeader(__('Fecha / Hora'));
        $GridHeaders->addHeader(__('Nivel'));
        $GridHeaders->addHeader(__('Evento'));
        $GridHeaders->addHeader(__('Login'));
        $GridHeaders->addHeader(__('IP'));
        $GridHeaders->addHeader(__('Descripción'));

        $isDemoMode = $this->configData->isDemoEnabled();

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('id');
        $GridData->addDataRowSource('date');
        $GridData->addDataRowSource('level');
        $GridData->addDataRowSource('action');
        $GridData->addDataRowSource('login');
        $GridData->addDataRowSource('ipAddress', false,
            function ($value) use ($isDemoMode) {
                return $isDemoMode ? preg_replace('#\d+#', '*', $value) : $value;
            });
        $GridData->addDataRowSource('description', false,
            function ($value) use ($isDemoMode) {
                if ($isDemoMode) {
                    $value = preg_replace('/\\d+\\.\\d+\\.\\d+\\.\\d+/', "*.*.*.*", $value);
                }

                $text = str_replace(';;', PHP_EOL, $value);

                if (preg_match('/^SQL.*/m', $text)) {
                    $text = preg_replace([
                        '/([a-zA-Z_]+),/m',
                        '/(UPDATE|DELETE|TRUNCATE|INSERT|SELECT|WHERE|LEFT|ORDER|LIMIT|FROM)/m'],
                        ['\\1,<br>', '<br>\\1'],
                        $text);
                }

//                if (strlen($text) >= 100) {
//                    $text = wordwrap($text, 100, PHP_EOL, true);
//                }

                return str_replace(PHP_EOL, '<br>', $text);
            });
        $GridData->setData($data);

        // Grid
        $Grid = new DataGrid($this->view->getTheme());
        $Grid->setId('tblEventLog');
        $Grid->setDataTableTemplate('datagrid-table-simple', 'grid');
        $Grid->setDataRowTemplate('datagrid-rows', $this->view->getBase());
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Registro de Eventos'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::EVENTLOG_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchEvent');
        $GridActionSearch->setTitle(__('Buscar Evento'));
        $GridActionSearch->setOnSubmitFunction('eventlog/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::EVENTLOG_SEARCH));

        $Grid->setDataActions($GridActionSearch);

        $GridActionRefresh = new DataGridAction();
        $GridActionRefresh->setId(ActionsInterface::EVENTLOG_SEARCH);
        $GridActionRefresh->setType(DataGridActionType::MENUBAR_ITEM);
        $GridActionRefresh->setName(__('Refrescar'));
        $GridActionRefresh->setTitle(__('Refrescar'));
        $GridActionRefresh->setIcon($this->icons->getIconRefresh());
        $GridActionRefresh->setOnClickFunction('eventlog/search');
        $GridActionRefresh->addData('action-route', Acl::getActionRoute(ActionsInterface::EVENTLOG_SEARCH));
        $GridActionRefresh->addData('target', '#data-table-tblEventLog');

        $Grid->setDataActions($GridActionRefresh);

        $GridActionClear = new DataGridAction();
        $GridActionClear->setId(ActionsInterface::EVENTLOG_CLEAR);
        $GridActionClear->setType(DataGridActionType::MENUBAR_ITEM);
        $GridActionClear->setName(__('Vaciar registro de eventos'));
        $GridActionClear->setTitle(__('Vaciar registro de eventos'));
        $GridActionClear->setIcon($this->icons->getIconClear());
        $GridActionClear->setOnClickFunction('eventlog/clear');
        $GridActionClear->addData('action-route', Acl::getActionRoute(ActionsInterface::EVENTLOG_CLEAR));
        $GridActionClear->addData('nextaction', Acl::getActionRoute(ActionsInterface::EVENTLOG));

        $Grid->setDataActions($GridActionClear);

        $Grid->setPager($this->getPager($GridActionSearch)
            ->setOnClickFunction('eventlog/nav')
        );

        return $Grid;
    }

    /**
     * @param array $data
     * @return DataGrid
     */
    public function getNotificationsGrid(array $data)
    {
        $isAdminApp = $this->context->getUserData()->getIsAdminApp();

        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Fecha'));
        $GridHeaders->addHeader(__('Tipo'));
        $GridHeaders->addHeader(__('Componente'));
        $GridHeaders->addHeader(__('Descripción'));
        $GridHeaders->addHeader(__('Estado'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('date', false,
            function ($value) {
                return DateUtil::getDateFromUnix($value);
            });
        $GridData->addDataRowSource('type');
        $GridData->addDataRowSource('component');
        $GridData->addDataRowSource('description');
        $GridData->addDataRowSourceWithIcon('checked', $this->icons->getIconEnabled()->setTitle(__('Leída')));
        $GridData->addDataRowSourceWithIcon('onlyAdmin', $this->icons->getIconAppAdmin()->setTitle(__('Sólo Admins')));
        $GridData->addDataRowSourceWithIcon('sticky', $this->icons->getIconGroup()->setTitle(__('Global')));
        $GridData->setData($data);

        // Grid
        $Grid = new DataGrid($this->view->getTheme());
        $Grid->setId('tblNotifications');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Notificaciones'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(ActionsInterface::NOTIFICATION_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchNotification');
        $GridActionSearch->setTitle(__('Buscar Notificación'));
        $GridActionSearch->setOnSubmitFunction('notification/search');
        $GridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_SEARCH));

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        if ($isAdminApp) {
            // Grid item's actions
            $GridActionNew = new DataGridAction();
            $GridActionNew->setId(ActionsInterface::NOTIFICATION_CREATE);
            $GridActionNew->setType(DataGridActionType::MENUBAR_ITEM);
            $GridActionNew->setName(__('Nueva Notificación'));
            $GridActionNew->setTitle(__('Nueva Notificación'));
            $GridActionNew->setIcon($this->icons->getIconAdd());
            $GridActionNew->setSkip(true);
            $GridActionNew->setOnClickFunction('notification/show');
            $GridActionNew->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_CREATE));

            $Grid->setDataActions($GridActionNew);
        }

        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::NOTIFICATION_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Notificación'));
        $GridActionView->setTitle(__('Ver Notificación'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('notification/show');
        $GridActionView->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_VIEW));

        $Grid->setDataActions($GridActionView);

        $GridActionCheck = new DataGridAction();
        $GridActionCheck->setId(ActionsInterface::NOTIFICATION_CHECK);
        $GridActionCheck->setName(__('Marcar Notificación'));
        $GridActionCheck->setTitle(__('Marcar Notificación'));
        $GridActionCheck->setIcon($this->icons->getIconEnabled());
        $GridActionCheck->setOnClickFunction('notification/check');
        $GridActionCheck->setFilterRowSource('checked');
        $GridActionCheck->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_CHECK));
        $GridActionCheck->addData('nextaction', Acl::getActionRoute(ActionsInterface::NOTIFICATION));

        $Grid->setDataActions($GridActionCheck);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::NOTIFICATION_EDIT);
        $GridActionEdit->setName(__('Editar Notificación'));
        $GridActionEdit->setTitle(__('Editar Notificación'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('notification/show');
        $GridActionEdit->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_EDIT));

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::NOTIFICATION_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Notificación'));
        $GridActionDel->setTitle(__('Eliminar Notificación'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('notification/delete');
        $GridActionDel->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_DELETE));
        $GridActionDel->addData('nextaction', Acl::getActionRoute(ActionsInterface::NOTIFICATION));

        if (!$isAdminApp) {
            $GridActionCheck->setFilterRowSource('sticky');
            $GridActionEdit->setFilterRowSource('sticky');
            $GridActionDel->setFilterRowSource('sticky');
        }

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * Actualizar los datos del paginador
     *
     * @param DataGridInterface $dataGrid
     * @param ItemSearchData    $itemSearchData
     * @return DataGridInterface
     */
    public function updatePager(DataGridInterface $dataGrid, ItemSearchData $itemSearchData)
    {
        $dataGrid->getPager()
            ->setLimitStart($itemSearchData->getLimitStart())
            ->setLimitCount($itemSearchData->getLimitCount())
            ->setFilterOn($itemSearchData->getSeachString() !== '');

        $dataGrid->updatePager();

        return $dataGrid;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->acl = $this->dic->get(Acl::class);
        $this->icons = $this->view->getTheme()->getIcons();
    }
}