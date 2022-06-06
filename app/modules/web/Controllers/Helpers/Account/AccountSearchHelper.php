<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Modules\Web\Controllers\Helpers\Account;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ProfileData;
use SP\DataModel\UserPreferencesData;
use SP\Domain\Account\Services\AccountSearchFilter;
use SP\Domain\Account\Services\AccountSearchItem;
use SP\Domain\Category\Services\CategoryService;
use SP\Domain\Client\ClientServiceInterface;
use SP\Domain\Tag\Services\TagService;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\DataGrid;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridSort;
use SP\Html\DataGrid\Layout\DataGridHeaderSort;
use SP\Html\DataGrid\Layout\DataGridPager;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\TemplateInterface;

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
    private bool                          $filterOn            = false;
    private int                           $queryTimeStart      = 0;
    private bool                          $isAjax              = false;
    private bool                          $isIndex             = false;
    private ?AccountSearchFilter          $accountSearchFilter = null;
    private ClientServiceInterface                           $clientService;
    private \SP\Domain\Account\AccountSearchServiceInterface $accountSearchService;
    private AccountActionsHelper                             $accountActionsHelper;

    public function __construct(
        Application $application,
        TemplateInterface $template,
        RequestInterface $request,
        ClientServiceInterface $clientService,
        \SP\Domain\Account\AccountSearchServiceInterface $accountSearchService,
        AccountActionsHelper $accountActionsHelper
    ) {
        parent::__construct($application, $template, $request);

        $this->clientService = $clientService;
        $this->accountSearchService = $accountSearchService;
        $this->accountActionsHelper = $accountActionsHelper;
    }

    /**
     * Obtener los datos para la caja de búsqueda
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getSearchBox(): void
    {
        $this->view->addTemplate('search-searchbox');

        $this->view->assign(
            'clients',
            SelectItemAdapter::factory($this->clientService->getAllForUser())
                ->getItemsFromModelSelected(
                    [$this->accountSearchFilter->getClientId()]
                )
        );
        $this->view->assign(
            'categories',
            SelectItemAdapter::factory(CategoryService::getItemsBasic())
                ->getItemsFromModelSelected([$this->accountSearchFilter->getCategoryId()])
        );
        $this->view->assign(
            'tags',
            SelectItemAdapter::factory(TagService::getItemsBasic())
                ->getItemsFromModelSelected($this->accountSearchFilter->getTagsId())
        );
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
    public function getAccountSearch(): void
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

        $userPreferences = $this->context->getUserData()->getPreferences() ?? new UserPreferencesData();

        AccountSearchItem::$accountLink = $userPreferences->isAccountLink();
        AccountSearchItem::$topNavbar = $userPreferences->isTopNavbar();
        AccountSearchItem::$optionalActions = $userPreferences->isOptionalActions();
        AccountSearchItem::$wikiEnabled = $this->configData->isWikiEnabled();
        AccountSearchItem::$dokuWikiEnabled = $this->configData->isDokuwikiEnabled();
        AccountSearchItem::$publicLinkEnabled = $this->configData->isPublinksEnabled();
        AccountSearchItem::$isDemoMode = $this->configData->isDemoEnabled();
        AccountSearchItem::$showTags = $userPreferences->isShowAccountSearchFilters();

        if (AccountSearchItem::$wikiEnabled) {
            $wikiFilter = array_map(
                static function ($value) {
                    return preg_quote($value, '/');
                },
                $this->configData->getWikiFilter()
            );

            $this->view->assign(
                'wikiFilter',
                implode('|', $wikiFilter)
            );
            $this->view->assign(
                'wikiPageUrl',
                $this->configData->getWikiPageurl()
            );
        }

        $dataGrid = $this->getGrid();
        $dataGrid->getData()->setData($this->accountSearchService->processSearchResults($this->accountSearchFilter));
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
    private function getGrid(): DataGrid
    {
        $icons = $this->view->getTheme()->getIcons();

        $gridActionOptional = new DataGridAction();
        $gridActionOptional->setId(0);
        $gridActionOptional->setName(__('More Actions'));
        $gridActionOptional->setTitle(__('More Actions'));
        $gridActionOptional->setIcon($icons->getIconOptional());
        $gridActionOptional->setRuntimeFilter(
            AccountSearchItem::class,
            'isShowOptional'
        );
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

        $userPreferences = $this->context->getUserData()->getPreferences() ?? new UserPreferencesData();
        $showOptionalActions = $userPreferences->isOptionalActions()
                               || $userPreferences->isResultsAsCards()
                               || ($userPreferences->getUserId() === 0
                                   && $this->configData->isResultsAsCards());

        $dataGrid = new DataGrid($this->view->getTheme());
        $dataGrid->setId('gridSearch');
        $dataGrid->setDataHeaderTemplate('account/search-header');
        $dataGrid->setDataRowTemplate(
            'search-rows',
            $this->view->getBase()
        );
        $dataGrid->setDataPagerTemplate(
            'datagrid-nav-full',
            'grid'
        );
        $dataGrid->setHeader($this->getHeaderSort());
        $dataGrid->addDataAction($this->accountActionsHelper->getViewAction());
        $dataGrid->addDataAction($this->accountActionsHelper->getViewPassAction());
        $dataGrid->addDataAction($this->accountActionsHelper->getCopyPassAction());
        $dataGrid->addDataAction(
            $this->accountActionsHelper->getEditAction(),
            !$showOptionalActions
        );
        $dataGrid->addDataAction(
            $this->accountActionsHelper->getCopyAction(),
            !$showOptionalActions
        );
        $dataGrid->addDataAction(
            $this->accountActionsHelper->getDeleteAction(),
            !$showOptionalActions
        );
        $dataGrid->addDataAction($this->accountActionsHelper->getRequestAction());
        $dataGrid->setPager($gridPager);
        $dataGrid->setData(new DataGridData());

        return $dataGrid;
    }

    /**
     * Devolver la cabecera con los campos de ordenación
     *
     * @return DataGridHeaderSort
     */
    private function getHeaderSort(): DataGridHeaderSort
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
    protected function initialize(): void
    {
        $this->queryTimeStart = microtime(true);
        $this->isIndex = $this->request->analyzeString('r') === Acl::getActionRoute(ActionsInterface::ACCOUNT);
        $this->setVars();
    }

    /**
     * Establecer las variables necesarias para las plantillas
     */
    private function setVars(): void
    {
        $userData = $this->context->getUserData();

        $this->view->assign('isAdmin', $userData->getIsAdminApp() || $userData->getIsAdminAcc());

        $profileData = $this->context->getUserProfile() ?? new ProfileData();

        $this->view->assign(
            'showGlobalSearch',
            $this->configData->isGlobalSearch() && $profileData->isAccGlobalSearch()
        );

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
    private function getFilters(): AccountSearchFilter
    {
        $accountSearchFilter = $this->context->getSearchFilters();

        // Return search filters from session if accessed from menu
        if ($accountSearchFilter !== null && $this->isIndex) {
            return $accountSearchFilter;
        }

        $userPreferences = $this->context->getUserData()->getPreferences() ?? new UserPreferencesData();
        $limitCount = $userPreferences->getResultsPerPage() > 0
            ? $userPreferences->getResultsPerPage()
            : $this->configData->getAccountCount();

        $accountSearchFilter = new AccountSearchFilter();
        $accountSearchFilter->setSortKey($this->request->analyzeInt('skey', 0));
        $accountSearchFilter->setSortOrder($this->request->analyzeInt('sorder', 0));
        $accountSearchFilter->setLimitStart($this->request->analyzeInt('start', 0));
        $accountSearchFilter->setLimitCount($this->request->analyzeInt('rpp', $limitCount));
        $accountSearchFilter->setGlobalSearch($this->request->analyzeBool('gsearch', false));
        $accountSearchFilter->setClientId($this->request->analyzeInt('client'));
        $accountSearchFilter->setCategoryId($this->request->analyzeInt('category'));
        $accountSearchFilter->setTagsId($this->request->analyzeArray('tags', null, []));
        $accountSearchFilter->setSearchFavorites($this->request->analyzeBool('searchfav', false));
        $accountSearchFilter->setTxtSearch($this->request->analyzeString('search'));
        $accountSearchFilter->setSortViews($userPreferences->isSortViews());

        return $accountSearchFilter;
    }
}