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

namespace SP\Modules\Web\Controllers;

use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\DataModel\ItemSearchData;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Helpers\TabsGridHelper;
use SP\Repositories\Account\AccountFileRepository;
use SP\Repositories\Account\AccountHistoryRepository;
use SP\Repositories\Account\AccountRepository;
use SP\Repositories\Category\CategoryRepository;
use SP\Repositories\Client\ClientRepository;
use SP\Repositories\CustomField\CustomFieldDefRepository;
use SP\Repositories\Plugin\PluginRepository;
use SP\Repositories\Tag\TagRepository;

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
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Dic\ContainerException
     */
    public function indexAction()
    {
        $this->getGridTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getGridTabs()
    {
        $this->itemSearchData = new ItemSearchData();
        $this->itemSearchData->setLimitCount($this->configData->getAccountCount());

        $this->itemsGridHelper = new ItemsGridHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        $this->tabsGridHelper = new TabsGridHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        if ($this->checkAccess(ActionsInterface::CATEGORY)) {
            $this->tabsGridHelper->addTab($this->getCategoriesList());
        }

        if ($this->checkAccess(ActionsInterface::TAG)) {
            $this->tabsGridHelper->addTab($this->getTagsList());
        }

        if ($this->checkAccess(ActionsInterface::CLIENT)) {
            $this->tabsGridHelper->addTab($this->getClientsList());
        }

        if ($this->checkAccess(ActionsInterface::CUSTOMFIELD)) {
            $this->tabsGridHelper->addTab($this->getCustomFieldsList());
        }

        if ($this->checkAccess(ActionsInterface::FILE)) {
            $this->tabsGridHelper->addTab($this->getAccountFilesList());
        }

        if ($this->checkAccess(ActionsInterface::ACCOUNTMGR)) {
            $this->tabsGridHelper->addTab($this->getAccountsList());
        }

        if ($this->checkAccess(ActionsInterface::ACCOUNTMGR_HISTORY)) {
            $this->tabsGridHelper->addTab($this->getAccountsHistoryList());
        }

        if ($this->checkAccess(ActionsInterface::PLUGIN)) {
            $this->tabsGridHelper->addTab($this->getPluginsList());
        }

        $this->eventDispatcher->notifyEvent('show.itemlist.items', $this);

        $this->tabsGridHelper->renderTabs(Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE), Request::analyze('tabIndex', 0));

        $this->view();
    }

    /**
     * Returns categories' data tab
     */
    protected function getCategoriesList()
    {
        $service = new CategoryRepository();

        return $this->itemsGridHelper->getCategoriesGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns tags' data tab
     */
    protected function getTagsList()
    {
        $service = new TagRepository();

        return $this->itemsGridHelper->getTagsGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns clients' data tab
     */
    protected function getClientsList()
    {
        $service = new ClientRepository();

        return $this->itemsGridHelper->getClientsGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns custom fields' data tab
     */
    protected function getCustomFieldsList()
    {
        $service = new CustomFieldDefRepository();

        return $this->itemsGridHelper->getCustomFieldsGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns account files' data tab
     */
    protected function getAccountFilesList()
    {
        $service = new AccountFileRepository();

        return $this->itemsGridHelper->getFilesGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns accounts' data tab
     */
    protected function getAccountsList()
    {
        $service = new AccountRepository();

        return $this->itemsGridHelper->getAccountsGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns accounts' history data tab
     */
    protected function getAccountsHistoryList()
    {
        $service = new AccountHistoryRepository();

        return $this->itemsGridHelper->getAccountsHistoryGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns plugins' data tab
     */
    protected function getPluginsList()
    {
        $service = new PluginRepository();

        return $this->itemsGridHelper->getPluginsGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * @return TabsGridHelper
     */
    public function getTabsGridHelper()
    {
        return $this->tabsGridHelper;
    }
}