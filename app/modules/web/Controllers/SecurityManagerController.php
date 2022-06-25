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
use SP\Modules\Web\Controllers\Helpers\Grid\EventlogGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\TrackGrid;
use SP\Modules\Web\Controllers\Helpers\TabsGridHelper;
use SP\Services\Auth\AuthException;
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
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
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
     * @return DataGridTab
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
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