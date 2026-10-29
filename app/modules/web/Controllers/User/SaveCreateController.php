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
use SP\Core\Application;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\UseCases\CreateUserUseCase;
use SP\Domain\User\UseCases\Dtos\CreateUserCommand;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class SaveCreateController
 */
final class SaveCreateController extends UserSaveBase
{
    public function __construct(
        Application            $application,
        WebControllerHelper    $webControllerHelper,
        private readonly CreateUserUseCase $createUserUseCase,
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
    public function saveCreateAction(): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::USER_CREATE)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->form->validateFor(AclActionsInterface::USER_CREATE);

            $result = $this->createUserUseCase->execute(
                new CreateUserCommand($this->form->getItemData())
            );

            $this->addCustomFieldsForItem(
                AclActionsInterface::USER,
                $result->id,
                $this->request,
                $this->customFieldService
            );

            $this->checkChangeUserPass($result->id, $this->form->getItemData());
        } catch (SPException $e) {
            return ActionResponse::error($e->getMessage(), $e->getHint());
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return ActionResponse::error($e->getMessage());
        }

        return ActionResponse::ok(__u('User added'));
    }
}