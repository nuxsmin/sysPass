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

namespace SP\Modules\Web\Controllers\AccessManager;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Auth\Ports\AuthTokenService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\Ports\UserService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\Grid\AuthTokenGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\PublicLinkGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\UserGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\UserGroupGrid;
use SP\Modules\Web\Controllers\Helpers\Grid\UserProfileGrid;
use SP\Modules\Web\Controllers\Helpers\TabsGridHelper;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class IndexController
 *
 * @package SP\Modules\Web\Controllers
 */
final class IndexController extends ControllerBase
{
    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                         $application,
        WebControllerHelper                 $webControllerHelper,
        protected TabsGridHelper            $tabsGridHelper,
        private readonly UserGrid           $userGrid,
        private readonly UserGroupGrid      $userGroupGrid,
        private readonly UserProfileGrid    $userProfileGrid,
        private readonly AuthTokenGrid      $authTokenGrid,
        private readonly PublicLinkGrid     $publicLinkGrid,
        private readonly UserService        $userService,
        private readonly UserGroupService   $userGroupService,
        private readonly UserProfileService $userProfileService,
        private readonly AuthTokenService   $authTokenService,
        private readonly PublicLinkService  $publicLinkService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * @return ActionResponse
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    #[Action(ResponseType::PLAIN_TEXT)]
    public function indexAction(): ActionResponse
    {
        return ActionResponse::ok($this->getGridTabs());
    }

    /**
     * Returns a tabbed grid with items
     *
     * @return string
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    protected function getGridTabs(): string
    {
        $itemSearchData = new ItemSearchDto(null, 0, $this->configData->getAccountCount());

        if ($this->checkAccess(AclActionsInterface::USER)) {
            $this->tabsGridHelper->addTab(
                $this->userGrid->getGrid($this->userService->search($itemSearchData))->updatePager()
            );
        }

        if ($this->checkAccess(AclActionsInterface::GROUP)) {
            $this->tabsGridHelper->addTab(
                $this->userGroupGrid->getGrid($this->userGroupService->search($itemSearchData))->updatePager()
            );
        }

        if ($this->checkAccess(AclActionsInterface::PROFILE)) {
            $this->tabsGridHelper->addTab(
                $this->userProfileGrid->getGrid($this->userProfileService->search($itemSearchData))->updatePager()
            );
        }

        if ($this->checkAccess(AclActionsInterface::AUTHTOKEN)) {
            $this->tabsGridHelper->addTab(
                $this->authTokenGrid->getGrid($this->authTokenService->search($itemSearchData))->updatePager()
            );
        }

        if ($this->configData->isPublinksEnabled()
            && $this->checkAccess(AclActionsInterface::PUBLICLINK)
        ) {
            $this->tabsGridHelper->addTab(
                $this->publicLinkGrid->getGrid($this->publicLinkService->search($itemSearchData))->updatePager()
            );
        }

        $this->eventDispatcher->notify('show.itemlist.accesses', new Event($this));

        $this->tabsGridHelper->renderTabs(
            $this->acl->getRouteFor(AclActionsInterface::ACCESS_MANAGE),
            $this->request->analyzeInt('tabIndex', 0)
        );

        return $this->render();
    }

    /**
     * @return TabsGridHelper
     */
    public function getTabsGridHelper(): TabsGridHelper
    {
        return $this->tabsGridHelper;
    }
}
