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
use SP\Modules\Web\Controllers\Helpers\Grid\AuthTokenGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\PublicLinkGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\UserGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\UserGroupGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\UserProfileGrid;
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
final class AccessManagerController extends ControllerBase
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

        $this->tabsGridHelper = $this->dic->get(TabsGridHelper::class);

        if ($this->checkAccess(Acl::USER)) {
            $this->tabsGridHelper->addTab($this->getUsersList());
        }

        if ($this->checkAccess(Acl::GROUP)) {
            $this->tabsGridHelper->addTab($this->getUsersGroupList());
        }

        if ($this->checkAccess(Acl::PROFILE)) {
            $this->tabsGridHelper->addTab($this->getUsersProfileList());
        }

        if ($this->checkAccess(Acl::AUTHTOKEN)) {
            $this->tabsGridHelper->addTab($this->getApiTokensList());
        }

        if ($this->configData->isPublinksEnabled() && $this->checkAccess(Acl::PUBLICLINK)) {
            $this->tabsGridHelper->addTab($this->getPublicLinksList());
        }

        $this->eventDispatcher->notifyEvent('show.itemlist.accesses', new Event($this));

        $this->tabsGridHelper->renderTabs(Acl::getActionRoute(Acl::ACCESS_MANAGE), $this->request->analyzeInt('tabIndex', 0));

        $this->view();
    }

    /**
     * Returns users' data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getUsersList()
    {

        return $this->dic->get(UserGrid::class)
            ->getGrid($this->dic->get(UserService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns users group data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getUsersGroupList()
    {
        return $this->dic->get(UserGroupGrid::class)
            ->getGrid($this->dic->get(UserGroupService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns users profile data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getUsersProfileList()
    {
        return $this->dic->get(UserProfileGrid::class)
            ->getGrid($this->dic->get(UserProfileService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns API tokens data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getApiTokensList()
    {
        return $this->dic->get(AuthTokenGrid::class)
            ->getGrid($this->dic->get(AuthTokenService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns public links data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getPublicLinksList()
    {
        return $this->dic->get(PublicLinkGrid::class)
            ->getGrid($this->dic->get(PublicLinkService::class)->search($this->itemSearchData))
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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Services\Auth\AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();
    }
}