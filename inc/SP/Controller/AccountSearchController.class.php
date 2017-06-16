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

namespace SP\Controller;

defined('APP_ROOT') || die();

use SP\Account\AccountSearch;
use SP\Account\AccountsSearchItem;
use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\SPException;
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
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Tags\Tag;
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
        $this->view->assign('isAdmin', $this->UserData->isUserIsAdminApp() || $this->UserData->isUserIsAdminAcc());
        $this->view->assign('showGlobalSearch', Config::getConfig()->isGlobalSearch() && $this->UserProfileData->isAccGlobalSearch());

        // Obtener el filtro de búsqueda desde la sesión
        $filters = Session::getSearchFilters();

        // Comprobar si la búsqueda es realizada desde el formulario
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
        $this->view->assign('searchTags', $isSearch ? Request::analyze('tags') : $filters->getTagsId());
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

        $this->view->assign('customers', Customer::getItem()->getItemsForSelectByUser());
        $this->view->assign('categories', Category::getItem()->getItemsForSelect());
        $this->view->assign('tags', Tag::getItem()->getItemsForSelect());
    }

    /**
     * Obtener los resultados de una búsqueda
     *
     * @throws \InvalidArgumentException
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
            ->setTagsId($this->view->searchTags)
            ->setSearchFavorites($this->view->searchFavorites);

        $this->filterOn = ($this->sortKey > 1
            || $this->view->searchCustomer
            || $this->view->searchCategory
            || $this->view->searchTags
            || $this->view->searchTxt
            || $this->view->searchFavorites
            || $Search->isSortViews());

        $UserPreferences = Session::getUserPreferences();

        AccountsSearchItem::$accountLink = $UserPreferences->isAccountLink();
        AccountsSearchItem::$topNavbar = $UserPreferences->isTopNavbar();
        AccountsSearchItem::$optionalActions = $UserPreferences->isOptionalActions();
        AccountsSearchItem::$wikiEnabled = Checks::wikiIsEnabled();
        AccountsSearchItem::$dokuWikiEnabled = Checks::dokuWikiIsEnabled();
        AccountsSearchItem::$isDemoMode = Checks::demoIsEnabled();

        if (AccountsSearchItem::$wikiEnabled) {
            $wikiFilter = array_map(function ($value) {
                return preg_quote($value, '/');
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
     * @throws \ReflectionException
     */
    private function getGrid()
    {
        $GridActionView = new DataGridAction();
        $GridActionView->setId(self::ACTION_ACC_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Detalles de Cuenta'));
        $GridActionView->setTitle(__('Detalles de Cuenta'));
        $GridActionView->setIcon($this->icons->getIconView());
        $GridActionView->setReflectionFilter(AccountsSearchItem::class, 'isShowView');
        $GridActionView->addData('action-id', self::ACTION_ACC_VIEW);
        $GridActionView->addData('action-sk', $this->sk);
        $GridActionView->addData('onclick', 'account/show');

        $GridActionViewPass = new DataGridAction();
        $GridActionViewPass->setId(self::ACTION_ACC_VIEW_PASS);
        $GridActionViewPass->setType(DataGridActionType::VIEW_ITEM);
        $GridActionViewPass->setName(__('Ver Clave'));
        $GridActionViewPass->setTitle(__('Ver Clave'));
        $GridActionViewPass->setIcon($this->icons->getIconViewPass());
        $GridActionViewPass->setReflectionFilter(AccountsSearchItem::class, 'isShowViewPass');
        $GridActionViewPass->addData('action-id', self::ACTION_ACC_VIEW_PASS);
        $GridActionViewPass->addData('action-full', 1);
        $GridActionViewPass->addData('action-sk', $this->sk);
        $GridActionViewPass->addData('onclick', 'account/showpass');

        // Añadir la clase para usar el portapapeles
        $ClipboardIcon = $this->icons->getIconClipboard()->setClass('clip-pass-button');

        $GridActionCopyPass = new DataGridAction();
        $GridActionCopyPass->setId(self::ACTION_ACC_VIEW_PASS);
        $GridActionCopyPass->setType(DataGridActionType::VIEW_ITEM);
        $GridActionCopyPass->setName(__('Copiar Clave en Portapapeles'));
        $GridActionCopyPass->setTitle(__('Copiar Clave en Portapapeles'));
        $GridActionCopyPass->setIcon($ClipboardIcon);
        $GridActionCopyPass->setReflectionFilter(AccountsSearchItem::class, 'isShowCopyPass');
        $GridActionCopyPass->addData('action-id', self::ACTION_ACC_VIEW_PASS);
        $GridActionCopyPass->addData('action-full', 0);
        $GridActionCopyPass->addData('action-sk', $this->sk);
        $GridActionCopyPass->addData('useclipboard', '1');

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(self::ACTION_ACC_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Cuenta'));
        $GridActionEdit->setTitle(__('Editar Cuenta'));
        $GridActionEdit->setIcon($this->icons->getIconEdit());
        $GridActionEdit->setReflectionFilter(AccountsSearchItem::class, 'isShowEdit');
        $GridActionEdit->addData('action-id', self::ACTION_ACC_EDIT);
        $GridActionEdit->addData('action-sk', $this->sk);
        $GridActionEdit->addData('onclick', 'account/edit');

        $GridActionCopy = new DataGridAction();
        $GridActionCopy->setId(self::ACTION_ACC_COPY);
        $GridActionCopy->setType(DataGridActionType::NEW_ITEM);
        $GridActionCopy->setName(__('Copiar Cuenta'));
        $GridActionCopy->setTitle(__('Copiar Cuenta'));
        $GridActionCopy->setIcon($this->icons->getIconCopy());
        $GridActionCopy->setReflectionFilter(AccountsSearchItem::class, 'isShowCopy');
        $GridActionCopy->addData('action-id', self::ACTION_ACC_COPY);
        $GridActionCopy->addData('action-sk', $this->sk);
        $GridActionCopy->addData('onclick', 'account/copy');

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_ACC_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Cuenta'));
        $GridActionDel->setTitle(__('Eliminar Cuenta'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setReflectionFilter(AccountsSearchItem::class, 'isShowDelete');
        $GridActionDel->addData('action-id', self::ACTION_ACC_DELETE);
        $GridActionDel->addData('action-sk', $this->sk);
        $GridActionDel->addData('onclick', 'account/delete');

        $GridActionRequest = new DataGridAction();
        $GridActionRequest->setId(self::ACTION_ACC_REQUEST);
        $GridActionRequest->setName(__('Solicitar Modificación'));
        $GridActionRequest->setTitle(__('Solicitar Modificación'));
        $GridActionRequest->setIcon($this->icons->getIconEmail());
        $GridActionRequest->setReflectionFilter(AccountsSearchItem::class, 'isShowRequest');
        $GridActionRequest->addData('action-id', self::ACTION_ACC_REQUEST);
        $GridActionRequest->addData('action-sk', $this->sk);
        $GridActionRequest->addData('onclick', 'account/show');

        $GridActionOptional = new DataGridAction();
        $GridActionOptional->setId(0);
        $GridActionOptional->setName(__('Más Acciones'));
        $GridActionOptional->setTitle(__('Más Acciones'));
        $GridActionOptional->setIcon($this->icons->getIconOptional());
        $GridActionOptional->setReflectionFilter(AccountsSearchItem::class, 'isShowOptional');
        $GridActionOptional->addData('onclick', 'account/menu');

        $GridPager = new DataGridPager();
        $GridPager->setIconPrev($this->icons->getIconNavPrev());
        $GridPager->setIconNext($this->icons->getIconNavNext());
        $GridPager->setIconFirst($this->icons->getIconNavFirst());
        $GridPager->setIconLast($this->icons->getIconNavLast());
        $GridPager->setSortKey($this->sortKey);
        $GridPager->setSortOrder($this->sortOrder);
        $GridPager->setLimitStart($this->limitStart);
        $GridPager->setLimitCount($this->limitCount);
        $GridPager->setOnClickFunction('account/sort');
        $GridPager->setFilterOn($this->filterOn);
        $GridPager->setSourceAction(new DataGridActionSearch(self::ACTION_ACC_SEARCH));

        $UserPreferences = Session::getUserPreferences();

        $showOptionalActions = $UserPreferences->isOptionalActions() || $UserPreferences->isResultsAsCards() || ($UserPreferences->getUserId() === 0 && Checks::resultsCardsIsEnabled());

        $Grid = new DataGrid();
        $Grid->setId('gridSearch');
        $Grid->setDataHeaderTemplate('header', $this->view->getBase());
        $Grid->setDataRowTemplate('rows', $this->view->getBase());
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($this->getHeaderSort());
        $Grid->setDataActions($GridActionView);
        $Grid->setDataActions($GridActionViewPass);
        $Grid->setDataActions($GridActionCopyPass);
        $Grid->setDataActions($GridActionEdit, !$showOptionalActions);
        $Grid->setDataActions($GridActionCopy, !$showOptionalActions);
        $Grid->setDataActions($GridActionDel, !$showOptionalActions);
        $Grid->setDataActions($GridActionRequest);
        $Grid->setPager($GridPager);
        $Grid->setData(new DataGridData());

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
        $GridSortCustomer->setName(__('Cliente'))
            ->setTitle(__('Ordenar por Cliente'))
            ->setSortKey(AccountSearch::SORT_CUSTOMER)
            ->setIconUp($this->icons->getIconUp())
            ->setIconDown($this->icons->getIconDown());

        $GridSortName = new DataGridSort();
        $GridSortName->setName(__('Nombre'))
            ->setTitle(__('Ordenar por Nombre'))
            ->setSortKey(AccountSearch::SORT_NAME)
            ->setIconUp($this->icons->getIconUp())
            ->setIconDown($this->icons->getIconDown());

        $GridSortCategory = new DataGridSort();
        $GridSortCategory->setName(__('Categoría'))
            ->setTitle(__('Ordenar por Categoría'))
            ->setSortKey(AccountSearch::SORT_CATEGORY)
            ->setIconUp($this->icons->getIconUp())
            ->setIconDown($this->icons->getIconDown());

        $GridSortLogin = new DataGridSort();
        $GridSortLogin->setName(__('Usuario'))
            ->setTitle(__('Ordenar por Usuario'))
            ->setSortKey(AccountSearch::SORT_LOGIN)
            ->setIconUp($this->icons->getIconUp())
            ->setIconDown($this->icons->getIconDown());

        $GridSortUrl = new DataGridSort();
        $GridSortUrl->setName(__('URL / IP'))
            ->setTitle(__('Ordenar por URL / IP'))
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

    /**
     * Realizar las accione del controlador
     *
     * @param mixed $type Tipo de acción
     * @throws \InvalidArgumentException
     */
    public function doAction($type = null)
    {
        try {
            $this->getSearchBox();
            $this->getSearch();

            $this->EventDispatcher->notifyEvent('show.account.search', $this);
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }
}