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
use SP\Storage\Database\QueryResult;

/**
 * Class PluginGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class PluginGrid extends GridBase
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

        $grid->addDataAction($this->getViewAction());
        $grid->addDataAction($this->getEnableAction());
        $grid->addDataAction($this->getDisableAction());
        $grid->addDataAction($this->getResetAction());
        $grid->addDataAction($this->getDeleteAction());
        $grid->addDataAction(
            $this->getDeleteAction()
                ->setName(__('Delete Selected'))
                ->setTitle(__('Delete Selected'))
                ->setIsSelection(true),
            true);

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
        $gridTab->setId('tblPlugins');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Plugins'));

        return $gridTab;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Plugin'));
        $gridHeader->addHeader(__('Status'));

        return $gridHeader;
    }

    /**
     * @return DataGridData
     */
    protected function getData(): DataGridData
    {
        // Grid Data
        $gridData = new DataGridData();
        $gridData->setDataRowSourceId('id');
        $gridData->addDataRowSource('name');
        $gridData->addDataRowSourceWithIcon('enabled', $this->icons->getIconEnabled());
        $gridData->addDataRowSourceWithIcon('enabled', $this->icons->getIconDisabled(), 0);
        $gridData->addDataRowSourceWithIcon('available', $this->icons->getIconDelete()->setTitle(__('Unavailable')), 0);
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
        $gridActionSearch->setId(ActionsInterface::PLUGIN_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchPlugin');
        $gridActionSearch->setTitle(__('Search for Plugin'));
        $gridActionSearch->setOnSubmitFunction('plugin/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getViewAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::PLUGIN_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('View Plugin'));
        $gridAction->setTitle(__('View Plugin'));
        $gridAction->setIcon($this->icons->getIconView());
        $gridAction->setOnClickFunction('plugin/show');
        $gridAction->setFilterRowSource('available', 0);
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_VIEW));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getEnableAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::PLUGIN_ENABLE);
        $gridAction->setName(__('Enable'));
        $gridAction->setTitle(__('Enable'));
        $gridAction->setIcon($this->icons->getIconEnabled());
        $gridAction->setOnClickFunction('plugin/toggle');
        $gridAction->setFilterRowSource('enabled');
        $gridAction->setFilterRowSource('available', 0);
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_ENABLE));
        $gridAction->addData('action-method', 'get');

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDisableAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::PLUGIN_DISABLE);
        $gridAction->setName(__('Disable'));
        $gridAction->setTitle(__('Disable'));
        $gridAction->setIcon($this->icons->getIconDisabled());
        $gridAction->setOnClickFunction('plugin/toggle');
        $gridAction->setFilterRowSource('enabled', 0);
        $gridAction->setFilterRowSource('available', 0);
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_DISABLE));
        $gridAction->addData('action-method', 'get');

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getResetAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::PLUGIN_RESET);
        $gridAction->setName(__('Reset Data'));
        $gridAction->setTitle(__('Reset Data'));
        $gridAction->setIcon($this->icons->getIconRefresh());
        $gridAction->setOnClickFunction('plugin/reset');
        $gridAction->setFilterRowSource('available', 0);
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_RESET));
        $gridAction->addData('action-method', 'get');
        $gridAction->addData('action-next', Acl::getActionRoute(ActionsInterface::PLUGIN));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::PLUGIN_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Delete Plugin'));
        $gridAction->setTitle(__('Delete Plugin'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setFilterRowSource('available', 1);
        $gridAction->setOnClickFunction('plugin/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::PLUGIN_DELETE));
        $gridAction->addData('action-next', Acl::getActionRoute(ActionsInterface::PLUGIN));

        return $gridAction;
    }
}