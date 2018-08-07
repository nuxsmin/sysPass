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

namespace SP\Modules\Web\Controllers\Helpers\Grid;


use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Html\DataGrid\DataGrid;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridInterface;
use SP\Storage\Database\QueryResult;

/**
 * Class EventlogGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class EventlogGrid extends GridBase
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

        $grid->setDataActions($this->getSearchAction());
        $grid->setPager($this->getPager($searchAction)->setOnClickFunction('eventlog/nav'));

        $grid->setDataActions($this->getRefrestAction());
        $grid->setDataActions($this->getClearAction());

        $grid->setTime(round(getElapsedTime($this->queryTimeStart), 5));

        return $grid;
    }

    /**
     * @return DataGridInterface
     */
    protected function getGridLayout(): DataGridInterface
    {
        // Grid
        $dataGrid = new DataGrid($this->view->getTheme());
        $dataGrid->setId('tblEventLog');
        $dataGrid->setDataTableTemplate('datagrid-table-simple', 'grid');
        $dataGrid->setDataRowTemplate('datagrid-rows', $this->view->getBase());
        $dataGrid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $dataGrid->setHeader($this->getHeader());
        $dataGrid->setData($this->getData());
        $dataGrid->setTitle(__('Registro de Eventos'));

        return $dataGrid;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('ID'));
        $gridHeader->addHeader(__('Fecha / Hora'));
        $gridHeader->addHeader(__('Nivel'));
        $gridHeader->addHeader(__('Evento'));
        $gridHeader->addHeader(__('Login'));
        $gridHeader->addHeader(__('IP'));
        $gridHeader->addHeader(__('Descripción'));

        return $gridHeader;
    }

    /**
     * @return DataGridData
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
        $gridData->addDataRowSource('action');
        $gridData->addDataRowSource('login');
        $gridData->addDataRowSource('ipAddress', false,
            function ($value) use ($isDemoMode) {
                return $isDemoMode ? preg_replace('#\d+#', '*', $value) : $value;
            });
        $gridData->addDataRowSource('description', false,
            function ($value) use ($isDemoMode) {
                if ($isDemoMode) {
                    $value = preg_replace('/\\d+\\.\\d+\\.\\d+\\.\\d+/', "*.*.*.*", $value);
                }

                $text = str_replace(';;', PHP_EOL, $value);

                if (preg_match('/^SQL.*/m', $text)) {
                    $text = preg_replace([
                        '/([a-zA-Z_]+),/m',
                        '/(UPDATE|DELETE|TRUNCATE|INSERT|SELECT|WHERE|LEFT|ORDER|LIMIT|FROM)/m'],
                        ['\\1,<br>', '<br>\\1'],
                        $text);
                }

//                if (strlen($text) >= 100) {
//                    $text = wordwrap($text, 100, PHP_EOL, true);
//                }

                return str_replace(PHP_EOL, '<br>', $text);
            });
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
        $gridActionSearch->setId(ActionsInterface::EVENTLOG_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchEvent');
        $gridActionSearch->setTitle(__('Buscar Evento'));
        $gridActionSearch->setOnSubmitFunction('eventlog/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::EVENTLOG_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getRefrestAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::EVENTLOG_SEARCH);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Refrescar'));
        $gridAction->setTitle(__('Refrescar'));
        $gridAction->setIcon($this->icons->getIconRefresh());
        $gridAction->setOnClickFunction('eventlog/search');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::EVENTLOG_SEARCH));
        $gridAction->addData('target', '#data-table-tblEventLog');

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getClearAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::EVENTLOG_CLEAR);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Vaciar registro de eventos'));
        $gridAction->setTitle(__('Vaciar registro de eventos'));
        $gridAction->setIcon($this->icons->getIconClear());
        $gridAction->setOnClickFunction('eventlog/clear');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::EVENTLOG_CLEAR));
        $gridAction->addData('nextaction', Acl::getActionRoute(ActionsInterface::EVENTLOG));

        return $gridAction;
    }
}