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

namespace SP\Modules\Web\Controllers\ItemManager;

use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Account\Ports\AccountFileService;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionService;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Domain\Tag\Ports\TagService;
use SP\Html\DataGrid\DataGridTab;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountHistoryGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\CategoryGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\ClientGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\CustomFieldGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\FileGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\ItemPresetGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\TagGrid;
use SP\Modules\Web\Controllers\Helpers\TabsGridHelper;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class ItemManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class IndexController extends ControllerBase
{
    protected ?ItemSearchDto $itemSearchData = null;
    private TabsGridHelper   $tabsGridHelper;
    private CategoryService $categoryService;
    private TagService      $tagService;
    private ClientService   $clientService;
    private CustomFieldDefinitionService $customFieldDefService;
    private AccountFileService           $accountFileService;
    private AccountService             $accountService;
    private AccountHistoryService $accountHistoryService;
    private ItemPresetService     $itemPresetService;
    private CategoryGrid          $categoryGrid;
    private TagGrid                        $tagGrid;
    private ClientGrid                     $clientGrid;
    private CustomFieldGrid                $customFieldGrid;
    private FileGrid                       $fileGrid;
    private AccountGrid                    $accountGrid;
    private AccountHistoryGrid             $accountHistoryGrid;
    private ItemPresetGrid                 $itemPresetGrid;

    public function __construct(
        Application                  $application,
        WebControllerHelper          $webControllerHelper,
        Helpers\TabsGridHelper       $tabsGridHelper,
        CategoryService              $categoryService,
        TagService $tagService,
        ClientService                $clientService,
        CustomFieldDefinitionService $customFieldDefService,
        AccountFileService           $accountFileService,
        AccountService               $accountService,
        AccountHistoryService        $accountHistoryService,
        ItemPresetService            $itemPresetService,
        Helpers\Grid\CategoryGrid    $categoryGrid,
        Helpers\Grid\TagGrid         $tagGrid,
        Helpers\Grid\ClientGrid      $clientGrid,
        Helpers\Grid\CustomFieldGrid $customFieldGrid,
        Helpers\Grid\FileGrid        $fileGrid,
        Helpers\Grid\AccountGrid     $accountGrid,
        Helpers\Grid\AccountHistoryGrid $accountHistoryGrid,
        Helpers\Grid\ItemPresetGrid  $itemPresetGrid
    ) {
        $this->tabsGridHelper = $tabsGridHelper;
        $this->categoryService = $categoryService;
        $this->tagService = $tagService;
        $this->clientService = $clientService;
        $this->customFieldDefService = $customFieldDefService;
        $this->accountFileService = $accountFileService;
        $this->accountService = $accountService;
        $this->accountHistoryService = $accountHistoryService;
        $this->itemPresetService = $itemPresetService;
        $this->categoryGrid = $categoryGrid;
        $this->tagGrid = $tagGrid;
        $this->clientGrid = $clientGrid;
        $this->customFieldGrid = $customFieldGrid;
        $this->fileGrid = $fileGrid;
        $this->accountGrid = $accountGrid;
        $this->accountHistoryGrid = $accountHistoryGrid;
        $this->itemPresetGrid = $itemPresetGrid;

        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function indexAction(): void
    {
        $this->getGridTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getGridTabs(): void
    {
        $this->itemSearchData = new ItemSearchDto(null, 0, $this->configData->getAccountCount());

        if ($this->checkAccess(AclActionsInterface::CATEGORY)) {
            $this->tabsGridHelper->addTab($this->getCategoriesList());
        }

        if ($this->checkAccess(AclActionsInterface::TAG)) {
            $this->tabsGridHelper->addTab($this->getTagsList());
        }

        if ($this->checkAccess(AclActionsInterface::CLIENT)) {
            $this->tabsGridHelper->addTab($this->getClientsList());
        }

        if ($this->checkAccess(AclActionsInterface::CUSTOMFIELD)) {
            $this->tabsGridHelper->addTab($this->getCustomFieldsList());
        }

        if ($this->configData->isFilesEnabled()
            && $this->checkAccess(AclActionsInterface::FILE)) {
            $this->tabsGridHelper->addTab($this->getAccountFilesList());
        }

        if ($this->checkAccess(AclActionsInterface::ACCOUNTMGR)) {
            $this->tabsGridHelper->addTab($this->getAccountsList());
        }

        if ($this->checkAccess(AclActionsInterface::ACCOUNTMGR_HISTORY)) {
            $this->tabsGridHelper->addTab($this->getAccountsHistoryList());
        }

        if ($this->checkAccess(AclActionsInterface::ITEMPRESET)) {
            $this->tabsGridHelper->addTab($this->getItemPresetList());
        }

        $this->eventDispatcher->notify(
            'show.itemlist.items',
            new Event($this)
        );

        $this->tabsGridHelper->renderTabs(
            Acl::getActionRoute(AclActionsInterface::ITEMS_MANAGE),
            $this->request->analyzeInt('tabIndex', 0)
        );

        $this->view();
    }

    /**
     * Returns categories' data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getCategoriesList(): DataGridTab
    {
        return $this->categoryGrid->getGrid($this->categoryService->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns tags' data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getTagsList(): DataGridTab
    {
        return $this->tagGrid->getGrid($this->tagService->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns clients' data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getClientsList(): DataGridTab
    {
        return $this->clientGrid->getGrid($this->clientService->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns custom fields' data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getCustomFieldsList(): DataGridTab
    {
        return $this->customFieldGrid->getGrid($this->customFieldDefService->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns account files' data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getAccountFilesList(): DataGridTab
    {
        return $this->fileGrid->getGrid($this->accountFileService->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns accounts' data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getAccountsList(): DataGridTab
    {
        return $this->accountGrid->getGrid($this->accountService->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns accounts' history data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getAccountsHistoryList(): DataGridTab
    {
        return $this->accountHistoryGrid->getGrid($this->accountHistoryService->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns API tokens data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getItemPresetList(): DataGridTab
    {
        return $this->itemPresetGrid->getGrid($this->itemPresetService->search($this->itemSearchData))->updatePager();
    }

    /**
     * @return TabsGridHelper
     */
    public function getTabsGridHelper(): TabsGridHelper
    {
        return $this->tabsGridHelper;
    }
}
