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

use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapWeb;
use SP\Domain\Account\Models\PublicLinkList;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Account\UseCases\Dtos\GetPublicLinkCommand;
use SP\Domain\Account\UseCases\GetPublicLinkUseCase;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Modules\Web\Mvc\View\Components\SelectItemAdapter;

/**
 * Class PublicLinkViewBase
 */
abstract class PublicLinkViewBase extends ControllerBase
{
    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        private readonly GetPublicLinkUseCase $getPublicLinkUseCase,
        private readonly AccountService $accountService,
        private readonly PublicLinkService $publicLinkService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    protected function setViewData(?int $publicLinkId = null): void
    {
        $this->view->addTemplate('public_link', 'itemshow');

        $publicLink = $publicLinkId
            ? $this->getPublicLinkUseCase->execute(new GetPublicLinkCommand($publicLinkId))->publicLink
            : new PublicLinkList();

        $this->view->assign('publicLink', $publicLink);
        $this->view->assign('usageInfo', unserialize($publicLink->getUseInfo(), ['allowed_classes' => false]));
        $this->view->assign(
            'accounts',
            SelectItemAdapter::factory($this->accountService->getForUser())
                ->getItemsFromModelSelected([$publicLink->getItemId()])
        );

        $this->view->assign('nextAction', $this->acl->getRouteFor(AclActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $baseUrl = ($this->configData->getApplicationUrl() ?: BootstrapWeb::$WEBURI).BootstrapWeb::$SUBURI;

            $this->view->assign(
                'publicLinkURL',
                $publicLink->getHash()
                    ? $this->publicLinkService->buildLinkUrl($baseUrl, $publicLink->getHash())
                    : null
            );
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }
}
