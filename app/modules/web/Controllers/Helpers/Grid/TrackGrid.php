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

namespace SP\Modules\Web\Controllers\Helpers\Grid;

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\Action\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridTab;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Http\Address;
use SP\Storage\Database\QueryResult;

/**
 * Class TrackGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class TrackGrid extends GridBase
{
    /**
     * @var QueryResult
     */
    private $queryResult;

    /**
     * @param QueryResult $queryResult
     *
     * @return DataGridInterface
     */
    public function getGrid(QueryResult $queryResult): DataGridInterface
    {
        $this->queryResult = $queryResult;

        $grid = $this->getGridLayout();

        $searchAction = $this->getSearchAction();

        $grid->addDataAction($searchAction);
        $grid->setPager($this->getPager($searchAction));

        $grid->addDataAction($this->getRefrestAction());
        $grid->addDataAction($this->getClearAction());
        $grid->addDataAction($this->getUnlockAction());

        $grid->setTime(round(getElapsedTime($this->queryTimeStart), 5));

        return $grid;
    }

    /**
     * @return DataGridInterface
     */
    protected function getGridLayout(): DataGridInterface
    {
        // Grid
        $gridTab = new DataGridTab($this->view->getTheme());
        $gridTab->setId('tblTracks');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Tracks'));

        return $gridTab;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Date'));
        $gridHeader->addHeader(__('Date Unlocked'));
        $gridHeader->addHeader(__('Source'));
        $gridHeader->addHeader('IPv4');
        $gridHeader->addHeader('IPv6');
        $gridHeader->addHeader(__('User'));

        return $gridHeader;
    }

    /**
     * @return DataGridData
     */
    protected function getData(): DataGridData
    {
        $demo = $this->configData->isDemoEnabled();


        // Grid Data
        $gridData = new DataGridData();
        $gridData->setDataRowSourceId('id');
        $gridData->addDataRowSource('dateTime');
        $gridData->addDataRowSource('dateTimeUnlock');
        $gridData->addDataRowSource('source', null, null, false);
        $gridData->addDataRowSource('ipv4', null, function ($value) use ($demo) {
            if ($value !== null) {
                if ($demo) {
                    return '*.*.*.*';
                }

                return Address::fromBinary($value);
            }

            return '&nbsp;';
        });
        $gridData->addDataRowSource('ipv6', null, function ($value) use ($demo) {
            if ($value !== null) {
                if ($demo) {
                    return '*.*.*.*';
                }

                return Address::fromBinary($value);
            }

            return '&nbsp;';
        });
        $gridData->addDataRowSource('userId');
        $gridData->setData($this->queryResult);

        return $gridData;
    }

    /**
     * @return DataGridActionSearch
     */
    private function getSearchAction()
    {
        // Grid Actions
        $gridActionSearch = new DataGridActionSearch();
        $gridActionSearch->setId(ActionsInterface::TRACK_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchTrack');
        $gridActionSearch->setTitle(__('Search for track'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::TRACK_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getRefrestAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::TRACK_SEARCH);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setSkip(true);
        $gridAction->setName(__('Refresh'));
        $gridAction->setTitle(__('Refresh'));
        $gridAction->setIcon($this->icons->getIconRefresh());
        $gridAction->setOnClickFunction('track/refresh');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::TRACK_SEARCH));
        $gridAction->addData('action-form', 'frmSearchTrack');

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getClearAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::TRACK_CLEAR);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setSkip(true);
        $gridAction->setName(Acl::getActionInfo(ActionsInterface::TRACK_CLEAR));
        $gridAction->setTitle(Acl::getActionInfo(ActionsInterface::TRACK_CLEAR));
        $gridAction->setIcon($this->icons->getIconClear());
        $gridAction->setOnClickFunction('track/clear');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::TRACK_CLEAR));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getUnlockAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::TRACK_UNLOCK);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(Acl::getActionInfo(ActionsInterface::TRACK_UNLOCK));
        $gridAction->setTitle(Acl::getActionInfo(ActionsInterface::TRACK_UNLOCK));
        $gridAction->setIcon($this->icons->getIconCheck());
        $gridAction->setOnClickFunction('track/unlock');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::TRACK_UNLOCK));
        $gridAction->setFilterRowSource('tracked', 0);

        return $gridAction;
    }
}