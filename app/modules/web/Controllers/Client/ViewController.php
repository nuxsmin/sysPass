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

namespace SP\Modules\Web\Controllers\Client;

use SP\Core\Events\Event;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

use function SP\__;
use function SP\__u;

/**
 * Class ViewController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ViewController extends ClientViewBase
{
    use JsonTrait;

    /**
     * View action
     *
     * @param int $id
     *
     * @return ActionResponse
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws NoSuchItemException
     */
    #[Action(ResponseType::JSON)]
    public function viewAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::CLIENT_VIEW)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        $this->view->assign('header', __('View Client'));
        $this->view->assign('isView', true);

        $this->setViewData($id);

        $this->eventDispatcher->notify('show.client', new Event($this));

        return ActionResponse::ok('', ['html' => $this->render()]);
    }
}
