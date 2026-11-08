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
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\UseCases\Dtos\UpdateUserCommand;
use SP\Domain\User\UseCases\UpdateUserUseCase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class SaveEditController
 */
final class SaveEditController extends UserSaveBase
{
    public function __construct(
        WebControllerHelper    $webControllerHelper,
        private readonly UpdateUserUseCase $updateUserUseCase,
        CustomFieldDataService $customFieldService,
        MailService            $mailService,
        UserPassRecoverService $userPassRecoverService
    ) {
        parent::__construct(
            $webControllerHelper,
            $customFieldService,
            $mailService,
            $userPassRecoverService
        );
    }

    #[Action(ResponseType::JSON)]
    public function saveEditAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::USER_EDIT)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->form->validateFor(AclActionsInterface::USER_EDIT, $id);

            $this->updateUserUseCase->execute(
                new UpdateUserCommand($this->form->getItemData())
            );

            $this->updateCustomFieldsForItem(
                AclActionsInterface::USER,
                $id,
                $this->request,
                $this->customFieldService
            );

            $this->checkChangeUserPass($id, $this->form->getItemData());
        } catch (SPException $e) {
            return ActionResponse::error($e->getMessage(), $e->getHint());
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return ActionResponse::error($e->getMessage());
        }

        return ActionResponse::ok(__u('User updated'));
    }
}