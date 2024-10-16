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
use SP\Domain\Category\Models\Category;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * A base class for all viewable actions
 */
abstract class CategoryViewBase extends ControllerBase
{
    use ItemTrait;

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                             $application,
        WebControllerHelper                     $webControllerHelper,
        private readonly CategoryService        $categoryService,
        private readonly CustomFieldDataService $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * Sets view data for displaying category's data
     *
     * @param int|null $categoryId
     * @param bool $readOnly
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    protected function setViewData(?int $categoryId = null, bool $readOnly = true): void
    {
        $this->view->addTemplate('category', 'itemshow');

        $category = $categoryId
            ? $this->categoryService->getById($categoryId)
            : new Category();

        $this->view->assign('category', $category);
        $this->view->assign('nextAction', $this->acl->getRouteFor(AclActionsInterface::ITEMS_MANAGE));
        $this->view->assign('isView', $readOnly);

        if ($readOnly) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }

        $this->view->assign(
            'showViewCustomPass',
            $this->acl->checkUserAccess(AclActionsInterface::CUSTOMFIELD_VIEW_PASS)
        );
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(AclActionsInterface::CATEGORY, $categoryId, $this->customFieldService)
        );
    }
}
