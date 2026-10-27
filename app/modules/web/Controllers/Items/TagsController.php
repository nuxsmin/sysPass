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

namespace SP\Modules\Web\Controllers\Items;

use Klein\Response;
use SP\Core\Application;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseStatus;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Http\Header;
use SP\Domain\Tag\Ports\TagService;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Mvc\Controller\SimpleControllerHelper;
use SP\Modules\Web\Mvc\View\Components\SelectItemAdapter;

/**
 * Class TagsController
 */
final class TagsController extends SimpleControllerBase
{
    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        private readonly TagService $tagService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->checks();
    }

    #[Action(ResponseType::CALLBACK)]
    public function tagsAction(): ActionResponse
    {
        $data = SelectItemAdapter::factory($this->tagService->getAll())->getJsonItemsFromModel();

        return new ActionResponse(
            ResponseStatus::OK,
            function (Response $response) use ($data) {
                $response->header(Header::CONTENT_TYPE->value, Header::CONTENT_TYPE_JSON->value);
                $response->body($data);
            }
        );
    }
}
