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

namespace SP\Modules\Web\Controllers\Account;

use Exception;
use SP\Core\Events\Event;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Web\Util\ErrorUtil;

use function SP\__;
use function SP\processException;

/**
 * Class CreateController
 */
final class CreateController extends AccountViewBase
{
    /**
     * Create action
     */
    public function createAction(): void
    {
        try {
            $this->accountHelper->setViewForBlank(AclActionsInterface::ACCOUNT_CREATE);

            $this->view->addTemplate('account');
            $this->view->assign(
                'title',
                [
                    'class' => 'titleGreen',
                    'name' => __('New Account'),
                    'icon' => $this->icons->add()->getIcon(),
                ]
            );
            $this->view->assign('formRoute', 'account/saveCreate');

            $this->eventDispatcher->notify('show.account.create', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }
}
