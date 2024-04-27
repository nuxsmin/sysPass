<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Application;
use SP\Domain\Account\Adapters\AccountSearchItem;
use SP\Domain\Account\Dtos\AccountSearchFilterDto;
use SP\Domain\Account\Ports\AccountSearchConstants;
use SP\Domain\Account\Ports\AccountSearchService;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Tag\Ports\TagService;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\DataGrid;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridSort;
use SP\Html\DataGrid\Layout\DataGridHeaderSort;
use SP\Html\DataGrid\Layout\DataGridPager;
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
    private bool                          $isAjax              = false;
    private int                           $queryTimeStart;
    private bool                    $isIndex;
    private ?AccountSearchFilterDto $accountSearchFilter = null;
    private ClientService $clientService;
    private AccountSearchService    $accountSearchService;
    private AccountActionsHelper $accountActionsHelper;
    private CategoryService $categoryService;
    private TagService      $tagService;

    public function __construct(
        Application          $application,
        TemplateInterface    $template,
        RequestInterface     $request,
        ClientService        $clientService,
        CategoryService      $categoryService,
        TagService $tagService,
        AccountSearchService $accountSearchService,
        AccountActionsHelper $accountActionsHelper
    ) {
        parent::__construct($application, $template, $request);

        $this->clientService = $clientService;
        $this->categoryService = $categoryService;
        $this->tagService = $tagService;
        $this->accountSearchService = $accountSearchService;
        $this->accountActionsHelper = $accountActionsHelper;

        $this->queryTimeStart = microtime(true);
        $this->isIndex = $this->request->analyzeString('r') === Acl::getActionRoute(AclActionsInterface::ACCOUNT);
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
        $this->view->assign('searchRoute', Acl::getActionRoute(AclActionsInterface::ACCOUNT_SEARCH));
        $this->view->assign('favoriteRouteOn', Acl::getActionRoute(AclActionsInterface::ACCOUNT_FAVORITE_ADD));
        $this->view->assign('favoriteRouteOff', Acl::getActionRoute(AclActionsInterface::ACCOUNT_FAVORITE_DELETE));
        $this->view->assign('viewAccountRoute', Acl::getActionRoute(AclActionsInterface::ACCOUNT_VIEW));
    }

    /**
     * Set search filters
     *
     * @return AccountSearchFilterDto
     */
    private function getFilters(): AccountSearchFilterDto
    {
        $accountSearchFilter = $this->context->getSearchFilters();

        // Return search filters from session if accessed from menu
        if ($accountSearchFilter !== null && $this->isIndex) {
            return $accountSearchFilter;
        }

        $userPreferences = $this->context->getUserData()->getPreferences() ?? new UserPreferences();
        $limitCount = $userPreferences->getResultsPerPage() > 0
            ? $userPreferences->getResultsPerPage()
            : $this->configData->getAccountCount();

        $accountSearchFilter = new AccountSearchFilterDto();
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

    /**
     * Obtener los datos para la caja de búsqueda
     *
     * @throws ConstraintException
     * @throws QueryException
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
            SelectItemAdapter::factory($this->categoryService->getAll())
                             ->getItemsFromModelSelected([$this->accountSearchFilter->getCategoryId()])
        );
        $this->view->assign(
            'tags',
            SelectItemAdapter::factory($this->tagService->getAll())
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

        $userPreferences = $this->context->getUserData()->getPreferences() ?? new UserPreferences();

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
        $dataGrid->getData()->setData($this->accountSearchService->getByFilter($this->accountSearchFilter));
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
        $gridActionOptional->setIcon($icons->optional());
        $gridActionOptional->setRuntimeFilter(
            AccountSearchItem::class,
            'isShowOptional'
        );
        $gridActionOptional->addData('onclick', 'account/menu');

        $gridPager = new DataGridPager();
        $gridPager->setIconPrev($icons->navPrev());
        $gridPager->setIconNext($icons->navNext());
        $gridPager->setIconFirst($icons->navFirst());
        $gridPager->setIconLast($icons->navLast());
        $gridPager->setSortKey($this->accountSearchFilter->getSortKey());
        $gridPager->setSortOrder($this->accountSearchFilter->getSortOrder());
        $gridPager->setLimitStart($this->accountSearchFilter->getLimitStart());
        $gridPager->setLimitCount($this->accountSearchFilter->getLimitCount());
        $gridPager->setOnClickFunction('account/sort');
        $gridPager->setFilterOn($this->filterOn);
        $gridPager->setSourceAction(new DataGridActionSearch(AclActionsInterface::ACCOUNT_SEARCH));

        $userPreferences = $this->context->getUserData()->getPreferences() ?? new UserPreferences();
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
                         ->setSortKey(AccountSearchConstants::SORT_CLIENT)
                         ->setIconUp($icons->up())
                         ->setIconDown($icons->down());

        $gridSortName = new DataGridSort();
        $gridSortName->setName(__('Name'))
                     ->setTitle(__('Sort by Name'))
                     ->setSortKey(AccountSearchConstants::SORT_NAME)
                     ->setIconUp($icons->up())
                     ->setIconDown($icons->down());

        $gridSortCategory = new DataGridSort();
        $gridSortCategory->setName(__('Category'))
                         ->setTitle(__('Sort by Category'))
                         ->setSortKey(AccountSearchConstants::SORT_CATEGORY)
                         ->setIconUp($icons->up())
                         ->setIconDown($icons->down());

        $gridSortLogin = new DataGridSort();
        $gridSortLogin->setName(__('User'))
                      ->setTitle(__('Sort by Username'))
                      ->setSortKey(AccountSearchConstants::SORT_LOGIN)
                      ->setIconUp($icons->up())
                      ->setIconDown($icons->down());

        $gridSortUrl = new DataGridSort();
        $gridSortUrl->setName(__('URL / IP'))
                    ->setTitle(__('Sort by URL / IP'))
                    ->setSortKey(AccountSearchConstants::SORT_URL)
                    ->setIconUp($icons->up())
                    ->setIconDown($icons->down());

        $gridHeaderSort = new DataGridHeaderSort();
        $gridHeaderSort->addSortField($gridSortCustomer)
                       ->addSortField($gridSortName)
                       ->addSortField($gridSortCategory)
                       ->addSortField($gridSortLogin)
                       ->addSortField($gridSortUrl);

        return $gridHeaderSort;
    }
}
