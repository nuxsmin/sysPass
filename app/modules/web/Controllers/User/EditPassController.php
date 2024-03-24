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

namespace SP\Modules\Web\Controllers\User;


use Exception;
use JsonException;
use SP\Core\Events\Event;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\User\Models\User;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class EditPassController
 */
final class EditPassController extends UserViewBase
{
    use JsonTrait;

    /**
     * Edit user's pass action
     *
     * @param  int  $id
     *
     * @return bool
     * @throws JsonException
     */
    public function editPassAction(int $id): bool
    {
        try {
            // Comprobar si el usuario a modificar es distinto al de la sesión
            if (!$this->acl->checkUserAccess(AclActionsInterface::USER_EDIT_PASS, $id)) {
                return $this->returnJsonResponse(
                    JsonMessage::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->addTemplate('user_pass', 'itemshow');

            $this->view->assign('header', __('Password Change'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'user/saveEditPass/'.$id);

            $user = $id
                ? $this->userService->getById($id)
                : new User();

            $this->view->assign('user', $user);

            $this->eventDispatcher->notify('show.user.editPass', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
