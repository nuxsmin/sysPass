<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Helpers\Grid;


use SP\Core\Acl\Acl;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Html\DataGrid\Action\DataGridAction;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\Action\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\DataGridTab;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Infrastructure\Database\QueryResult;

use function SP\__;
use function SP\getElapsedTime;

/**
 * Class EventlogGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class EventlogGrid extends GridBase
{
    private ?QueryResult $queryResult = null;

    /**
     * @param QueryResult $queryResult
     *
     * @return DataGridInterface
     * @throws SPException
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

        $grid->setTime(round(getElapsedTime($this->queryTimeStart), 5));

        return $grid;
    }

    /**
     * @throws SPException
     */
    protected function getGridLayout(): DataGridInterface
    {
        // Grid
        $gridTab = new DataGridTab($this->theme);
        $gridTab->setId('tblEventLog');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Event Log'));

        return $gridTab;
    }

    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('ID'));
        $gridHeader->addHeader(__('Date / Time'));
        $gridHeader->addHeader(__('Level'));
        $gridHeader->addHeader(__('Event'));
        $gridHeader->addHeader(__('Login'));
        $gridHeader->addHeader(__('IP'));
        $gridHeader->addHeader(__('Description'));

        return $gridHeader;
    }

    /**
     * @throws SPException
     */
    protected function getData(): DataGridData
    {
        // Grid Data
        $isDemoMode = $this->configData->isDemoEnabled();

        $gridData = new DataGridData();
        $gridData->setDataRowSourceId('id');
        $gridData->addDataRowSource('id');
        $gridData->addDataRowSource('date');
        $gridData->addDataRowSource('level');
        $gridData->addDataRowSource('action', null, null, false);
        $gridData->addDataRowSource('login');
        $gridData->addDataRowSource(
            'ipAddress',
            false,
            function ($value) use ($isDemoMode) {
                return $isDemoMode ? '*.*.*.*' : $value;
            }
        );
        $gridData->addDataRowSource(
            'description',
            false,
            function ($value) use ($isDemoMode) {
                if ($isDemoMode) {
                    $value = preg_replace(
                        '/\d+\.\d+\.\d+\.\d+/',
                        '*.*.*.*',
                        $value
                    );
                }

                if (preg_match('/^SQL.*/m', $value)) {
                    $value = preg_replace([
                                              '/([a-zA-Z_]+),/m',
                                              '/(UPDATE|DELETE|TRUNCATE|INSERT|SELECT|WHERE|LEFT|ORDER|LIMIT|FROM)/m'
                                          ],
                                          ['\\1,<br>', '<br>\\1'],
                                          $value
                    );
                }

                return wordwrap(
                    str_replace([';;', PHP_EOL], '<br>', $value),
                    100,
                    '<br>',
                    true
                );
            },
            false
        );
        $gridData->setData($this->queryResult);

        return $gridData;
    }

    private function getSearchAction(): DataGridActionSearch
    {
        // Grid Actions
        $gridActionSearch = new DataGridActionSearch();
        $gridActionSearch->setId(AclActionsInterface::EVENTLOG_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchEvent');
        $gridActionSearch->setTitle(__('Search for Events'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::EVENTLOG_SEARCH)
        );

        return $gridActionSearch;
    }

    private function getRefrestAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::EVENTLOG_SEARCH);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setSkip(true);
        $gridAction->setName(__('Refresh'));
        $gridAction->setTitle(__('Refresh'));
        $gridAction->setIcon($this->icons->refresh());
        $gridAction->setOnClickFunction('eventlog/refresh');
        $gridAction->addData('action-form', 'frmSearchEvent');
        $gridAction->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::EVENTLOG_SEARCH)
        );

        return $gridAction;
    }

    private function getClearAction(): DataGridAction
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(AclActionsInterface::EVENTLOG_CLEAR);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setSkip(true);
        $gridAction->setName(__('Clear the event log out'));
        $gridAction->setTitle(__('Clear the event log out'));
        $gridAction->setIcon($this->icons->clear());
        $gridAction->setOnClickFunction('eventlog/clear');
        $gridAction->addData(
            'action-route',
            Acl::getActionRoute(AclActionsInterface::EVENTLOG_CLEAR)
        );

        return $gridAction;
    }
}
