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

use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\SessionUtil;
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
     * @var Icons
     */
    private $_icons;
    /**
     * @var string
     */
    private $_sk;
    /**
     * @var int
     */
    private $_queryTimeStart;
    /**
     * @var bool
     */
    private $_filter = false;

    /**
     * Grids constructor.
     */
    public function __construct()
    {
        $this->_sk = SessionUtil::getSessionKey(true);
        $this->_icons = new Icons();
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
        $GridActionNew->setIcon($this->_icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_CATEGORIES_NEW);
        $GridActionNew->setOnClickArgs($this->_sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CATEGORIES_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Categoría'));
        $GridActionEdit->setTitle(_('Editar Categoría'));
        $GridActionEdit->setIcon($this->_icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_CATEGORIES_EDIT);
        $GridActionEdit->setOnClickArgs($this->_sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CATEGORIES_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Categoría'));
        $GridActionDel->setTitle(_('Eliminar Categoría'));
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_CATEGORIES_DELETE);
        $GridActionDel->setOnClickArgs($this->_sk);

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
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionSearch);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Categorías'));
        $Grid->setTime(round(microtime() - $this->_queryTimeStart, 5));

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
        $GridPager->setLimitCount(Config::getValue('account_count'));
        $GridPager->setIconPrev($this->_icons->getIconNavPrev());
        $GridPager->setIconNext($this->_icons->getIconNavNext());
        $GridPager->setIconFirst($this->_icons->getIconNavFirst());
        $GridPager->setIconLast($this->_icons->getIconNavLast());

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
        $GridActionNew->setIcon($this->_icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_CUSTOMERS_NEW);
        $GridActionNew->setOnClickArgs($this->_sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CUSTOMERS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Cliente'));
        $GridActionEdit->setTitle(_('Editar Cliente'));
        $GridActionEdit->setIcon($this->_icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_CUSTOMERS_EDIT);
        $GridActionEdit->setOnClickArgs($this->_sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CUSTOMERS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Cliente'));
        $GridActionDel->setTitle(_('Eliminar Cliente'));
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_CUSTOMERS_DELETE);
        $GridActionDel->setOnClickArgs($this->_sk);

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
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionSearch);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Clientes'));
        $Grid->setTime(round(microtime() - $this->_queryTimeStart, 5));

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
        $GridActionNew->setIcon($this->_icons->getIconAdd());
        $GridActionNew->setSkip(true);
        $GridActionNew->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionNew->setOnClickArgs('this');
        $GridActionNew->setOnClickArgs(self::ACTION_MGM_CUSTOMFIELDS_NEW);
        $GridActionNew->setOnClickArgs($this->_sk);

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_MGM_CUSTOMFIELDS_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(_('Editar Campo'));
        $GridActionEdit->setTitle(_('Editar Campo'));
        $GridActionEdit->setIcon($this->_icons->getIconEdit());
        $GridActionEdit->setOnClickFunction('sysPassUtil.Common.appMgmtData');
        $GridActionEdit->setOnClickArgs('this');
        $GridActionEdit->setOnClickArgs(self::ACTION_MGM_CUSTOMFIELDS_EDIT);
        $GridActionEdit->setOnClickArgs($this->_sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_CUSTOMFIELDS_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Campo'));
        $GridActionDel->setTitle(_('Eliminar Campo'));
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_CUSTOMFIELDS_DELETE);
        $GridActionDel->setOnClickArgs($this->_sk);

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
        $Grid->setDataActions($GridActionNew);
        $Grid->setDataActions($GridActionEdit);
        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionSearch);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Campos Personalizados'));
        $Grid->setTime(round(microtime() - $this->_queryTimeStart, 5));

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
        $GridActionView->setIcon($this->_icons->getIconView());
        $GridActionView->setOnClickFunction('sysPassUtil.Common.viewFile');
        $GridActionView->setOnClickArgs('this');
        $GridActionView->setOnClickArgs(self::ACTION_MGM_FILES_VIEW);
        $GridActionView->setOnClickArgs($this->_sk);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_MGM_FILES_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Archivo'));
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_FILES_DELETE);
        $GridActionDel->setOnClickArgs($this->_sk);

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
        $Grid->setTime(round(microtime() - $this->_queryTimeStart, 5));

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
        $GridActionDel->setIcon($this->_icons->getIconDelete());
        $GridActionDel->setOnClickFunction('sysPassUtil.Common.appMgmtDelete');
        $GridActionDel->setOnClickArgs('this');
        $GridActionDel->setOnClickArgs(self::ACTION_MGM_ACCOUNTS_DELETE);
        $GridActionDel->setOnClickArgs($this->_sk);

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
        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionSearch);
        $Grid->setHeader($GridHeaders);
        $Grid->setPager($this->getPager($GridActionSearch));
        $Grid->setData($GridData);
        $Grid->setTitle(_('Gestión de Cuentas'));
        $Grid->setTime(round(microtime() - $this->_queryTimeStart, 5));

        return $Grid;
    }

    /**
     * @param boolean $filter
     */
    public function setFilter($filter)
    {
        $this->_filter = $filter;
    }

    /**
     * @param int $queryTimeStart
     */
    public function setQueryTimeStart($queryTimeStart)
    {
        $this->_queryTimeStart = $queryTimeStart;
    }
}