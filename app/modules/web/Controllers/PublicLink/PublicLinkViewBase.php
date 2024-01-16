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

namespace SP\Modules\Web\Controllers\PublicLink;


use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapWeb;
use SP\DataModel\PublicLinkList;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Account\Services\PublicLink;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\Components\SelectItemAdapter;

/**
 * Class PublicLinkViewBase
 */
abstract class PublicLinkViewBase extends ControllerBase
{
    private PublicLinkService $publicLinkService;
    private AccountService    $accountService;

    public function __construct(
        Application       $application,
        WebControllerHelper $webControllerHelper,
        PublicLinkService $publicLinkService,
        AccountService    $accountService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->publicLinkService = $publicLinkService;
        $this->accountService = $accountService;
    }

    /**
     * Sets view data for displaying public link's data
     *
     * @param  int|null  $publicLinkId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    protected function setViewData(?int $publicLinkId = null): void
    {
        $this->view->addTemplate('public_link', 'itemshow');

        $publicLink = $publicLinkId
            ? $this->publicLinkService->getById($publicLinkId)
            : new PublicLinkList();

        $this->view->assign('publicLink', $publicLink);
        $this->view->assign('usageInfo', unserialize($publicLink->getUseInfo(), ['allowed_classes' => false]));
        $this->view->assign(
            'accounts',
            SelectItemAdapter::factory($this->accountService->getForUser())
                ->getItemsFromModelSelected([$publicLink->getItemId()])
        );

        $this->view->assign('nextAction', Acl::getActionRoute(AclActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $baseUrl = ($this->configData->getApplicationUrl() ?: BootstrapWeb::$WEBURI).BootstrapWeb::$SUBURI;

            $this->view->assign('publicLinkURL', PublicLink::getLinkForHash($baseUrl, $publicLink->getHash()));
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }
}
