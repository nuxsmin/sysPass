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

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\DataModel\ItemSearchData;
use SP\Html\DataGrid\DataGridTab;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountHistoryGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\CategoryGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\ClientGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\CustomFieldGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\FileGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\ItemPresetGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\TagGrid;
use SP\Modules\Web\Controllers\Helpers\TabsGridHelper;
use SP\Services\Account\AccountFileService;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Account\AccountService;
use SP\Services\Auth\AuthException;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\CustomField\CustomFieldDefService;
use SP\Services\ItemPreset\ItemPresetService;
use SP\Services\Tag\TagService;

/**
 * Class ItemManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ItemManagerController extends ControllerBase
{
    /**
     * @var ItemSearchData
     */
    protected $itemSearchData;
    /**
     * @var TabsGridHelper
     */
    protected $tabsGridHelper;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function indexAction()
    {
        $this->getGridTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getGridTabs()
    {
        $this->itemSearchData = new ItemSearchData();
        $this->itemSearchData->setLimitCount($this->configData->getAccountCount());

        $this->tabsGridHelper = $this->dic->get(TabsGridHelper::class);

        if ($this->checkAccess(Acl::CATEGORY)) {
            $this->tabsGridHelper->addTab($this->getCategoriesList());
        }

        if ($this->checkAccess(Acl::TAG)) {
            $this->tabsGridHelper->addTab($this->getTagsList());
        }

        if ($this->checkAccess(Acl::CLIENT)) {
            $this->tabsGridHelper->addTab($this->getClientsList());
        }

        if ($this->checkAccess(Acl::CUSTOMFIELD)) {
            $this->tabsGridHelper->addTab($this->getCustomFieldsList());
        }

        if ($this->configData->isFilesEnabled() && $this->checkAccess(Acl::FILE)) {
            $this->tabsGridHelper->addTab($this->getAccountFilesList());
        }

        if ($this->checkAccess(Acl::ACCOUNTMGR)) {
            $this->tabsGridHelper->addTab($this->getAccountsList());
        }

        if ($this->checkAccess(Acl::ACCOUNTMGR_HISTORY)) {
            $this->tabsGridHelper->addTab($this->getAccountsHistoryList());
        }

        if ($this->checkAccess(Acl::ITEMPRESET)) {
            $this->tabsGridHelper->addTab($this->getItemPresetList());
        }

        $this->eventDispatcher->notifyEvent('show.itemlist.items', new Event($this));

        $this->tabsGridHelper->renderTabs(Acl::getActionRoute(Acl::ITEMS_MANAGE), $this->request->analyzeInt('tabIndex', 0));

        $this->view();
    }

    /**
     * Returns categories' data tab
     *
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getCategoriesList()
    {
        return $this->dic->get(CategoryGrid::class)
            ->getGrid($this->dic->get(CategoryService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns tags' data tab
     *
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getTagsList()
    {
        return $this->dic->get(TagGrid::class)
            ->getGrid($this->dic->get(TagService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns clients' data tab
     *
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getClientsList()
    {
        return $this->dic->get(ClientGrid::class)
            ->getGrid($this->dic->get(ClientService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns custom fields' data tab
     *
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getCustomFieldsList()
    {
        return $this->dic->get(CustomFieldGrid::class)
            ->getGrid($this->dic->get(CustomFieldDefService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns account files' data tab
     *
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getAccountFilesList()
    {
        return $this->dic->get(FileGrid::class)
            ->getGrid($this->dic->get(AccountFileService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns accounts' data tab
     *
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getAccountsList()
    {
        return $this->dic->get(AccountGrid::class)
            ->getGrid($this->dic->get(AccountService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns accounts' history data tab
     *
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getAccountsHistoryList()
    {
        return $this->dic->get(AccountHistoryGrid::class)
            ->getGrid($this->dic->get(AccountHistoryService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns API tokens data tab
     *
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getItemPresetList()
    {
        return $this->dic->get(ItemPresetGrid::class)
            ->getGrid($this->dic->get(ItemPresetService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * @return TabsGridHelper
     */
    public function getTabsGridHelper()
    {
        return $this->tabsGridHelper;
    }

    /**
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        $this->checkLoggedIn();
    }
}