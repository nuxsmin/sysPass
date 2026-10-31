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

use SP\Domain\Api\Ports\ApiService;
use SP\Domain\Category\Ports\CategoryAdapter;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Modules\Api\Controllers\ApiControllerHelper;
use SP\Modules\Api\Controllers\ControllerBase;
use SP\Modules\Api\Controllers\Help\CategoryHelp;

/**
 * Class CategoryBase
 */
abstract class CategoryBase extends ControllerBase
{
    /**
     * @throws InvalidClassException
     */
    public function __construct(
        ApiControllerHelper                $controllerHelper,
        ApiService                         $apiService,
        protected readonly CategoryService $categoryService,
        protected readonly CategoryAdapter $categoryAdapter
    ) {
        parent::__construct($controllerHelper, $apiService);

        $this->apiService->setHelpClass(CategoryHelp::class);
    }
}
