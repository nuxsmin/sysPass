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

namespace SP\Modules\Web\Controllers\SecurityManager;

use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Security\Ports\EventlogService;
use SP\Domain\Security\Ports\TrackServiceInterface;
use SP\Html\DataGrid\DataGridTab;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\Grid\EventlogGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\TrackGrid;
use SP\Modules\Web\Controllers\Helpers\TabsGridHelper;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class IndexController
 *
 * @package SP\Modules\Web\Controllers
 */
final class IndexController extends ControllerBase
{
    protected ItemSearchData         $itemSearchData;
    protected TabsGridHelper         $tabsGridHelper;
    private EventlogGrid             $eventlogGrid;
    private TrackGrid             $trackGrid;
    private EventlogService       $eventlogService;
    private TrackServiceInterface $trackService;

    public function __construct(
        Application         $application,
        WebControllerHelper $webControllerHelper,
        TabsGridHelper      $tabsGridHelper,
        EventlogGrid        $eventlogGrid,
        TrackGrid           $trackGrid,
        EventlogService     $eventlogService,
        TrackServiceInterface $trackService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->tabsGridHelper = $tabsGridHelper;
        $this->eventlogGrid = $eventlogGrid;
        $this->trackGrid = $trackGrid;
        $this->eventlogService = $eventlogService;
        $this->trackService = $trackService;

        $this->itemSearchData = new ItemSearchData();
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
        $this->itemSearchData->setLimitCount($this->configData->getAccountCount());

        if ($this->checkAccess(AclActionsInterface::EVENTLOG)
            && $this->configData->isLogEnabled()
        ) {
            $this->tabsGridHelper->addTab($this->getEventlogList());
        }

        if ($this->checkAccess(AclActionsInterface::TRACK)) {
            $this->tabsGridHelper->addTab($this->getTracksList());
        }

        $this->eventDispatcher->notify(
            'show.itemlist.security',
            new Event($this)
        );

        $this->tabsGridHelper->renderTabs(
            Acl::getActionRoute(AclActionsInterface::SECURITY_MANAGE),
            $this->request->analyzeInt('tabIndex', 0)
        );

        $this->view();
    }

    /**
     * Returns eventlog data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getEventlogList(): DataGridTab
    {
        return $this->eventlogGrid->getGrid($this->eventlogService->search($this->itemSearchData))->updatePager();
    }

    /**
     * Returns tracks data tab
     *
     * @return DataGridTab
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getTracksList(): DataGridTab
    {
        return $this->trackGrid->getGrid($this->trackService->search($this->itemSearchData))->updatePager();
    }

    /**
     * @return TabsGridHelper
     */
    public function getTabsGridHelper(): TabsGridHelper
    {
        return $this->tabsGridHelper;
    }
}
