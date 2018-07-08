<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\DataModel\ItemSearchData;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Helpers\TabsGridHelper;
use SP\Services\Account\AccountFileService;
use SP\Services\Account\AccountHistoryService;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\CustomField\CustomFieldDefService;
use SP\Services\Plugin\PluginService;
use SP\Services\Tag\TagService;

/**
 * Class ItemManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
class ItemManagerController extends ControllerBase
{
    /**
     * @var ItemSearchData
     */
    protected $itemSearchData;
    /**
     * @var ItemsGridHelper
     */
    protected $itemsGridHelper;
    /**
     * @var TabsGridHelper
     */
    protected $tabsGridHelper;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function indexAction()
    {
        $this->getGridTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getGridTabs()
    {
        $this->itemSearchData = new ItemSearchData();
        $this->itemSearchData->setLimitCount($this->configData->getAccountCount());

        $this->itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $this->tabsGridHelper = $this->dic->get(TabsGridHelper::class);

        $this->itemsGridHelper->setQueryTimeStart(microtime(true));

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

        if ($this->checkAccess(Acl::PLUGIN)) {
            $this->tabsGridHelper->addTab($this->getPluginsList());
        }

        $this->eventDispatcher->notifyEvent('show.itemlist.items', new Event($this));

        $this->tabsGridHelper->renderTabs(Acl::getActionRoute(Acl::ITEMS_MANAGE), $this->request->analyzeInt('tabIndex', 0));

        $this->view();
    }

    /**
     * Returns categories' data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getCategoriesList()
    {
        return $this->itemsGridHelper->getCategoriesGrid($this->dic->get(CategoryService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns tags' data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getTagsList()
    {
        return $this->itemsGridHelper->getTagsGrid($this->dic->get(TagService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns clients' data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getClientsList()
    {
        return $this->itemsGridHelper->getClientsGrid($this->dic->get(ClientService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns custom fields' data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getCustomFieldsList()
    {
        return $this->itemsGridHelper->getCustomFieldsGrid($this->dic->get(CustomFieldDefService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns account files' data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getAccountFilesList()
    {
        return $this->itemsGridHelper->getFilesGrid($this->dic->get(AccountFileService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns accounts' data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getAccountsList()
    {
        return $this->itemsGridHelper->getAccountsGrid($this->dic->get(AccountService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns accounts' history data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getAccountsHistoryList()
    {
        return $this->itemsGridHelper->getAccountsHistoryGrid($this->dic->get(AccountHistoryService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns plugins' data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getPluginsList()
    {
        return $this->itemsGridHelper->getPluginsGrid($this->dic->get(PluginService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * @return TabsGridHelper
     */
    public function getTabsGridHelper()
    {
        return $this->tabsGridHelper;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Services\Auth\AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();
    }
}