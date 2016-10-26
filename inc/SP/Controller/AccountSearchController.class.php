<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

use SP\Account\AccountSearch;
use SP\Account\AccountsSearchData;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\Html\DataGrid\DataGrid;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeaderSort;
use SP\Html\DataGrid\DataGridPager;
use SP\Html\DataGrid\DataGridSort;
use SP\Http\Request;
use SP\Storage\DBUtil;
use SP\Util\Checks;

/**
 * Clase encargada de obtener los datos para presentar la búsqueda
 *
 * @package Controller
 */
class AccountSearchController extends ControllerBase implements ActionsInterface
{
    /**
     * Indica si el filtrado de cuentas está activo
     *
     * @var bool
     */
    private $filterOn = false;


    /** @var string */
    private $sk = '';
    /** @var int */
    private $sortKey = 0;
    /** @var string */
    private $sortOrder = 0;
    /** @var bool */
    private $searchGlobal = false;
    /** @var int */
    private $limitStart = 0;
    /** @var int */
    private $limitCount = 0;
    /** @var int */
    private $queryTimeStart = 0;
    /** @var bool */
    private $isAjax = false;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->queryTimeStart = microtime();
        $this->sk = SessionUtil::getSessionKey(true);
        $this->view->assign('sk', $this->sk);
        $this->setVars();
    }

    /**
     * Establecer las variables necesarias para las plantillas
     */
    private function setVars()
    {
        $this->view->assign('isAdmin', Session::getUserIsAdminApp() || Session::getUserIsAdminAcc());
        $this->view->assign('showGlobalSearch', Config::getConfig()->isGlobalSearch());

        // Comprobar si está creado el objeto de búsqueda en la sesión
        if (!is_object(Session::getSearchFilters())) {
            Session::setSearchFilters(new AccountSearch());
        }

        // Obtener el filtro de búsqueda desde la sesión
        $filters = Session::getSearchFilters();

        // Comprobar si la búsqueda es realizada desde el fromulario
        // de lo contrario, se recupera la información de filtros de la sesión
        $isSearch = (!isset($this->view->actionId));

        $this->sortKey = $isSearch ? Request::analyze('skey', 0) : $filters->getSortKey();
        $this->sortOrder = $isSearch ? Request::analyze('sorder', 0) : $filters->getSortOrder();
        $this->searchGlobal = $isSearch ? Request::analyze('gsearch', 0) : $filters->getGlobalSearch();
        $this->limitStart = $isSearch ? Request::analyze('start', 0) : $filters->getLimitStart();
        $this->limitCount = $isSearch ? Request::analyze('rpp', 0) : $filters->getLimitCount();

        // Valores POST
        $this->view->assign('searchCustomer', $isSearch ? Request::analyze('customer', 0) : $filters->getCustomerId());
        $this->view->assign('searchCategory', $isSearch ? Request::analyze('category', 0) : $filters->getCategoryId());
        $this->view->assign('searchTxt', $isSearch ? Request::analyze('search') : $filters->getTxtSearch());
        $this->view->assign('searchGlobal', Request::analyze('gsearch', $filters->getGlobalSearch()));
        $this->view->assign('searchFavorites', Request::analyze('searchfav', $filters->isSearchFavorites()));
    }

    /**
     * @param boolean $isAjax
     */
    public function setIsAjax($isAjax)
    {
        $this->isAjax = $isAjax;
    }

    /**
     * Obtener los datos para la caja de búsqueda
     */
    public function getSearchBox()
    {
        $this->view->addTemplate('searchbox');

        $this->view->assign('customers', DBUtil::getValuesForSelect('customers', 'customer_id', 'customer_name'));
        $this->view->assign('categories', DBUtil::getValuesForSelect('categories', 'category_id', 'category_name'));
    }

    /**
     * Obtener los resultados de una búsqueda
     */
    public function getSearch()
    {
        $this->view->addTemplate('index');

        $this->view->assign('isAjax', $this->isAjax);

        $Search = new AccountSearch();
        $Search->setGlobalSearch($this->searchGlobal)
            ->setSortKey($this->sortKey)
            ->setSortOrder($this->sortOrder)
            ->setLimitStart($this->limitStart)
            ->setLimitCount($this->limitCount)
            ->setTxtSearch($this->view->searchTxt)
            ->setCategoryId($this->view->searchCategory)
            ->setCustomerId($this->view->searchCustomer)
            ->setSearchFavorites($this->view->searchFavorites);

        $this->filterOn = ($this->sortKey > 1
            || $this->view->searchCustomer
            || $this->view->searchCategory
            || $this->view->searchTxt
            || $this->view->searchFavorites
            || $Search->isSortViews());

        AccountsSearchData::$accountLink = Session::getUserPreferences()->isAccountLink();
        AccountsSearchData::$topNavbar = Session::getUserPreferences()->isTopNavbar();
        AccountsSearchData::$optionalActions = Session::getUserPreferences()->isOptionalActions();
        AccountsSearchData::$requestEnabled = Checks::mailrequestIsEnabled();
        AccountsSearchData::$wikiEnabled = Checks::wikiIsEnabled();
        AccountsSearchData::$dokuWikiEnabled = Checks::dokuWikiIsEnabled();
        AccountsSearchData::$isDemoMode = Checks::demoIsEnabled();

        if (AccountsSearchData::$wikiEnabled) {
            $wikiFilter = array_map(function ($value) {
                return preg_quote($value);
            }, Config::getConfig()->getWikiFilter());

            $this->view->assign('wikiFilter', implode('|', $wikiFilter));
            $this->view->assign('wikiPageUrl', Config::getConfig()->getWikiPageurl());
        }

        $Grid = $this->getGrid();
        $Grid->getData()->setData($Search->processSearchResults());
        $Grid->updatePager();
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        $this->view->assign('data', $Grid);
    }

    /**
     * Devuelve la matriz a utilizar en la vista
     *
     * @return DataGrid
     */
    private function getGrid()
    {
        $showOptionalActions = Session::getUserPreferences()->isOptionalActions();

        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_ACC_VIEW)
            ->setType(DataGridActionType::VIEW_ITEM)
            ->setName(_('Detalles de Cuenta'))
            ->setTitle(_('Detalles de Cuenta'))
            ->setIcon($this->icons->getIconView())
            ->setReflectionFilter('\\SP\\Account\\AccountsSearchData', 'isShowView')
            ->addData('action-id', self::ACTION_ACC_VIEW)
            ->addData('action-sk', $this->sk)
            ->addData('onclick', 'account/show');

        $GridActionViewPass = new DataGridAction();
        $GridActionViewPass->setId(self::ACTION_ACC_VIEW_PASS)
            ->setType(DataGridActionType::VIEW_ITEM)
            ->setName(_('Ver Clave'))
            ->setTitle(_('Ver Clave'))
            ->setIcon($this->icons->getIconViewPass())
            ->setReflectionFilter('\\SP\\Account\\AccountsSearchData', 'isShowViewPass')
            ->addData('action-id', self::ACTION_ACC_VIEW_PASS)
            ->addData('action-sk', $this->sk)
            ->addData('onclick', 'account/showpass');

        // Añadir la clase para usar el portapapeles
        $ClipboardIcon = $this->icons->getIconClipboard()->setClass('clip-pass-button');

        $GridActionCopyPass = new DataGridAction();
        $GridActionCopyPass->setId(self::ACTION_ACC_VIEW_PASS)
            ->setType(DataGridActionType::VIEW_ITEM)
            ->setName(_('Copiar Clave en Portapapeles'))
            ->setTitle(_('Copiar Clave en Portapapeles'))
            ->setIcon($ClipboardIcon)
            ->setReflectionFilter('\\SP\\Account\\AccountsSearchData', 'isShowCopyPass')
            ->addData('action-id', self::ACTION_ACC_VIEW_PASS)
            ->addData('action-sk', $this->sk)
            ->addData('useclipboard', '1');

        $EditIcon = $this->icons->getIconEdit();

        if (!$showOptionalActions) {
            $EditIcon->setClass('actions-optional');
        }

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_ACC_EDIT)
            ->setType(DataGridActionType::EDIT_ITEM)
            ->setName(_('Editar Cuenta'))
            ->setTitle(_('Editar Cuenta'))
            ->setIcon($EditIcon)
            ->setReflectionFilter('\\SP\\Account\\AccountsSearchData', 'isShowEdit')
            ->addData('action-id', self::ACTION_ACC_EDIT)
            ->addData('action-sk', $this->sk)
            ->addData('onclick', 'account/edit');

        $CopyIcon = $this->icons->getIconCopy();

        if (!$showOptionalActions) {
            $CopyIcon->setClass('actions-optional');
        }

        $GridActionCopy = new DataGridAction();
        $GridActionCopy->setId(self::ACTION_ACC_COPY)
            ->setType(DataGridActionType::NEW_ITEM)
            ->setName(_('Copiar Cuenta'))
            ->setTitle(_('Copiar Cuenta'))
            ->setIcon($CopyIcon)
            ->setReflectionFilter('\\SP\\Account\\AccountsSearchData', 'isShowCopy')
            ->addData('action-id', self::ACTION_ACC_COPY)
            ->addData('action-sk', $this->sk)
            ->addData('onclick', 'account/copy');

        $DeleteIcon = $this->icons->getIconDelete();

        if (!$showOptionalActions) {
            $DeleteIcon->setClass('actions-optional');
        }

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_ACC_DELETE)
            ->setType(DataGridActionType::DELETE_ITEM)
            ->setName(_('Eliminar Cuenta'))
            ->setTitle(_('Eliminar Cuenta'))
            ->setIcon($DeleteIcon)
            ->setReflectionFilter('\\SP\\Account\\AccountsSearchData', 'isShowDelete')
            ->addData('action-id', self::ACTION_ACC_DELETE)
            ->addData('action-sk', $this->sk)
            ->addData('onclick', 'account/delete');

        $GridActionRequest = new DataGridAction();
        $GridActionRequest->setId(self::ACTION_ACC_REQUEST)
            ->setName(_('Solicitar Modificación'))
            ->setTitle(_('Solicitar Modificación'))
            ->setIcon($this->icons->getIconEmail())
            ->setReflectionFilter('\\SP\\Account\\AccountsSearchData', 'isShowRequest')
            ->addData('action-id', self::ACTION_ACC_REQUEST)
            ->addData('action-sk', $this->sk)
            ->addData('onclick', 'account/request');

        $GridActionOptional = new DataGridAction();
        $GridActionOptional->setId(0)
            ->setName(_('Más Acciones'))
            ->setTitle(_('Más Acciones'))
            ->setIcon($this->icons->getIconOptional())
            ->setReflectionFilter('\\SP\\Account\\AccountsSearchData', 'isShowOptional')
            ->addData('onclick', 'account/menu');

        $GridPager = new DataGridPager();
        $GridPager->setIconPrev($this->icons->getIconNavPrev())
            ->setIconNext($this->icons->getIconNavNext())
            ->setIconFirst($this->icons->getIconNavFirst())
            ->setIconLast($this->icons->getIconNavLast())
            ->setSortKey($this->sortKey)
            ->setSortOrder($this->sortOrder)
            ->setLimitStart($this->limitStart)
            ->setLimitCount($this->limitCount)
            ->setOnClickFunction('account/sort')
            ->setOnClickArgs($this->sortKey)
            ->setOnClickArgs($this->sortOrder)
            ->setFilterOn($this->filterOn)
            ->setSourceAction(new DataGridActionSearch(self::ACTION_ACC_SEARCH));

        $Grid = new DataGrid();
        $Grid->setId('gridSearch')
            ->setDataHeaderTemplate('header', $this->view->getBase())
            ->setDataRowTemplate('rows', $this->view->getBase())
            ->setDataPagerTemplate('datagrid-nav-full', 'grid')
            ->setHeader($this->getHeaderSort())
            ->setDataActions($GridActionView)
            ->setDataActions($GridActionViewPass)
            ->setDataActions($GridActionCopyPass)
            ->setDataActions($GridActionOptional)
            ->setDataActions($GridActionEdit)
            ->setDataActions($GridActionCopy)
            ->setDataActions($GridActionDel)
            ->setDataActions($GridActionRequest)
            ->setPager($GridPager)
            ->setData(new DataGridData());

        return $Grid;
    }

    /**
     * Devolver la cabecera con los campos de ordenación
     *
     * @return DataGridHeaderSort
     */
    private function getHeaderSort()
    {
        $GridSortCustomer = new DataGridSort();
        $GridSortCustomer->setName(_('Cliente'))
            ->setTitle(_('Ordenar por Cliente'))
            ->setSortKey(AccountSearch::SORT_CUSTOMER)
            ->setIconUp($this->icons->getIconUp())
            ->setIconDown($this->icons->getIconDown());

        $GridSortName = new DataGridSort();
        $GridSortName->setName(_('Nombre'))
            ->setTitle(_('Ordenar por Nombre'))
            ->setSortKey(AccountSearch::SORT_NAME)
            ->setIconUp($this->icons->getIconUp())
            ->setIconDown($this->icons->getIconDown());

        $GridSortCategory = new DataGridSort();
        $GridSortCategory->setName(_('Categoría'))
            ->setTitle(_('Ordenar por Categoría'))
            ->setSortKey(AccountSearch::SORT_CATEGORY)
            ->setIconUp($this->icons->getIconUp())
            ->setIconDown($this->icons->getIconDown());

        $GridSortLogin = new DataGridSort();
        $GridSortLogin->setName(_('Usuario'))
            ->setTitle(_('Ordenar por Usuario'))
            ->setSortKey(AccountSearch::SORT_LOGIN)
            ->setIconUp($this->icons->getIconUp())
            ->setIconDown($this->icons->getIconDown());

        $GridSortUrl = new DataGridSort();
        $GridSortUrl->setName(_('URL / IP'))
            ->setTitle(_('Ordenar por URL / IP'))
            ->setSortKey(AccountSearch::SORT_URL)
            ->setIconUp($this->icons->getIconUp())
            ->setIconDown($this->icons->getIconDown());

        $GridHeaderSort = new DataGridHeaderSort();
        $GridHeaderSort->addSortField($GridSortCustomer)
            ->addSortField($GridSortName)
            ->addSortField($GridSortCategory)
            ->addSortField($GridSortLogin)
            ->addSortField($GridSortUrl);

        return $GridHeaderSort;
    }
}