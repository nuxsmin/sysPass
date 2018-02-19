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

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\DataModel\ItemSearchData;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Helpers\TabsGridHelper;
use SP\Services\AuthToken\AuthTokenService;
use SP\Services\PublicLink\PublicLinkService;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;

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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function indexAction()
    {
        $this->getGridTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    protected function getGridTabs()
    {
        $this->itemSearchData = new ItemSearchData();
        $this->itemSearchData->setLimitCount($this->configData->getAccountCount());

        $this->itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $this->tabsGridHelper = $this->dic->get(TabsGridHelper::class);

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

        $this->eventDispatcher->notifyEvent('show.itemlist.accesses', new Event($this));

        $this->tabsGridHelper->renderTabs(Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE), Request::analyze('tabIndex', 0));

        $this->view();
    }

    /**
     * Returns users' data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getUsersList()
    {

        return $this->itemsGridHelper->getUsersGrid($this->dic->get(UserService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns users group data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getUsersGroupList()
    {
        return $this->itemsGridHelper->getUserGroupsGrid($this->dic->get(UserGroupService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns users profile data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getUsersProfileList()
    {
        return $this->itemsGridHelper->getUserProfilesGrid($this->dic->get(UserProfileService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns API tokens data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getApiTokensList()
    {
        return $this->itemsGridHelper->getApiTokensGrid($this->dic->get(AuthTokenService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns public links data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getPublicLinksList()
    {
        return $this->itemsGridHelper->getPublicLinksGrid($this->dic->get(PublicLinkService::class)->search($this->itemSearchData))->updatePager();
    }

    /**
     * @return TabsGridHelper
     */
    public function getTabsGridHelper()
    {
        return $this->tabsGridHelper;
    }
}