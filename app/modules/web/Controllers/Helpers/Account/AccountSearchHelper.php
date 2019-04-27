<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\DataGrid;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridSort;
use SP\Html\DataGrid\Layout\DataGridHeaderSort;
use SP\Html\DataGrid\Layout\DataGridPager;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Account\AccountSearchFilter;
use SP\Services\Account\AccountSearchItem;
use SP\Services\Account\AccountSearchService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Tag\TagService;

/**
 * Class AccountSearch
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class AccountSearchHelper extends HelperBase
{
    /**
     * @var bool Indica si el filtrado de cuentas está activo
     */
    private $filterOn = false;
    /**
     * @var string
     */
    private $sk;
    /**
     * @var int
     */
    private $queryTimeStart = 0;
    /**
     * @var bool
     */
    private $isAjax = false;
    /**
     * @var bool
     */
    private $isIndex = false;
    /**
     * @var  AccountSearchFilter
     */
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
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getSearchBox()
    {
        $this->view->addTemplate('search-searchbox');

        $this->view->assign('clients',
            SelectItemAdapter::factory(
                $this->dic->get(ClientService::class)
                    ->getAllForUser())->getItemsFromModelSelected([$this->accountSearchFilter->getClientId()]));
        $this->view->assign('categories',
            SelectItemAdapter::factory(
                CategoryService::getItemsBasic())
                ->getItemsFromModelSelected([$this->accountSearchFilter->getCategoryId()]));
        $this->view->assign('tags',
            SelectItemAdapter::factory(
                TagService::getItemsBasic())
                ->getItemsFromModelSelected($this->accountSearchFilter->getTagsId()));
    }

    /**
     * Obtener los resultados de una búsqueda
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
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

        $userPreferences = $this->context->getUserData()->getPreferences();

        AccountSearchItem::$accountLink = $userPreferences->isAccountLink();
        AccountSearchItem::$topNavbar = $userPreferences->isTopNavbar();
        AccountSearchItem::$optionalActions = $userPreferences->isOptionalActions();
        AccountSearchItem::$wikiEnabled = $this->configData->isWikiEnabled();
        AccountSearchItem::$dokuWikiEnabled = $this->configData->isDokuwikiEnabled();
        AccountSearchItem::$publicLinkEnabled = $this->configData->isPublinksEnabled();
        AccountSearchItem::$isDemoMode = $this->configData->isDemoEnabled();
        AccountSearchItem::$showTags = $userPreferences->isShowAccountSearchFilters();

        if (AccountSearchItem::$wikiEnabled) {
            $wikiFilter = array_map(function ($value) {
                return preg_quote($value, '/');
            }, $this->configData->getWikiFilter());

            $this->view->assign('wikiFilter', implode('|', $wikiFilter));
            $this->view->assign('wikiPageUrl', $this->configData->getWikiPageurl());
        }

        $accountSearchService = $this->dic->get(AccountSearchService::class);

        $dataGrid = $this->getGrid();
        $dataGrid->getData()->setData($accountSearchService->processSearchResults($this->accountSearchFilter));
        $dataGrid->updatePager();
        $dataGrid->setTime(round(getElapsedTime($this->queryTimeStart), 5));


        // Establecer el filtro de búsqueda en la sesión como un objeto
        $this->context->setSearchFilters($this->accountSearchFilter);

        $this->view->assign('data', $dataGrid);
    }

    /**
     * Devuelve la matriz a utilizar en la vista
     *
     * @return DataGrid
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function getGrid()
    {
        $icons = $this->view->getTheme()->getIcons();

        $gridActionOptional = new DataGridAction();
        $gridActionOptional->setId(0);
        $gridActionOptional->setName(__('More Actions'));
        $gridActionOptional->setTitle(__('More Actions'));
        $gridActionOptional->setIcon($icons->getIconOptional());
        $gridActionOptional->setRuntimeFilter(AccountSearchItem::class, 'isShowOptional');
        $gridActionOptional->addData('onclick', 'account/menu');

        $gridPager = new DataGridPager();
        $gridPager->setIconPrev($icons->getIconNavPrev());
        $gridPager->setIconNext($icons->getIconNavNext());
        $gridPager->setIconFirst($icons->getIconNavFirst());
        $gridPager->setIconLast($icons->getIconNavLast());
        $gridPager->setSortKey($this->accountSearchFilter->getSortKey());
        $gridPager->setSortOrder($this->accountSearchFilter->getSortOrder());
        $gridPager->setLimitStart($this->accountSearchFilter->getLimitStart());
        $gridPager->setLimitCount($this->accountSearchFilter->getLimitCount());
        $gridPager->setOnClickFunction('account/sort');
        $gridPager->setFilterOn($this->filterOn);
        $gridPager->setSourceAction(new DataGridActionSearch(ActionsInterface::ACCOUNT_SEARCH));

        $userPreferences = $this->context->getUserData()->getPreferences();
        $showOptionalActions = $userPreferences->isOptionalActions()
            || $userPreferences->isResultsAsCards()
            || ($userPreferences->getUserId() === 0
                && $this->configData->isResultsAsCards());

        $actions = $this->dic->get(AccountActionsHelper::class);

        $dataGrid = new DataGrid($this->view->getTheme());
        $dataGrid->setId('gridSearch');
        $dataGrid->setDataHeaderTemplate('search-header', $this->view->getBase());
        $dataGrid->setDataRowTemplate('search-rows', $this->view->getBase());
        $dataGrid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $dataGrid->setHeader($this->getHeaderSort());
        $dataGrid->addDataAction($actions->getViewAction());
        $dataGrid->addDataAction($actions->getViewPassAction());
        $dataGrid->addDataAction($actions->getCopyPassAction());
        $dataGrid->addDataAction($actions->getEditAction(), !$showOptionalActions);
        $dataGrid->addDataAction($actions->getCopyAction(), !$showOptionalActions);
        $dataGrid->addDataAction($actions->getDeleteAction(), !$showOptionalActions);
        $dataGrid->addDataAction($actions->getRequestAction());
        $dataGrid->setPager($gridPager);
        $dataGrid->setData(new DataGridData());

        return $dataGrid;
    }

    /**
     * Devolver la cabecera con los campos de ordenación
     *
     * @return DataGridHeaderSort
     */
    private function getHeaderSort()
    {
        $icons = $this->view->getTheme()->getIcons();

        $gridSortCustomer = new DataGridSort();
        $gridSortCustomer->setName(__('Client'))
            ->setTitle(__('Sort by Client'))
            ->setSortKey(AccountSearchFilter::SORT_CLIENT)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $gridSortName = new DataGridSort();
        $gridSortName->setName(__('Name'))
            ->setTitle(__('Sort by Name'))
            ->setSortKey(AccountSearchFilter::SORT_NAME)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $gridSortCategory = new DataGridSort();
        $gridSortCategory->setName(__('Category'))
            ->setTitle(__('Sort by Category'))
            ->setSortKey(AccountSearchFilter::SORT_CATEGORY)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $gridSortLogin = new DataGridSort();
        $gridSortLogin->setName(__('User'))
            ->setTitle(__('Sort by Username'))
            ->setSortKey(AccountSearchFilter::SORT_LOGIN)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $gridSortUrl = new DataGridSort();
        $gridSortUrl->setName(__('URL / IP'))
            ->setTitle(__('Sort by URL / IP'))
            ->setSortKey(AccountSearchFilter::SORT_URL)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $gridHeaderSort = new DataGridHeaderSort();
        $gridHeaderSort->addSortField($gridSortCustomer)
            ->addSortField($gridSortName)
            ->addSortField($gridSortCategory)
            ->addSortField($gridSortLogin)
            ->addSortField($gridSortUrl);

        return $gridHeaderSort;
    }

    /**
     * Initialize
     */
    protected function initialize()
    {
        $this->queryTimeStart = microtime(true);
        $this->sk = $this->view->get('sk');
        $this->isIndex = $this->request->analyzeString('r') === Acl::getActionRoute(ActionsInterface::ACCOUNT);
        $this->setVars();
    }

    /**
     * Establecer las variables necesarias para las plantillas
     */
    private function setVars()
    {
        $userData = $this->context->getUserData();

        $this->view->assign('isAdmin',
            $userData->getIsAdminApp()
            || $userData->getIsAdminAcc());
        $this->view->assign('showGlobalSearch',
            $this->configData->isGlobalSearch()
            && $this->context->getUserProfile()->isAccGlobalSearch());

        $this->accountSearchFilter = $this->getFilters();

        $this->view->assign('searchCustomer', $this->accountSearchFilter->getClientId());
        $this->view->assign('searchCategory', $this->accountSearchFilter->getCategoryId());
        $this->view->assign('searchTags', $this->accountSearchFilter->getTagsId());
        $this->view->assign('searchTxt', $this->accountSearchFilter->getTxtSearch());
        $this->view->assign('searchGlobal', $this->accountSearchFilter->getGlobalSearch());
        $this->view->assign('searchFavorites', $this->accountSearchFilter->isSearchFavorites());

        $this->view->assign('searchRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_SEARCH));
        $this->view->assign('favoriteRouteOn', Acl::getActionRoute(ActionsInterface::ACCOUNT_FAVORITE_ADD));
        $this->view->assign('favoriteRouteOff', Acl::getActionRoute(ActionsInterface::ACCOUNT_FAVORITE_DELETE));
        $this->view->assign('viewAccountRoute', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW));
    }

    /**
     * Set search filters
     *
     * @return AccountSearchFilter
     */
    private function getFilters()
    {
        $accountSearchFilter = $this->context->getSearchFilters();

        // Return search filters from session if accessed from menu
        if ($accountSearchFilter !== null && $this->isIndex) {
            return $accountSearchFilter;
        }

        $userPreferences = $this->context->getUserData()->getPreferences();
        $limitCount = $userPreferences->getResultsPerPage() > 0
            ? $userPreferences->getResultsPerPage()
            : $this->configData->getAccountCount();

        $accountSearchFilter = new AccountSearchFilter();
        $accountSearchFilter->setSortKey($this->request->analyzeInt('skey', 0));
        $accountSearchFilter->setSortOrder($this->request->analyzeInt('sorder', 0));
        $accountSearchFilter->setLimitStart($this->request->analyzeInt('start', 0));
        $accountSearchFilter->setLimitCount($this->request->analyzeInt('rpp', $limitCount));
        $accountSearchFilter->setGlobalSearch($this->request->analyzeBool('gsearch', false));
        $accountSearchFilter->setClientId($this->request->analyzeInt('client', 0));
        $accountSearchFilter->setCategoryId($this->request->analyzeInt('category', 0));
        $accountSearchFilter->setTagsId($this->request->analyzeArray('tags'));
        $accountSearchFilter->setSearchFavorites($this->request->analyzeBool('searchfav', false));
        $accountSearchFilter->setTxtSearch($this->request->analyzeString('search'));
        $accountSearchFilter->setSortViews($userPreferences->isSortViews());

        return $accountSearchFilter;
    }
}