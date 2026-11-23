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

namespace SP\Modules\Api\Controllers\Category;

use League\Fractal\Resource\Item;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Util\Util;
use function SP\__u;

/**
 * Class ViewController
 *
 * @package SP\Modules\Api\Controllers
 */
final class ViewController extends CategoryBase
{
    /**
     * viewAction
     */
    #[Action(ResponseType::JSON_RPC)]
    public function viewAction(): ActionResponse
    {
        $this->setupApi(AclActionsInterface::CATEGORY_VIEW);

        $id = $this->apiService->getParamInt('id', true);
        $customFields = Util::boolval($this->apiService->getParamString('customFields'));

        $categoryData = $this->categoryService->getById($id);

        $this->eventDispatcher->notify(
            'show.category',
            new Event(
                $this,
                EventMessage::build()
                    ->addDescription(__u('Category displayed'))
                    ->addDetail(__u('Name'), $categoryData->getName())
                    ->addDetail('ID', $id)
            )
        );

        $out = $this->fractal->createData(new Item($categoryData, $this->categoryAdapter));

        if ($customFields) {
            $this->apiService->requireMasterPass();
            $this->fractal->parseIncludes(['customFields']);
        }

        return ActionResponse::ok('', $out->toArray(), $id);
    }
}
