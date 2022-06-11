<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Account;


use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Util\ErrorUtil;

/**
 * ViewController
 */
final class ViewController extends AccountViewBase
{

    /**
     * View action
     *
     * @param  int  $id  Account's ID
     */
    public function viewAction(int $id): void
    {
        try {
            $this->view->addTemplate('account');

            $accountDetailsResponse = $this->accountService->getById($id);
            $this->accountService
                ->withUsersById($accountDetailsResponse)
                ->withUserGroupsById($accountDetailsResponse)
                ->withTagsById($accountDetailsResponse);

            $this->accountHelper->setIsView(true);
            $this->accountHelper->setViewForAccount($accountDetailsResponse, ActionsInterface::ACCOUNT_VIEW);

            $this->view->assign(
                'title',
                [
                    'class' => 'titleNormal',
                    'name'  => __('Account Details'),
                    'icon'  => $this->icons->getIconView()->getIcon(),
                ]
            );

            $this->accountService->incrementViewCounter($id);

            $this->eventDispatcher->notifyEvent('show.account', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            if ($this->isAjax === false && !$this->view->isUpgraded()) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }
}