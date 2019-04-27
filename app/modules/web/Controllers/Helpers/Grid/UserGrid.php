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
 * Class UserGrid
 *
 * @package SP\Modules\Web\Controllers\Helpers\Grid
 */
final class UserGrid extends GridBase
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

        $grid->addDataAction($this->getCreateAction());
        $grid->addDataAction($this->getViewAction());
        $grid->addDataAction($this->getEditAction());
        $grid->addDataAction($this->getEditPassAction());
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
        $gridTab->setId('tblUsers');
        $gridTab->setDataRowTemplate('datagrid-rows', 'grid');
        $gridTab->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $gridTab->setHeader($this->getHeader());
        $gridTab->setData($this->getData());
        $gridTab->setTitle(__('Users'));

        return $gridTab;
    }

    /**
     * @return DataGridHeader
     */
    protected function getHeader(): DataGridHeader
    {
        // Grid Header
        $gridHeader = new DataGridHeader();
        $gridHeader->addHeader(__('Name'));
        $gridHeader->addHeader(__('Login'));
        $gridHeader->addHeader(__('Profile'));
        $gridHeader->addHeader(__('Group'));
        $gridHeader->addHeader(__('Properties'));

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
        $gridData->addDataRowSource('login');
        $gridData->addDataRowSource('userProfileName');
        $gridData->addDataRowSource('userGroupName');
        $gridData->addDataRowSourceWithIcon('isAdminApp', $this->icons->getIconAppAdmin());
        $gridData->addDataRowSourceWithIcon('isAdminAcc', $this->icons->getIconAccAdmin());
        $gridData->addDataRowSourceWithIcon('isLdap', $this->icons->getIconLdapUser());
        $gridData->addDataRowSourceWithIcon('isDisabled', $this->icons->getIconDisabled());
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
        $gridActionSearch->setId(ActionsInterface::USER_SEARCH);
        $gridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $gridActionSearch->setName('frmSearchUser');
        $gridActionSearch->setTitle(__('Search for User'));
        $gridActionSearch->setOnSubmitFunction('appMgmt/search');
        $gridActionSearch->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_SEARCH));

        return $gridActionSearch;
    }

    /**
     * @return DataGridAction
     */
    private function getCreateAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_CREATE);
        $gridAction->setType(DataGridActionType::MENUBAR_ITEM);
        $gridAction->setName(__('New User'));
        $gridAction->setTitle(__('New User'));
        $gridAction->setIcon($this->icons->getIconAdd());
        $gridAction->setSkip(true);
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_CREATE));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getEditAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_EDIT);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Edit User'));
        $gridAction->setTitle(__('Edit User'));
        $gridAction->setIcon($this->icons->getIconEdit());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_EDIT));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getDeleteAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_DELETE);
        $gridAction->setType(DataGridActionType::DELETE_ITEM);
        $gridAction->setName(__('Delete User'));
        $gridAction->setTitle(__('Delete User'));
        $gridAction->setIcon($this->icons->getIconDelete());
        $gridAction->setOnClickFunction('appMgmt/delete');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_DELETE));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getViewAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_VIEW);
        $gridAction->setType(DataGridActionType::VIEW_ITEM);
        $gridAction->setName(__('View User Details'));
        $gridAction->setTitle(__('View User Details'));
        $gridAction->setIcon($this->icons->getIconView());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_VIEW));

        return $gridAction;
    }

    /**
     * @return DataGridAction
     */
    private function getEditPassAction()
    {
        $gridAction = new DataGridAction();
        $gridAction->setId(ActionsInterface::USER_EDIT_PASS);
        $gridAction->setType(DataGridActionType::EDIT_ITEM);
        $gridAction->setName(__('Change User\'s Password'));
        $gridAction->setTitle(__('Change User\'s Password'));
        $gridAction->setIcon($this->icons->getIconEditPass());
        $gridAction->setOnClickFunction('appMgmt/show');
        $gridAction->setFilterRowSource('isLdap');
        $gridAction->addData('action-route', Acl::getActionRoute(ActionsInterface::USER_EDIT_PASS));

        return $gridAction;
    }
}