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

namespace SP\Modules\Web\Controllers\CustomField;

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Domain\CustomField\UseCases\CreateCustomFieldUseCase;
use SP\Domain\CustomField\UseCases\Dtos\CreateCustomFieldCommand;

use function SP\__u;

/**
 * Class SaveCreateController
 */
final class SaveCreateController extends CustomFieldSaveBase
{
    public function __construct(
        WebControllerHelper $webControllerHelper,
        private readonly CreateCustomFieldUseCase $createCustomFieldUseCase
    ) {
        parent::__construct($webControllerHelper);
    }

    #[Action(ResponseType::JSON)]
    public function saveCreateAction(): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::CUSTOMFIELD_CREATE)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->form->validateFor(AclActionsInterface::CUSTOMFIELD_CREATE);

            $result = $this->createCustomFieldUseCase->execute(
                new CreateCustomFieldCommand($this->form->getItemData())
            );

        } catch (SPException $e) {
            return ActionResponse::error($e->getMessage(), $e->getHint());
        }

        return ActionResponse::ok(__u('Field added'));
    }
}
