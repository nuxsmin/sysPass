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

namespace SP\Modules\Web\Controllers\PublicLink;

use Exception;
use SP\Domain\Account\UseCases\DeletePublicLinkUseCase;
use SP\Domain\Account\UseCases\DeletePublicLinksUseCase;
use SP\Domain\Account\UseCases\Dtos\DeletePublicLinkCommand;
use SP\Domain\Account\UseCases\Dtos\DeletePublicLinksCommand;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Web\Mvc\Controller\ItemTrait;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class DeleteController
 */
final class DeleteController extends PublicLinkSaveBase
{
    use ItemTrait;

    public function __construct(
        WebControllerHelper $webControllerHelper,
        private readonly DeletePublicLinkUseCase $deletePublicLinkUseCase,
        private readonly DeletePublicLinksUseCase $deletePublicLinksUseCase
    ) {
        parent::__construct($webControllerHelper);
    }

    #[Action(ResponseType::JSON)]
    public function deleteAction(?int $id = null): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::PUBLICLINK_DELETE)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            if ($id === null) {
                $ids = array_map(
                    'intval',
                    $this->request->analyzeArray('items') ?? []
                );

                $this->deletePublicLinksUseCase->execute(new DeletePublicLinksCommand($ids));

                return ActionResponse::ok(__u('Links deleted'));
            }

            $this->deletePublicLinkUseCase->execute(new DeletePublicLinkCommand($id));
        } catch (Exception $e) {
            return ActionResponse::error($e->getMessage());
        }

        return ActionResponse::ok(__u('Link deleted'));
    }
}
