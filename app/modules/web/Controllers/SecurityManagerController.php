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
use SP\Modules\Web\Controllers\Helpers\Grid\EventlogGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\TrackGrid;
use SP\Modules\Web\Controllers\Helpers\TabsGridHelper;
use SP\Services\EventLog\EventlogService;
use SP\Services\Track\TrackService;

/**
 * Class SecurityManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SecurityManagerController extends ControllerBase
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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getGridTabs()
    {
        $this->itemSearchData = new ItemSearchData();
        $this->itemSearchData->setLimitCount($this->configData->getAccountCount());

        $this->tabsGridHelper = $this->dic->get(TabsGridHelper::class);

        if ($this->checkAccess(Acl::EVENTLOG)
            && $this->configData->isLogEnabled()
        ) {
            $this->tabsGridHelper->addTab($this->getEventlogList());
        }

        if ($this->checkAccess(Acl::TRACK)) {
            $this->tabsGridHelper->addTab($this->getTracksList());
        }

        $this->eventDispatcher->notifyEvent('show.itemlist.security', new Event($this));

        $this->tabsGridHelper->renderTabs(Acl::getActionRoute(Acl::SECURITY_MANAGE), $this->request->analyzeInt('tabIndex', 0));

        $this->view();
    }

    /**
     * Returns eventlog data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getEventlogList()
    {
        return $this->dic->get(EventlogGrid::class)
            ->getGrid($this->dic->get(EventlogService::class)->search($this->itemSearchData))
            ->updatePager();
    }

    /**
     * Returns tracks data tab
     *
     * @return \SP\Html\DataGrid\DataGridTab
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getTracksList()
    {
        return $this->dic->get(TrackGrid::class)
            ->getGrid($this->dic->get(TrackService::class)->search($this->itemSearchData))
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