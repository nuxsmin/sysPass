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
use SP\Html\DataGrid\Action\DataGridActionInterface;
use SP\Html\DataGrid\Action\DataGridActionSearch;
use SP\Html\DataGrid\Action\DataGridActionType;
use SP\Html\DataGrid\DataGrid;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridInterface;
use SP\Html\DataGrid\Layout\DataGridHeader;
use SP\Html\Html;
use SP\Storage\Database\QueryResult;
use SP\Util\DateUtil;

/**
 * Class NotificationGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class NotificationGrid extends GridBase
{
    /**
     * @var QueryResult
     */
    private $queryResult;
    /**
     * @var bool
     */
    private $isAdminApp;

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

        $this->isAdminApp = (bool)$this->context->getUserData()->getIsAdminApp();

        if ($this->isAdminApp) {
            $grid->addDataAction($this->getCreateAction());
        }

        $grid->addDataAction($this->getViewAction());
        $grid->addDataAction($this->setNonAdminFilter($this->getCheckAction()));

        if ($this->isAdminApp) {
            $grid->addDataAction($this->setNonAdminFilter($this->getEditAction()));
        }

        $grid->addDataAction($this->setNonAdminFilter($this->getDeleteAction()));
        $grid->addDataAction(
            $this->setNonAdminFilter(
                $this->getDeleteAction()
                    ->setTitle(__('Delete Selected'))
                    ->setName(__('Delete Selected'))
                    ->setIsSelection(true)),
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
        $dataGrid = new DataGrid($this->view->getTheme());
        $dataGrid->setId('tblNotifications');
        $dataGrid->setDataRowTemplate('datagrid-rows', 'grid');
        $dataGrid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $dataGrid->setHeader($this->getHeader());
        $dataGrid->setData($this->getData());
        $dataGrid->setTitle(__('Notifications'));
        $dataGrid->setTime(round(getElapsedTime($this->queryTimeStart), 5));

        return $dataGrid;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Date'));
        $gridHeader->addHeader(__('Type'));
        $gridHeader->addHeader(__('Component'));
        $gridHeader->addHeader(__('Description'));
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
        $gridData->addDataRowSource('date', false,
            function ($value) {
                return DateUtil::getDateFromUnix($value);
            });
        $gridData->addDataRowSource('type');
        $gridData->addDataRowSource('component');
        $gridData->addDataRowSource('description', false, function ($data) {
            return Html::stripTags($data);
        });
        $gridData->addDataRowSourceWithIcon('checked', $this->icons->getIconEnabled()->setTitle(__('Read')));
        $gridData->addDataRowSourceWithIcon('onlyAdmin', $this->icons->getIconAppAdmin()->setTitle(__('Only Admins')));
        $gridData->addDataRowSourceWithIcon('sticky', $this->icons->getIconGroup()->setTitle(__('Global')));
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
        $gridActionSearch->setId(ActionsInterface::NOTIFICATION_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchNotification');
        $gridActionSearch->setTitle(__('Search for Notification'));
        $gridActionSearch->setOnSubmitFunction('notification/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::NOTIFICATION_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('New Notification'));
        $gridAction->setTitle(__('New Notification'));
        $gridAction->setIcon($this->icons->getIconAdd());
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('notification/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_CREATE));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getViewAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::NOTIFICATION_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('View Notification'));
        $gridAction->setTitle(__('View Notification'));
        $gridAction->setIcon($this->icons->getIconView());
        $gridAction->setOnClickFunction('notification/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_VIEW));

        return $gridAction;
    }

    /**
     * @param DataGridActionInterface $gridAction
     *
     * @return DataGridActionInterface
     */
    private function setNonAdminFilter(DataGridActionInterface $gridAction)
    {
        if (!$this->isAdminApp) {
            $gridAction->setFilterRowSource('sticky');
        }

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getCheckAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::NOTIFICATION_CHECK);
        $gridAction->setName(__('Checkout Notification'));
        $gridAction->setTitle(__('Checkout Notification'));
        $gridAction->setIcon($this->icons->getIconEnabled());
        $gridAction->setOnClickFunction('notification/check');
        $gridAction->setFilterRowSource('checked');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_CHECK));
        $gridAction->addData('action-next', Acl::getActionRoute(ActionsInterface::NOTIFICATION));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getEditAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::NOTIFICATION_EDIT);
        $gridAction->setName(__('Edit Notification'));
        $gridAction->setTitle(__('Edit Notification'));
        $gridAction->setIcon($this->icons->getIconEdit());
        $gridAction->setOnClickFunction('notification/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_EDIT));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::NOTIFICATION_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Delete Notification'));
        $gridAction->setTitle(__('Delete Notification'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('notification/delete');
        $gridAction->setFilterRowSource('checked', 0);
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::NOTIFICATION_DELETE));
        $gridAction->addData('action-next', Acl::getActionRoute(ActionsInterface::NOTIFICATION));

        return $gridAction;
    }
}