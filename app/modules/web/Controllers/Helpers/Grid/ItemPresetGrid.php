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
use SP\Services\ItemPreset\ItemPresetInterface;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountDefaultPermissionGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class ItemPresetGrid extends GridBase
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

        $grid->addDataAction($this->getCreatePermissionAction(), true);
        $grid->addDataAction($this->getCreatePrivateAction(), true);
        $grid->addDataAction($this->getCreateSessionTimeoutAction(), true);
        $grid->addDataAction($this->getCreateAccountPasswordAction(), true);
        $grid->addDataAction($this->getEditAction());
        $grid->addDataAction($this->getDeleteAction());
        $grid->addDataAction(
            $this->getDeleteAction()
                ->setTitle(__('Delete Selected'))
                ->setName(__('Delete Selected'))
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
        $gridTab->setId('tblItemPreset');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Preset Values'));

        return $gridTab;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Type'));
        $gridHeader->addHeader(__('User'));
        $gridHeader->addHeader(__('Group'));
        $gridHeader->addHeader(__('Profile'));
        $gridHeader->addHeader(__('Priority'));
        $gridHeader->addHeader(__('Forced'));

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
        $gridData->addDataRowSource('type');
        $gridData->addDataRowSource('userName');
        $gridData->addDataRowSource('userGroupName');
        $gridData->addDataRowSource('userProfileName');
        $gridData->addDataRowSource('priority');
        $gridData->addDataRowSourceWithIcon('fixed', $this->icons->getIconEnabled());
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
        $gridActionSearch->setId(ActionsInterface::ITEMPRESET_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchItemPreset');
        $gridActionSearch->setTitle(__('Search for Value'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::ITEMPRESET_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getCreatePermissionAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Permission Preset'));
        $gridAction->setTitle(__('New Permission Preset'));

        $icon = clone $this->icons->getIconAdd();

        $gridAction->setIcon($icon->setIcon('add_circle'));
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');

        $route = Acl::getActionRoute(ActionsInterface::ITEMPRESET_CREATE) . '/' . ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION;

        $gridAction->addData('action-route', $route);

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getCreatePrivateAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Private Account Preset'));
        $gridAction->setTitle(__('New Private Account Preset'));

        $icon = clone $this->icons->getIconAdd();

        $gridAction->setIcon($icon->setIcon('add_circle'));
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');

        $route = Acl::getActionRoute(ActionsInterface::ITEMPRESET_CREATE) . '/' . ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE;

        $gridAction->addData('action-route', $route);

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateSessionTimeoutAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Session Timeout Preset'));
        $gridAction->setTitle(__('New Session Timeout Preset'));

        $icon = clone $this->icons->getIconAdd();

        $gridAction->setIcon($icon->setIcon('add_circle'));
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');

        $route = Acl::getActionRoute(ActionsInterface::ITEMPRESET_CREATE) . '/' . ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT;

        $gridAction->addData('action-route', $route);

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateAccountPasswordAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('Account Password Preset'));
        $gridAction->setTitle(__('New Account Password Preset'));

        $icon = clone $this->icons->getIconAdd();

        $gridAction->setIcon($icon->setIcon('add_circle'));
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');

        $route = Acl::getActionRoute(ActionsInterface::ITEMPRESET_CREATE) . '/' . ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD;

        $gridAction->addData('action-route', $route);

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getEditAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_EDIT);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Edit Value'));
        $gridAction->setTitle(__('Edit Value'));
        $gridAction->setIcon($this->icons->getIconEdit());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ITEMPRESET_EDIT));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::ITEMPRESET_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Delete Value'));
        $gridAction->setTitle(__('Delete Value'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::ITEMPRESET_DELETE));

        return $gridAction;
    }
}