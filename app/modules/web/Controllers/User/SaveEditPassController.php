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


use SP\Core\Application;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\UseCases\Dtos\UpdateUserPassCommand;
use SP\Domain\User\UseCases\UpdateUserPassUseCase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class SaveEditPassController
 */
final class SaveEditPassController extends UserSaveBase
{
    public function __construct(
        Application            $application,
        WebControllerHelper    $webControllerHelper,
        private readonly UpdateUserPassUseCase $updateUserPassUseCase,
        CustomFieldDataService $customFieldService,
        MailService            $mailService,
        UserPassRecoverService $userPassRecoverService
    ) {
        parent::__construct(
            $application,
            $webControllerHelper,
            $customFieldService,
            $mailService,
            $userPassRecoverService
        );
    }

    #[Action(ResponseType::JSON)]
    public function saveEditPassAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::USER_EDIT_PASS, $id)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->form->validateFor(AclActionsInterface::USER_EDIT_PASS, $id);

            $this->updateUserPassUseCase->execute(
                new UpdateUserPassCommand($id, (string)$this->form->getItemData()->getPass())
            );
        } catch (SPException $e) {
            return ActionResponse::error($e->getMessage(), $e->getHint());
        }

        return ActionResponse::ok(__u('Password updated'));
    }
}