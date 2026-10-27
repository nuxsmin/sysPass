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

namespace SP\Modules\Web\Controllers\UserProfile;

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Exceptions\SPException;

use function SP\__;
use function SP\__u;

/**
 * Class ViewController
 */
final class ViewController extends UserProfileViewBase
{
    #[Action(ResponseType::JSON)]
    public function viewAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::PROFILE_VIEW)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->view->assign('header', __('View Profile'));
            $this->view->assign('isView', true);

            $this->setViewData($id);
        } catch (SPException $e) {
            return ActionResponse::error($e->getMessage(), $e->getHint());
        }

        $this->eventDispatcher->notify('show.userProfile', new Event($this));

        return ActionResponse::ok('', ['html' => $this->render()]);
    }
}
