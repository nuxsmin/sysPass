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

use SP\Core\Events\Event;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__;

/**
 * Class EditController
 */
final class EditController extends AccountViewBase
{

    /**
     * Edit action
     *
     * @param int $id Account's ID
     * @throws ServiceException
     * @throws AccountPermissionException
     * @throws UnauthorizedActionException
     * @throws UnauthorizedPageException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws UpdatedMasterPassException
     * @throws NoSuchItemException
     */
    #[Action(ResponseType::PLAIN_TEXT)]
    public function editAction(int $id): ActionResponse
    {
        $this->accountHelper->initializeFor(AclActionsInterface::ACCOUNT_EDIT);

        $accountEnrichedDto = new AccountEnrichedDto($this->accountService->getByIdEnriched($id));
        $accountEnrichedDto = $this->accountService->withUsers($accountEnrichedDto);
        $accountEnrichedDto = $this->accountService->withUserGroups($accountEnrichedDto);
        $accountEnrichedDto = $this->accountService->withTags($accountEnrichedDto);

        $this->accountHelper->setViewForAccount($accountEnrichedDto);

        $this->view->addTemplate('account');
        $this->view->assign(
            'title',
            [
                'class' => 'titleOrange',
                'name' => __('Edit Account'),
                'icon' => $this->icons->edit()->getIcon(),
            ]
        );
        $this->view->assign('formRoute', 'account/saveEdit');

        $this->accountService->incrementViewCounter($id);

        $this->eventDispatcher->notify('show.account.edit', new Event($this));

        if ($this->isAjax === false) {
            $this->upgradeView();
        }

        return ActionResponse::ok($this->render());
    }
}
