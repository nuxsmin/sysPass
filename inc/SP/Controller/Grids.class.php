<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace SP\Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\SessionUtil;
use SP\Core\ThemeIconsBase;
use SP\Core\Themes;
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
class Grids implements ActionsInterface
{
    /**
     * @var ThemeIconsBase
     */
    private $icons;
    /**
     * @var string
     */
    private $sk;
    /**
     * @var int
     */
    private $queryTimeStart;
    /**
     * @var bool
     */
    private $filter = false;

    /**
     * Grids constructor.
     */
    public function __construct()
    {
        $this->sk = SessionUtil::getSessionKey(true);
        $this->icons = Themes::getIcons();
    }

    /**
     * @return DataGridTab
     */
    public function getCategoriesGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_MGM_CATEGORIES_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchCategory');
        $GridActionSearch->setTitle(_('Buscar Categoría'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_CATEGORIES_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nueva Categoría'));
        $GridActionNew->setTitle(_('Nueva Categoría'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_CATEGORIES_NEW);
        $GridActionNew->setOnClickArgs($this->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CATEGORIES_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Categoría'));
        $GridActionEdit->setTitle(_('Editar Categoría'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_CATEGORIES_EDIT);
        $GridActionEdit->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CATEGORIES_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Categoría'));
        $GridActionDel->setTitle(_('Eliminar Categoría'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_CATEGORIES_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Descripción'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('category_id');
        $GridData->addDataRowSource('category_name');
        $GridData->addDataRowSource('category_description');

        $Grid = new DataGridTab();
        $Grid->setId('tblCategories');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Categorías'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * Devolver el paginador por defecto
     *
     * @param DataGridActionSearch $sourceAction
     * @return DataGridPager
     */
    public function getPager(DataGridActionSearch $sourceAction)
    {
        $GridPager = new DataGridPager();
        $GridPager->setOnClickFunction('sysPassUtil.Common.appMgmtNav');
        $GridPager->setOnClickArgs($sourceAction->getName());
        $GridPager->setLimitStart(0);
        $GridPager->setLimitCount(Config::getConfig()->getAccountCount());
        $GridPager->setIconPrev($this->icons->getIconNavPrev());
        $GridPager->setIconNext($this->icons->getIconNavNext());
        $GridPager->setIconFirst($this->icons->getIconNavFirst());
        $GridPager->setIconLast($this->icons->getIconNavLast());

        return $GridPager;
    }

    /**
     * @return DataGridTab
     */
    public function getCustomersGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_MGM_CUSTOMERS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchCustomer');
        $GridActionSearch->setTitle(_('Buscar Cliente'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_CUSTOMERS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nuevo Cliente'));
        $GridActionNew->setTitle(_('Nuevo Cliente'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_CUSTOMERS_NEW);
        $GridActionNew->setOnClickArgs($this->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CUSTOMERS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Cliente'));
        $GridActionEdit->setTitle(_('Editar Cliente'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_CUSTOMERS_EDIT);
        $GridActionEdit->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CUSTOMERS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Cliente'));
        $GridActionDel->setTitle(_('Eliminar Cliente'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_CUSTOMERS_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Descripción'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('customer_id');
        $GridData->addDataRowSource('customer_name');
        $GridData->addDataRowSource('customer_description');

        $Grid = new DataGridTab();
        $Grid->setId('tblCustomers');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Clientes'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @return DataGridTab
     */
    public function getCustomFieldsGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_MGM_CUSTOMFIELDS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchCustomField');
        $GridActionSearch->setTitle(_('Buscar Campo'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_CUSTOMFIELDS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nuevo Campo'));
        $GridActionNew->setTitle(_('Nuevo Campo'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_CUSTOMFIELDS_NEW);
        $GridActionNew->setOnClickArgs($this->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CUSTOMFIELDS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Campo'));
        $GridActionEdit->setTitle(_('Editar Campo'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_CUSTOMFIELDS_EDIT);
        $GridActionEdit->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CUSTOMFIELDS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Campo'));
        $GridActionDel->setTitle(_('Eliminar Campo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_CUSTOMFIELDS_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Módulo'));
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Tipo'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('id');
        $GridData->addDataRowSource('module');
        $GridData->addDataRowSource('name');
        $GridData->addDataRowSource('typeName');

        $Grid = new DataGridTab();
        $Grid->setId('tblCustomFields');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Campos Personalizados'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @return DataGridTab
     */
    public function getFilesGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_MGM_FILES_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchFile');
        $GridActionSearch->setTitle(_('Buscar Archivo'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_FILES_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(_('Ver Archivo'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.viewFile');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_MGM_FILES_VIEW);
        $GridActionView->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_FILES_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Archivo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_FILES_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Cuenta'));
        $GridHeaders->addHeader(_('Cliente'));
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Tipo'));
        $GridHeaders->addHeader(_('Tamaño'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('accfile_id');
        $GridData->addDataRowSource('account_name');
        $GridData->addDataRowSource('customer_name');
        $GridData->addDataRowSource('accfile_name');
        $GridData->addDataRowSource('accfile_type');
        $GridData->addDataRowSource('accfile_size');

        $Grid = new DataGridTab();
        $Grid->setId('tblFiles');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionSearch);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Archivos'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @return DataGridTab
     */
    public function getAccountsGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_MGM_ACCOUNTS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchAccount');
        $GridActionSearch->setTitle(_('Buscar Cuenta'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_ACCOUNTS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Cuenta'));
        $GridActionDel->setTitle(_('Eliminar Cuenta'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_ACCOUNTS_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Cliente'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('account_id');
        $GridData->addDataRowSource('account_name');
        $GridData->addDataRowSource('customer_name');

        $Grid = new DataGridTab();
        $Grid->setId('tblAccounts');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Cuentas'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @return DataGridTab
     */
    public function getUsersGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_USR_USERS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchUser');
        $GridActionSearch->setTitle(_('Buscar Usuario'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_USR_USERS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nuevo Usuario'));
        $GridActionNew->setTitle(_('Nuevo Usuario'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_USR_USERS_NEW);
        $GridActionNew->setOnClickArgs($this->sk);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_USR_USERS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(_('Ver Detalles de Usuario'));
        $GridActionView->setTitle(_('Ver Detalles de Usuario'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_USR_USERS_VIEW);
        $GridActionView->setOnClickArgs($this->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_USR_USERS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Usuario'));
        $GridActionEdit->setTitle(_('Editar Usuario'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_USR_USERS_EDIT);
        $GridActionEdit->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_USR_USERS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Usuario'));
        $GridActionDel->setTitle(_('Eliminar Usuario'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_USR_USERS_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridActionEditPass = new DataGridAction();
        $GridActionEditPass->setId(self::ACTION_USR_USERS_EDITPASS);
        $GridActionEditPass->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEditPass->setName(_('Cambiar Clave de Usuario'));
        $GridActionEditPass->setTitle(_('Cambiar Clave de Usuario'));
        $GridActionEditPass->setIcon($this->icons->getIconEditPass());
        $GridActionEditPass->setOnClickFunction('sysPassUtil.Common.usrUpdPass');
        $GridActionEditPass->setOnClickArgs('this');
        $GridActionEditPass->setOnClickArgs(self::ACTION_USR_USERS_EDITPASS);
        $GridActionEditPass->setOnClickArgs($this->sk);
        $GridActionEditPass->setFilterRowSource('user_isLdap');

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Login'));
        $GridHeaders->addHeader(_('Perfil'));
        $GridHeaders->addHeader(_('Grupo'));
        $GridHeaders->addHeader(_('Propiedades'));

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

        $Grid = new DataGridTab();
        $Grid->setId('tblUsers');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionEditPass);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Usuarios'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @return DataGridTab
     */
    public function getGroupsGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_USR_GROUPS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchGroup');
        $GridActionSearch->setTitle(_('Buscar Grupo'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_USR_GROUPS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nuevo Grupo'));
        $GridActionNew->setTitle(_('Nuevo Grupo'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_USR_GROUPS_NEW);
        $GridActionNew->setOnClickArgs($this->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_USR_GROUPS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Grupo'));
        $GridActionEdit->setTitle(_('Editar Grupo'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_USR_GROUPS_EDIT);
        $GridActionEdit->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_USR_GROUPS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Grupo'));
        $GridActionDel->setTitle(_('Eliminar Grupo'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_USR_GROUPS_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));
        $GridHeaders->addHeader(_('Descripción'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('usergroup_id');
        $GridData->addDataRowSource('usergroup_name');
        $GridData->addDataRowSource('usergroup_description');

        $Grid = new DataGridTab();
        $Grid->setId('tblGroups');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Grupos'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @return DataGridTab
     */
    public function getProfilesGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_USR_PROFILES_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchProfile');
        $GridActionSearch->setTitle(_('Buscar Perfil'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_USR_PROFILES_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nuevo Perfil'));
        $GridActionNew->setTitle(_('Nuevo Perfil'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_USR_PROFILES_NEW);
        $GridActionNew->setOnClickArgs($this->sk);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_USR_PROFILES_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(_('Ver Detalles de Perfil'));
        $GridActionView->setTitle(_('Ver Detalles de Perfil'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_USR_PROFILES_VIEW);
        $GridActionView->setOnClickArgs($this->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_USR_PROFILES_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Perfil'));
        $GridActionEdit->setTitle(_('Editar Perfil'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_USR_PROFILES_EDIT);
        $GridActionEdit->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_USR_PROFILES_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Perfil'));
        $GridActionDel->setTitle(_('Eliminar Perfil'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_USR_PROFILES_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('userprofile_id');
        $GridData->addDataRowSource('userprofile_name');

        $Grid = new DataGridTab();
        $Grid->setId('tblProfiles');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Perfiles'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @return DataGridTab
     */
    public function getTokensGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_MGM_APITOKENS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchToken');
        $GridActionSearch->setTitle(_('Buscar Token'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_APITOKENS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nueva Autorización'));
        $GridActionNew->setTitle(_('Nueva Autorización'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_APITOKENS_NEW);
        $GridActionNew->setOnClickArgs($this->sk);

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_APITOKENS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(_('Ver token de Autorización'));
        $GridActionView->setTitle(_('Ver token de Autorización'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_MGM_APITOKENS_VIEW);
        $GridActionView->setOnClickArgs($this->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_APITOKENS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Autorización'));
        $GridActionEdit->setTitle(_('Editar Autorización'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_APITOKENS_EDIT);
        $GridActionEdit->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_APITOKENS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Autorización'));
        $GridActionDel->setTitle(_('Eliminar Autorización'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_APITOKENS_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Usuario'));
        $GridHeaders->addHeader(_('Acción'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('authtoken_id');
        $GridData->addDataRowSource('user_login');
        $GridData->addDataRowSource('authtoken_actionId');

        $Grid = new DataGridTab();
        $Grid->setId('tblTokens');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Autorizaciones API'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @return DataGridTab
     */
    public function getPublicLinksGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_MGM_PUBLICLINKS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchLink');
        $GridActionSearch->setTitle(_('Buscar Enlace'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_MGM_PUBLICLINKS_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(_('Ver Enlace'));
        $GridActionView->setTitle(_('Ver Enlace'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_MGM_PUBLICLINKS_VIEW);
        $GridActionView->setOnClickArgs($this->sk);

        $GridActionRefresh = new DataGridAction();
        $GridActionRefresh->setId(self::ACTION_MGM_PUBLICLINKS_REFRESH);
        $GridActionRefresh->setName(_('Renovar Enlace'));
        $GridActionRefresh->setTitle(_('Renovar Enlace'));
        $GridActionRefresh->setIcon($this->icons->getIconRefresh());
        $GridActionRefresh->setOnClickFunction('sysPassUtil.Common.linksMgmtRefresh');
        $GridActionRefresh->setOnClickArgs('this');
        $GridActionRefresh->setOnClickArgs(self::ACTION_MGM_PUBLICLINKS_REFRESH);
        $GridActionRefresh->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_PUBLICLINKS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Enlace'));
        $GridActionDel->setTitle(_('Eliminar Enlace'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_PUBLICLINKS_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Cuenta'));
        $GridHeaders->addHeader(_('Fecha Creación'));
        $GridHeaders->addHeader(_('Fecha Caducidad'));
        $GridHeaders->addHeader(_('Usuario'));
        $GridHeaders->addHeader(_('Notificar'));
        $GridHeaders->addHeader(_('Visitas'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('publicLink_id');
        $GridData->addDataRowSource('publicLink_account');
        $GridData->addDataRowSource('publicLink_dateAdd');
        $GridData->addDataRowSource('publicLink_dateExpire');
        $GridData->addDataRowSource('publicLink_user');
        $GridData->addDataRowSource('publicLink_notify');
        $GridData->addDataRowSource('publicLink_views');

        $Grid = new DataGridTab();
        $Grid->setId('tblLinks');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionRefresh);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Enlaces'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @return DataGridTab
     */
    public function getTagsGrid()
    {
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_MGM_TAGS_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchTag');
        $GridActionSearch->setTitle(_('Buscar Etiqueta'));
        $GridActionSearch->setOnSubmitFunction('sysPassUtil.Common.appMgmtSearch');
        $GridActionSearch->setOnSubmitArgs('this');

        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_MGM_TAGS_NEW);
        $GridActionNew->setType(DataGridActionType::NEW_ITEM);
        $GridActionNew->setName(_('Nueva Etiqueta'));
        $GridActionNew->setTitle(_('Nueva Etiqueta'));
        $GridActionNew->setIcon($this->icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_TAGS_NEW);
        $GridActionNew->setOnClickArgs($this->sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_TAGS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Etiqueta'));
        $GridActionEdit->setTitle(_('Editar Etiqueta'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_TAGS_EDIT);
        $GridActionEdit->setOnClickArgs($this->sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_TAGS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Etiqueta'));
        $GridActionDel->setTitle(_('Eliminar Etiqueta'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_TAGS_DELETE);
        $GridActionDel->setOnClickArgs($this->sk);

        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Nombre'));

        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('tag_id');
        $GridData->addDataRowSource('tag_name');

        $Grid = new DataGridTab();
        $Grid->setId('tblTags');
        $Grid->setDataRowTemplate('datagrid-rows');
        $Grid->setDataPagerTemplate('datagrid-nav-full');
        $Grid->setDataActions($GridActionSearch);
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Etiquetas'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @param boolean $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * @param int $queryTimeStart
     */
    public function setQueryTimeStart($queryTimeStart)
    {
        $this->queryTimeStart = $queryTimeStart;
    }
}