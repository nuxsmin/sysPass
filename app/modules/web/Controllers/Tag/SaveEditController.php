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

namespace SP\Modules\Web\Controllers\Tag;


use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Tag\UseCases\Dtos\UpdateTagCommand;
use SP\Domain\Tag\UseCases\UpdateTagUseCase;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class SaveEditController
 */
final class SaveEditController extends ControllerBase
{
    public function __construct(
        WebControllerHelper $webControllerHelper,
        private readonly UpdateTagUseCase $updateTagUseCase
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();
    }

    #[Action(ResponseType::JSON)]
    public function saveEditAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::TAG_EDIT)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->updateTagUseCase->execute(
                new UpdateTagCommand($id, $this->request->analyzeString('name') ?? '')
            );
        } catch (SPException $e) {
            return ActionResponse::error($e->getMessage(), $e->getHint());
        }

        return ActionResponse::ok(__u('Tag updated'));
    }
}
