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

use SP\Account\AccountSearch;
use SP\Account\AccountsSearchItem;
use SP\Core\ActionsInterface;
use SP\Core\SessionUtil;
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
    /** @var  AccountSearch */
    private $search;

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
        $this->view->addTemplate('search-searchbox');

        $this->view->assign('customers', Customer::getItem()->getItemsForSelectByUser());
        $this->view->assign('categories', Category::getItem()->getItemsForSelect());
        $this->view->assign('tags', Tag::getItem()->getItemsForSelect());
    }

    /**
     * Obtener los resultados de una búsqueda
     *
     * @throws \InvalidArgumentException
     * @throws \ReflectionException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function getSearch()
    {
        $this->view->addTemplate('search-index');

        $this->view->assign('isAjax', $this->isAjax);

        $this->filterOn = ($this->search->getSortKey() > 1
            || $this->search->getCustomerId()
            || $this->search->getCategoryId()
            || $this->search->getTagsId()
            || $this->search->getTxtSearch()
            || $this->search->isSearchFavorites()
            || $this->search->isSortViews());

        $UserPreferences = $this->session->getUserPreferences();

        AccountsSearchItem::$accountLink = $UserPreferences->isAccountLink();
        AccountsSearchItem::$topNavbar = $UserPreferences->isTopNavbar();
        AccountsSearchItem::$optionalActions = $UserPreferences->isOptionalActions();
        AccountsSearchItem::$wikiEnabled = $this->configData->isWikiEnabled();
        AccountsSearchItem::$dokuWikiEnabled = $this->configData->isDokuwikiEnabled();
        AccountsSearchItem::$isDemoMode = $this->configData->isDemoEnabled();

        if (AccountsSearchItem::$wikiEnabled) {
            $wikiFilter = array_map(function ($value) {
                return preg_quote($value, '/');
            }, $this->configData->getWikiFilter());

            $this->view->assign('wikiFilter', implode('|', $wikiFilter));
            $this->view->assign('wikiPageUrl', $this->configData->getWikiPageurl());
        }

        $Grid = $this->getGrid();
        $Grid->getData()->setData($this->search->processSearchResults());
        $Grid->updatePager();
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));


        // Establecer el filtro de búsqueda en la sesión como un objeto
        $this->session->setSearchFilters($this->search);

        $this->view->assign('data', $Grid);
    }

    /**
     * Devuelve la matriz a utilizar en la vista
     *
     * @return DataGrid
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     */
    private function getGrid()
    {
        $icons = $this->view->getTheme()->getIcons();

        $GridActionView = new DataGridAction();
        $GridActionView->setId(ActionsInterface::ACTION_ACC_VIEW);
        $GridActionView->setType(DataGridActionType::VIEW_ITEM);
        $GridActionView->setName(__('Detalles de Cuenta'));
        $GridActionView->setTitle(__('Detalles de Cuenta'));
        $GridActionView->setIcon($icons->getIconView());
        $GridActionView->setReflectionFilter(AccountsSearchItem::class, 'isShowView');
        $GridActionView->addData('action-id', 'account/view');
        $GridActionView->addData('action-sk', $this->sk);
        $GridActionView->addData('onclick', 'account/view');

        $GridActionViewPass = new DataGridAction();
        $GridActionViewPass->setId(ActionsInterface::ACTION_ACC_VIEW_PASS);
        $GridActionViewPass->setType(DataGridActionType::VIEW_ITEM);
        $GridActionViewPass->setName(__('Ver Clave'));
        $GridActionViewPass->setTitle(__('Ver Clave'));
        $GridActionViewPass->setIcon($icons->getIconViewPass());
        $GridActionViewPass->setReflectionFilter(AccountsSearchItem::class, 'isShowViewPass');
        $GridActionViewPass->addData('action-id', 'account/showpass');
        $GridActionViewPass->addData('action-full', 1);
        $GridActionViewPass->addData('action-sk', $this->sk);
        $GridActionViewPass->addData('onclick', 'account/showpass');

        // Añadir la clase para usar el portapapeles
        $ClipboardIcon = $icons->getIconClipboard()->setClass('clip-pass-button');

        $GridActionCopyPass = new DataGridAction();
        $GridActionCopyPass->setId(ActionsInterface::ACTION_ACC_VIEW_PASS);
        $GridActionCopyPass->setType(DataGridActionType::VIEW_ITEM);
        $GridActionCopyPass->setName(__('Copiar Clave en Portapapeles'));
        $GridActionCopyPass->setTitle(__('Copiar Clave en Portapapeles'));
        $GridActionCopyPass->setIcon($ClipboardIcon);
        $GridActionCopyPass->setReflectionFilter(AccountsSearchItem::class, 'isShowCopyPass');
        $GridActionCopyPass->addData('action-id', 'account/showpass');
        $GridActionCopyPass->addData('action-full', 0);
        $GridActionCopyPass->addData('action-sk', $this->sk);
        $GridActionCopyPass->addData('useclipboard', '1');

        $GridActionEdit = new DataGridAction();
        $GridActionEdit->setId(ActionsInterface::ACTION_ACC_EDIT);
        $GridActionEdit->setType(DataGridActionType::EDIT_ITEM);
        $GridActionEdit->setName(__('Editar Cuenta'));
        $GridActionEdit->setTitle(__('Editar Cuenta'));
        $GridActionEdit->setIcon($icons->getIconEdit());
        $GridActionEdit->setReflectionFilter(AccountsSearchItem::class, 'isShowEdit');
        $GridActionEdit->addData('action-id', ActionsInterface::ACTION_ACC_EDIT);
        $GridActionEdit->addData('action-sk', $this->sk);
        $GridActionEdit->addData('onclick', 'account/edit');

        $GridActionCopy = new DataGridAction();
        $GridActionCopy->setId(ActionsInterface::ACTION_ACC_COPY);
        $GridActionCopy->setType(DataGridActionType::NEW_ITEM);
        $GridActionCopy->setName(__('Copiar Cuenta'));
        $GridActionCopy->setTitle(__('Copiar Cuenta'));
        $GridActionCopy->setIcon($icons->getIconCopy());
        $GridActionCopy->setReflectionFilter(AccountsSearchItem::class, 'isShowCopy');
        $GridActionCopy->addData('action-id', ActionsInterface::ACTION_ACC_COPY);
        $GridActionCopy->addData('action-sk', $this->sk);
        $GridActionCopy->addData('onclick', 'account/copy');

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(ActionsInterface::ACTION_ACC_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Cuenta'));
        $GridActionDel->setTitle(__('Eliminar Cuenta'));
        $GridActionDel->setIcon($icons->getIconDelete());
        $GridActionDel->setReflectionFilter(AccountsSearchItem::class, 'isShowDelete');
        $GridActionDel->addData('action-id', ActionsInterface::ACTION_ACC_DELETE);
        $GridActionDel->addData('action-sk', $this->sk);
        $GridActionDel->addData('onclick', 'account/delete');

        $GridActionRequest = new DataGridAction();
        $GridActionRequest->setId(ActionsInterface::ACTION_ACC_REQUEST);
        $GridActionRequest->setName(__('Solicitar Modificación'));
        $GridActionRequest->setTitle(__('Solicitar Modificación'));
        $GridActionRequest->setIcon($icons->getIconEmail());
        $GridActionRequest->setReflectionFilter(AccountsSearchItem::class, 'isShowRequest');
        $GridActionRequest->addData('action-id', ActionsInterface::ACTION_ACC_REQUEST);
        $GridActionRequest->addData('action-sk', $this->sk);
        $GridActionRequest->addData('onclick', 'account/show');

        $GridActionOptional = new DataGridAction();
        $GridActionOptional->setId(0);
        $GridActionOptional->setName(__('Más Acciones'));
        $GridActionOptional->setTitle(__('Más Acciones'));
        $GridActionOptional->setIcon($icons->getIconOptional());
        $GridActionOptional->setReflectionFilter(AccountsSearchItem::class, 'isShowOptional');
        $GridActionOptional->addData('onclick', 'account/menu');

        $GridPager = new DataGridPager();
        $GridPager->setIconPrev($icons->getIconNavPrev());
        $GridPager->setIconNext($icons->getIconNavNext());
        $GridPager->setIconFirst($icons->getIconNavFirst());
        $GridPager->setIconLast($icons->getIconNavLast());
        $GridPager->setSortKey($this->search->getSortKey());
        $GridPager->setSortOrder($this->search->getSortOrder());
        $GridPager->setLimitStart($this->search->getLimitStart());
        $GridPager->setLimitCount($this->search->getLimitCount());
        $GridPager->setOnClickFunction('account/sort');
        $GridPager->setFilterOn($this->filterOn);
        $GridPager->setSourceAction(new DataGridActionSearch(ActionsInterface::ACTION_ACC_SEARCH));

        $UserPreferences = $this->session->getUserPreferences();

        $showOptionalActions = $UserPreferences->isOptionalActions() || $UserPreferences->isResultsAsCards() || ($UserPreferences->getUserId() === 0 && $this->configData->isResultsAsCards());

        $Grid = new DataGrid();
        $Grid->setId('gridSearch');
        $Grid->setDataHeaderTemplate('search-header', $this->view->getBase());
        $Grid->setDataRowTemplate('search-rows', $this->view->getBase());
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
        $icons = $this->view->getTheme()->getIcons();

        $GridSortCustomer = new DataGridSort();
        $GridSortCustomer->setName(__('Cliente'))
            ->setTitle(__('Ordenar por Cliente'))
            ->setSortKey(AccountSearch::SORT_CUSTOMER)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $GridSortName = new DataGridSort();
        $GridSortName->setName(__('Nombre'))
            ->setTitle(__('Ordenar por Nombre'))
            ->setSortKey(AccountSearch::SORT_NAME)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $GridSortCategory = new DataGridSort();
        $GridSortCategory->setName(__('Categoría'))
            ->setTitle(__('Ordenar por Categoría'))
            ->setSortKey(AccountSearch::SORT_CATEGORY)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $GridSortLogin = new DataGridSort();
        $GridSortLogin->setName(__('Usuario'))
            ->setTitle(__('Ordenar por Usuario'))
            ->setSortKey(AccountSearch::SORT_LOGIN)
            ->setIconUp($icons->getIconUp())
            ->setIconDown($icons->getIconDown());

        $GridSortUrl = new DataGridSort();
        $GridSortUrl->setName(__('URL / IP'))
            ->setTitle(__('Ordenar por URL / IP'))
            ->setSortKey(AccountSearch::SORT_URL)
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

        $this->view->assign('isAdmin', $userData->isUserIsAdminApp() || $userData->isUserIsAdminAcc());
        $this->view->assign('showGlobalSearch', $this->configData->isGlobalSearch() && $this->session->getUserProfile()->isAccGlobalSearch());

        // Obtener el filtro de búsqueda desde la sesión
        $this->search = $this->getFilters();

        $this->view->assign('searchCustomer', $this->search->getCustomerId());
        $this->view->assign('searchCategory', $this->search->getCategoryId());
        $this->view->assign('searchTags', $this->search->getTagsId());
        $this->view->assign('searchTxt', $this->search->getTxtSearch());
        $this->view->assign('searchGlobal', $this->search->getGlobalSearch());
        $this->view->assign('searchFavorites', $this->search->isSearchFavorites());
    }

    /**
     * Set search filters
     *
     * @return AccountSearch
     */
    private function getFilters()
    {
        if (empty(Request::analyze('sk'))) {
            // Obtener el filtro de búsqueda desde la sesión
            return $this->session->getSearchFilters();
        }

        $this->search = new AccountSearch();
        $this->search->setSortKey(Request::analyze('skey', 0));
        $this->search->setSortOrder(Request::analyze('sorder', 0));
        $this->search->setLimitStart(Request::analyze('start', 0));
        $this->search->setLimitCount(Request::analyze('rpp', 0));
        $this->search->setGlobalSearch(Request::analyze('gsearch', false));
        $this->search->setCustomerId(Request::analyze('customer', 0));
        $this->search->setCategoryId(Request::analyze('category', 0));
        $this->search->setTagsId(Request::analyze('tags'));
        $this->search->setSearchFavorites(Request::analyze('searchfav', false));
        $this->search->setTxtSearch(Request::analyze('search'));

        return $this->search;
    }
}