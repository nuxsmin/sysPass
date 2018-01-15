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
use SP\Repositories\ApiToken\ApiTokenRepository;
use SP\Repositories\PublicLink\PublicLinkRepository;
use SP\Repositories\User\UserRepository;
use SP\Repositories\UserGroup\UserGroupRepository;
use SP\Repositories\UserProfile\UserProfileRepository;

/**
 * Class AccessMgmtController
 *
 * @package SP\Modules\Web\Controllers
 */
class AccessManagerController extends ControllerBase
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
     */
    public function indexAction()
    {
        $this->getGridTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    protected function getGridTabs()
    {
        $this->itemSearchData = new ItemSearchData();
        $this->itemSearchData->setLimitCount($this->configData->getAccountCount());

        $this->itemsGridHelper = new ItemsGridHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        $this->tabsGridHelper = new TabsGridHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        if ($this->checkAccess(ActionsInterface::USER)) {
            $this->tabsGridHelper->addTab($this->getUsersList());
        }

        if ($this->checkAccess(ActionsInterface::GROUP)) {
            $this->tabsGridHelper->addTab($this->getUsersGroupList());
        }

        if ($this->checkAccess(ActionsInterface::PROFILE)) {
            $this->tabsGridHelper->addTab($this->getUsersProfileList());
        }

        if ($this->checkAccess(ActionsInterface::APITOKEN)) {
            $this->tabsGridHelper->addTab($this->getApiTokensList());
        }

        if ($this->checkAccess(ActionsInterface::PUBLICLINK)) {
            $this->tabsGridHelper->addTab($this->getPublicLinksList());
        }

        $this->eventDispatcher->notifyEvent('show.itemlist.accesses', $this);

        $this->tabsGridHelper->renderTabs(Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE), Request::analyze('tabIndex', 0));

        $this->view();
    }

    /**
     * Returns users' data tab
     */
    protected function getUsersList()
    {
        $service = new UserRepository();

        return $this->itemsGridHelper->getUsersGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns users group data tab
     */
    protected function getUsersGroupList()
    {
        $service = new UserGroupRepository();

        return $this->itemsGridHelper->getUserGroupsGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns users profile data tab
     */
    protected function getUsersProfileList()
    {
        $service = new UserProfileRepository();

        return $this->itemsGridHelper->getUserProfilesGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns API tokens data tab
     */
    protected function getApiTokensList()
    {
        $service = new ApiTokenRepository();

        return $this->itemsGridHelper->getApiTokensGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns public links data tab
     */
    protected function getPublicLinksList()
    {
        $service = new PublicLinkRepository();

        return $this->itemsGridHelper->getPublicLinksGrid($service->search($this->itemSearchData))->updatePager();
    }

    /**
     * @return TabsGridHelper
     */
    public function getTabsGridHelper()
    {
        return $this->tabsGridHelper;
    }
}