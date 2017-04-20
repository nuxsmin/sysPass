<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link http://syspass.org
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

namespace SP\Controller\Grids;

defined('APP_ROOT') || die();

use SP\Config\Config;
use SP\Core\Acl;
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Html\Assets\FontIcon;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridTab;

/**
 * Class Grids con las plantillas de tablas de datos
 *
 * @package SP\Controller
 */
class Items extends GridBase
{
    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getCategoriesGrid()
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
        $GridActionSearch->setId(self::ACTION_MGM_CATEGORIES_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchCategory');
        $GridActionSearch->setTitle(__('Buscar Categoría'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_CATEGORIES_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nueva Categoría'));
        $GridActionNew->setTitle(__('Nueva Categoría'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionNew);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CATEGORIES_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Categoría'));
        $GridActionEdit->setTitle(__('Editar Categoría'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CATEGORIES_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Categoría'));
        $GridActionDel->setTitle(__('Eliminar Categoría'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getCustomersGrid()
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
        $GridActionSearch->setId(self::ACTION_MGM_CUSTOMERS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchCustomer');
        $GridActionSearch->setTitle(__('Buscar Cliente'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_CUSTOMERS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Cliente'));
        $GridActionNew->setTitle(__('Nuevo Cliente'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionNew);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CUSTOMERS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Cliente'));
        $GridActionEdit->setTitle(__('Editar Cliente'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CUSTOMERS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Cliente'));
        $GridActionDel->setTitle(__('Eliminar Cliente'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getCustomFieldsGrid()
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
        $GridActionSearch->setId(self::ACTION_MGM_CUSTOMFIELDS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchCustomField');
        $GridActionSearch->setTitle(__('Buscar Campo'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_CUSTOMFIELDS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Campo'));
        $GridActionNew->setTitle(__('Nuevo Campo'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionNew);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CUSTOMFIELDS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Campo'));
        $GridActionEdit->setTitle(__('Editar Campo'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CUSTOMFIELDS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Campo'));
        $GridActionDel->setTitle(__('Eliminar Campo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getFilesGrid()
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
        $GridActionSearch->setId(self::ACTION_MGM_FILES_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchFile');
        $GridActionSearch->setTitle(__('Buscar Archivo'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_FILES_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Archivo'));
        $GridActionView->setTitle(__('Ver Archivo'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('file/view');

        $Grid->setDataActions($GridActionView);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_FILES_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Archivo'));
        $GridActionDel->setTitle(__('Eliminar Archivo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getAccountsGrid()
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
        $GridActionSearch->setId(self::ACTION_MGM_ACCOUNTS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchAccount');
        $GridActionSearch->setTitle(__('Buscar Cuenta'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_ACCOUNTS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Cuenta'));
        $GridActionDel->setTitle(__('Eliminar Cuenta'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getAccountsHistoryGrid()
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
        $GridActionSearch->setId(self::ACTION_MGM_ACCOUNTS_SEARCH_HISTORY);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchAccountHistory');
        $GridActionSearch->setTitle(__('Buscar Cuenta'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionRestore = new DataGridAction();
        $GridActionRestore->setId(self::ACTION_MGM_ACCOUNTS_EDIT_RESTORE);
        $GridActionRestore->setType(DataGridActionType::EDIT_ITEM);
        $GridActionRestore->setName(__('Restaurar Cuenta'));
        $GridActionRestore->setTitle(__('Restaurar Cuenta'));
        $GridActionRestore->setIcon($this->icons->getIconRestore());
        $GridActionRestore->setOnClickFunction('');

//        $Grid->setDataActions($GridActionRestore);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_ACCOUNTS_DELETE_HISTORY);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Cuenta'));
        $GridActionDel->setTitle(__('Eliminar Cuenta'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getUsersGrid()
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
        $GridActionSearch->setId(self::ACTION_USR_USERS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchUser');
        $GridActionSearch->setTitle(__('Buscar Usuario'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_USR_USERS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Usuario'));
        $GridActionNew->setTitle(__('Nuevo Usuario'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionNew);

        if (Acl::checkUserAccess(ActionsInterface::ACTION_CFG_IMPORT)
            && Config::getConfig()->isLdapEnabled()
        ) {
            $GridActionLdapSync = new DataGridAction();
            $GridActionLdapSync->setId(self::ACTION_USR_SYNC_LDAP);
            $GridActionLdapSync->setType(DataGridActionType::NEW_ITEM);
            $GridActionLdapSync->setName(__('Importar usuarios de LDAP'));
            $GridActionLdapSync->setTitle(__('Importar usuarios de LDAP'));
            $GridActionLdapSync->setIcon(new FontIcon('get_app'));
            $GridActionLdapSync->setSkip(true);
            $GridActionLdapSync->setOnClickFunction('appMgmt/ldapSync');

            $Grid->setDataActions($GridActionLdapSync);
        }

        // Grid item's actions
        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_USR_USERS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Detalles de Usuario'));
        $GridActionView->setTitle(__('Ver Detalles de Usuario'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionView);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_USR_USERS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Usuario'));
        $GridActionEdit->setTitle(__('Editar Usuario'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionEdit);

        $GridActionEditPass = new DataGridAction();
        $GridActionEditPass->setId(self::ACTION_USR_USERS_EDITPASS);
        $GridActionEditPass->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEditPass->setName(__('Cambiar Clave de Usuario'));
        $GridActionEditPass->setTitle(__('Cambiar Clave de Usuario'));
        $GridActionEditPass->setIcon($this->icons->getIconEditPass());
        $GridActionEditPass->setOnClickFunction('appMgmt/show');
        $GridActionEditPass->setFilterRowSource('user_isLdap');

        $Grid->setDataActions($GridActionEditPass);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_USR_USERS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Usuario'));
        $GridActionDel->setTitle(__('Eliminar Usuario'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getGroupsGrid()
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
        $GridActionSearch->setId(self::ACTION_USR_GROUPS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchGroup');
        $GridActionSearch->setTitle(__('Buscar Grupo'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_USR_GROUPS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Grupo'));
        $GridActionNew->setTitle(__('Nuevo Grupo'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionNew);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_USR_GROUPS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Grupo'));
        $GridActionEdit->setTitle(__('Editar Grupo'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_USR_GROUPS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Grupo'));
        $GridActionDel->setTitle(__('Eliminar Grupo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getProfilesGrid()
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('userprofile_id');
        $GridData->addDataRowSource('userprofile_name');

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
        $GridActionSearch->setId(self::ACTION_USR_PROFILES_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchProfile');
        $GridActionSearch->setTitle(__('Buscar Perfil'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_USR_PROFILES_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nuevo Perfil'));
        $GridActionNew->setTitle(__('Nuevo Perfil'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionNew);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_USR_PROFILES_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Detalles de Perfil'));
        $GridActionView->setTitle(__('Ver Detalles de Perfil'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionView);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_USR_PROFILES_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Perfil'));
        $GridActionEdit->setTitle(__('Editar Perfil'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_USR_PROFILES_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Perfil'));
        $GridActionDel->setTitle(__('Eliminar Perfil'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getTokensGrid()
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
        $GridActionSearch->setId(self::ACTION_MGM_APITOKENS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchToken');
        $GridActionSearch->setTitle(__('Buscar Token'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_APITOKENS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nueva Autorización'));
        $GridActionNew->setTitle(__('Nueva Autorización'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionNew);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_APITOKENS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver token de Autorización'));
        $GridActionView->setTitle(__('Ver token de Autorización'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionView);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_APITOKENS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Autorización'));
        $GridActionEdit->setTitle(__('Editar Autorización'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_APITOKENS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Autorización'));
        $GridActionDel->setTitle(__('Eliminar Autorización'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getPublicLinksGrid()
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
        $GridData->addDataRowSource('accountName');
        $GridData->addDataRowSource('dateAdd');
        $GridData->addDataRowSource('dateExpire');
        $GridData->addDataRowSource('userLogin');
        $GridData->addDataRowSource('notify');
        $GridData->addDataRowSource('countViews');

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
        $GridActionSearch->setId(self::ACTION_MGM_PUBLICLINKS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchLink');
        $GridActionSearch->setTitle(__('Buscar Enlace'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_PUBLICLINKS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Enlace'));
        $GridActionView->setTitle(__('Ver Enlace'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionView);

        $GridActionRefresh = new DataGridAction();
        $GridActionRefresh->setId(self::ACTION_MGM_PUBLICLINKS_REFRESH);
        $GridActionRefresh->setName(__('Renovar Enlace'));
        $GridActionRefresh->setTitle(__('Renovar Enlace'));
        $GridActionRefresh->setIcon($this->icons->getIconRefresh());
        $GridActionRefresh->setOnClickFunction('link/refresh');

        $Grid->setDataActions($GridActionRefresh);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_PUBLICLINKS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Enlace'));
        $GridActionDel->setTitle(__('Eliminar Enlace'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getTagsGrid()
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Nombre'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('tag_id');
        $GridData->addDataRowSource('tag_name');

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
        $GridActionSearch->setId(self::ACTION_MGM_TAGS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchTag');
        $GridActionSearch->setTitle(__('Buscar Etiqueta'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_TAGS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(__('Nueva Etiqueta'));
        $GridActionNew->setTitle(__('Nueva Etiqueta'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionNew);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_TAGS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Etiqueta'));
        $GridActionEdit->setTitle(__('Editar Etiqueta'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionEdit);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_TAGS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Etiqueta'));
        $GridActionDel->setTitle(__('Eliminar Etiqueta'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * @return DataGridTab
     * @throws InvalidArgumentException
     */
    public function getPluginsGrid()
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
        $GridActionSearch->setId(self::ACTION_MGM_PLUGINS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchPlugin');
        $GridActionSearch->setTitle(__('Buscar Plugin'));
        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_PLUGINS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Ver Plugin'));
        $GridActionView->setTitle(__('Ver Plugin'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionView);

        $GridActionEnable = new DataGridAction();
        $GridActionEnable->setId(self::ACTION_MGM_PLUGINS_ENABLE);
        $GridActionEnable->setName(__('Habilitar'));
        $GridActionEnable->setTitle(__('Habilitar'));
        $GridActionEnable->setIcon($this->icons->getIconEnabled());
        $GridActionEnable->setOnClickFunction('plugin/toggle');
        $GridActionEnable->setFilterRowSource('plugin_enabled', 1);

        $Grid->setDataActions($GridActionEnable);

        $GridActionDisable = new DataGridAction();
        $GridActionDisable->setId(self::ACTION_MGM_PLUGINS_DISABLE);
        $GridActionDisable->setName(__('Deshabilitar'));
        $GridActionDisable->setTitle(__('Deshabilitar'));
        $GridActionDisable->setIcon($this->icons->getIconDisabled());
        $GridActionDisable->setOnClickFunction('plugin/toggle');
        $GridActionDisable->setFilterRowSource('plugin_enabled', 0);

        $Grid->setDataActions($GridActionDisable);

        $GridActionReset = new DataGridAction();
        $GridActionReset->setId(self::ACTION_MGM_PLUGINS_RESET);
        $GridActionReset->setName(__('Restablecer Datos'));
        $GridActionReset->setTitle(__('Restablecer Datos'));
        $GridActionReset->setIcon($this->icons->getIconRefresh());
        $GridActionReset->setOnClickFunction('plugin/reset');

        $Grid->setDataActions($GridActionReset);

        return $Grid;
    }
}