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

use SP\Account\AccountSearchFilter;
use SP\Account\AccountSearchItem;
use SP\Core\Acl\ActionsInterface;
use SP\Core\SessionUtil;
use SP\Html\DataGrid\DataGrid;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeaderSort;
use SP\Html\DataGrid\DataGridPager;
use SP\Html\DataGrid\DataGridSort;
use SP\Http\Request;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Repositories\Category\CategoryRepository;
use SP\Repositories\Client\ClientRepository;
use SP\Repositories\Tag\TagRepository;
use SP\Services\Account\AccountSearchService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Tag\TagService;

/**
 * Class AccountSearch
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
class AccountSearchHelper extends HelperBase
{
    /** @var bool Indica si el filtrado de cuentas está activo */
    private $filterOn = false;
    /** @var string */
    private $sk;
    /** @var int */
    private $queryTimeStart = 0;
    /** @var bool */
    private $isAjax = false;
    /** @var  AccountSearchFilter */
    private $accountSearchFilter;

    /**
     * @param boolean $isAjax
     */
    public function setIsAjax($isAjax)
    {
        $this->isAjax = $isAjax;
    }

    /**
     * Obtener los datos para la caja de búsqueda
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function getSearchBox()
    {
        $this->view->addTemplate('search-searchbox');

        $this->view->assign('clients', (new SelectItemAdapter((new ClientService())->getAllForUser()))->getItemsFromModelSelected([$this->accountSearchFilter->getClientId()]));
        $this->view->assign('categories', (new SelectItemAdapter(CategoryService::getItemsBasic()))->getItemsFromModelSelected([$this->accountSearchFilter->getCategoryId()]));
        $this->view->assign('tags', (new SelectItemAdapter(TagService::getItemsBasic()))->getItemsFromModelSelected($this->accountSearchFilter->getTagsId()));
    }

    /**
     * Obtener los resultados de una búsqueda
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAccountSearch()
    {
        $this->view->addTemplate('search-index');

        $this->view->assign('isAjax', $this->isAjax);

        $this->filterOn = ($this->accountSearchFilter->getSortKey() > 1
            || $this->accountSearchFilter->getClientId()
            || $this->accountSearchFilter->getCategoryId()
            || $this->accountSearchFilter->getTagsId()
            || $this->accountSearchFilter->getTxtSearch()
            || $this->accountSearchFilter->isSearchFavorites()
            || $this->accountSearchFilter->isSortViews());

        $userPreferences = $this->session->getUserData()->getPreferences();

        AccountSearchItem::$accountLink = $userPreferences->isAccountLink();
        AccountSearchItem::$topNavbar = $userPreferences->isTopNavbar();
        AccountSearchItem::$optionalActions = $userPreferences->isOptionalActions();
        AccountSearchItem::$wikiEnabled = $this->configData->isWikiEnabled();
        AccountSearchItem::$dokuWikiEnabled = $this->configData->isDokuwikiEnabled();
        AccountSearchItem::$isDemoMode = $this->configData->isDemoEnabled();

        if (AccountSearchItem::$wikiEnabled) {
            $wikiFilter = array_map(function ($value) {
                return preg_quote($value, '/');
            }, $this->configData->getWikiFilter());

            $this->view->assign('wikiFilter', implode('|', $wikiFilter));
            $this->view->assign('wikiPageUrl', $this->configData->getWikiPageurl());
        }

        $accountSearchService = new AccountSearchService();

        $Grid = $this->getGrid();
        $Grid->getData()->setData($accountSearchService->processSearchResults($this->accountSearchFilter));
        $Grid->updatePager();
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));


        // Establecer el filtro de búsqueda en la sesión como un objeto
        $this->session->setSearchFilters($this->accountSearchFilter);

        $this->view->assign('data', $Grid);
    }

    /**
     * Devuelve la matriz a utilizar en la vista
     *
     * @return DataGrid
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function getGrid()
    {
        $icons = $this->view->getTheme()->getIcons();

        $GridActionOptional = new DataGridAction();
        $GridActionOptional->setId(0);
        $GridActionOptional->setName(__('Más Acciones'));
        $GridActionOptional->setTitle(__('Más Acciones'));
        $GridActionOptional->setIcon($icons->getIconOptional());
        $GridActionOptional->setReflectionFilter(AccountSearchItem::class, 'isShowOptional');
        $GridActionOptional->addData('onclick', 'account/menu');

        $GridPager = new DataGridPager();
        $GridPager->setIconPrev($icons->getIconNavPrev());
        $GridPager->setIconNext($icons->getIconNavNext());
        $GridPager->setIconFirst($icons->getIconNavFirst());
        $GridPager->setIconLast($icons->getIconNavLast());
        $GridPager->setSortKey($this->accountSearchFilter->getSortKey());
        $GridPager->setSortOrder($this->accountSearchFilter->getSortOrder());
        $GridPager->setLimitStart($this->accountSearchFilter->getLimitStart());
        $GridPager->setLimitCount($this->accountSearchFilter->getLimitCount());
        $GridPager->setOnClickFunction('account/sort');
        $GridPager->setFilterOn($this->filterOn);
        $GridPager->setSourceAction(new DataGridActionSearch(ActionsInterface::ACCOUNT_SEARCH));

        $userPreferences = $this->session->getUserData()->getPreferences();
        $showOptionalActions = $userPreferences->isOptionalActions() || $userPreferences->isResultsAsCards() || ($userPreferences->getUserId() === 0 && $this->configData->isResultsAsCards());

        $actions = new AccountActionsHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        $Grid = new DataGrid();
        $Grid->setId('gridSearch');
        $Grid->setDataHeaderTemplate('search-header', $this->view->getBase());
        $Grid->setDataRowTemplate('search-rows', $this->view->getBase());
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($this->getHeaderSort());
        $Grid->setDataActions($actions->getViewAction());
        $Grid->setDataActions($actions->getViewPassAction());
        $Grid->setDataActions($actions->getCopyPassAction());
        $Grid->setDataActions($actions->getEditAction(), !$showOptionalActions);
        $Grid->setDataActions($actions->getCopyAction(), !$showOptionalActions);
        $Grid->setDataActions($actions->getDeleteAction(), !$showOptionalActions);
        $Grid->setDataActions($actions->getRequestAction());
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
        $icons = $this->view->getTheme()->getIcons();

        $GridSortCustomer = new DataGridSort();
        $GridSortCustomer->setName(__('Cliente'))
            ->setTitle(__('Ordenar por Cliente'))
            ->setSortKey(AccountSearchFilter::SORT_CLIENT)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $GridSortName = new DataGridSort();
        $GridSortName->setName(__('Nombre'))
            ->setTitle(__('Ordenar por Nombre'))
            ->setSortKey(AccountSearchFilter::SORT_NAME)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $GridSortCategory = new DataGridSort();
        $GridSortCategory->setName(__('Categoría'))
            ->setTitle(__('Ordenar por Categoría'))
            ->setSortKey(AccountSearchFilter::SORT_CATEGORY)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $GridSortLogin = new DataGridSort();
        $GridSortLogin->setName(__('Usuario'))
            ->setTitle(__('Ordenar por Usuario'))
            ->setSortKey(AccountSearchFilter::SORT_LOGIN)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $GridSortUrl = new DataGridSort();
        $GridSortUrl->setName(__('URL / IP'))
            ->setTitle(__('Ordenar por URL / IP'))
            ->setSortKey(AccountSearchFilter::SORT_URL)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $GridHeaderSort = new DataGridHeaderSort();
        $GridHeaderSort->addSortField($GridSortCustomer)
            ->addSortField($GridSortName)
            ->addSortField($GridSortCategory)
            ->addSortField($GridSortLogin)
            ->addSortField($GridSortUrl);

        return $GridHeaderSort;
    }

    /**
     * Initialize
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function initialize()
    {
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
        $userData = $this->session->getUserData();

        $this->view->assign('isAdmin', $userData->getIsAdminApp() || $userData->getIsAdminAcc());
        $this->view->assign('showGlobalSearch', $this->configData->isGlobalSearch() && $this->session->getUserProfile()->isAccGlobalSearch());

        // Obtener el filtro de búsqueda desde la sesión
        $this->accountSearchFilter = $this->getFilters();

        $this->view->assign('searchCustomer', $this->accountSearchFilter->getClientId());
        $this->view->assign('searchCategory', $this->accountSearchFilter->getCategoryId());
        $this->view->assign('searchTags', $this->accountSearchFilter->getTagsId());
        $this->view->assign('searchTxt', $this->accountSearchFilter->getTxtSearch());
        $this->view->assign('searchGlobal', $this->accountSearchFilter->getGlobalSearch());
        $this->view->assign('searchFavorites', $this->accountSearchFilter->isSearchFavorites());
    }

    /**
     * Set search filters
     *
     * @return AccountSearchFilter
     */
    private function getFilters()
    {
        $accountSearchFilter = $this->session->getSearchFilters();

        if ($accountSearchFilter !== null && empty(Request::analyze('sk'))) {
            // Obtener el filtro de búsqueda desde la sesión
            return $accountSearchFilter;
        }

        $userPreferences = $this->session->getUserData()->getPreferences();
        $limitCount = ($userPreferences->getResultsPerPage() > 0) ? $userPreferences->getResultsPerPage() : $this->configData->getAccountCount();

        $accountSearchFilter = new AccountSearchFilter();
        $accountSearchFilter->setSortKey(Request::analyze('skey', 0));
        $accountSearchFilter->setSortOrder(Request::analyze('sorder', 0));
        $accountSearchFilter->setLimitStart(Request::analyze('start', 0));
        $accountSearchFilter->setLimitCount(Request::analyze('rpp', $limitCount));
        $accountSearchFilter->setGlobalSearch(Request::analyze('gsearch', false));
        $accountSearchFilter->setClientId(Request::analyze('customer', 0));
        $accountSearchFilter->setCategoryId(Request::analyze('category', 0));
        $accountSearchFilter->setTagsId(Request::analyze('tags'));
        $accountSearchFilter->setSearchFavorites(Request::analyze('searchfav', false));
        $accountSearchFilter->setTxtSearch(Request::analyze('search'));
        $accountSearchFilter->setSortViews($userPreferences->isSortViews());

        return $accountSearchFilter;
    }
}