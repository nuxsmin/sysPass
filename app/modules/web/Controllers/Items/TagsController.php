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

use SP\Core\Application;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Services\JsonResponse;
use SP\Domain\Tag\Ports\TagService;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;
use SP\Mvc\View\Components\SelectItemAdapter;

/**
 * Class TagsController
 */
final class TagsController extends SimpleControllerBase
{
    private TagService $tagService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        TagService $tagService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->checks();

        $this->tagService = $tagService;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function tagsAction(): void
    {
        JsonResponse::factory($this->router->response())
            ->sendRaw(SelectItemAdapter::factory($this->tagService->getAll())->getJsonItemsFromModel());
    }
}
