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

namespace SP\Modules\Web\Controllers\Category;


use SP\Core\Application;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Html\DataGrid\DataGridInterface;
use SP\Modules\Web\Controllers\Helpers\Grid\CategoryGrid;
use SP\Modules\Web\Controllers\SearchGridControllerBase;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * SearchController
 */
final class SearchController extends SearchGridControllerBase
{
    use ItemTrait;

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                      $application,
        WebControllerHelper              $webControllerHelper,
        private readonly CategoryService $categoryService,
        private readonly CategoryGrid    $categoryGrid
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * getSearchGrid
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        return $this->categoryGrid->updatePager(
            $this->categoryGrid->getGrid($this->categoryService->search($itemSearchData)),
            $itemSearchData
        );
    }

    protected function getAclAction(): int
    {
        return AclActionsInterface::CATEGORY_SEARCH;
    }
}
