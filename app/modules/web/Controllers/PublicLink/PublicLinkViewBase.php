<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Account\Models\PublicLinkList;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Account\UseCases\Dtos\GetPublicLinkCommand;
use SP\Domain\Account\UseCases\GetPublicLinkUseCase;
use SP\Domain\Common\Models\ItemWithIdAndNameModel;
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
        WebControllerHelper $webControllerHelper,
        private readonly GetPublicLinkUseCase $getPublicLinkUseCase,
        private readonly AccountService $accountService,
        private readonly PublicLinkService $publicLinkService
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();
    }

    protected function setViewData(?int $publicLinkId = null): void
    {
        $this->view->addTemplate('public_link', 'itemshow');

        $publicLink = $publicLinkId
            ? $this->getPublicLinkUseCase->execute(new GetPublicLinkCommand($publicLinkId))->publicLink
            : new PublicLinkList();

        $this->view->assign('publicLink', $publicLink);
        $this->view->assign(
            'usageInfo',
            unserialize($publicLink->getUseInfo() ?? '', ['allowed_classes' => false])
        );

        $accounts = array_map(
            static function (object $account): ItemWithIdAndNameModel {
                if ($account instanceof ItemWithIdAndNameModel) {
                    return $account;
                }

                $id = $account->id ?? ($account['id'] ?? null);
                $name = $account->name ?? ($account['name'] ?? null);
                $clientName = $account->clientName ?? ($account['clientName'] ?? '');

                return new readonly class(
                    $id !== null ? (int)$id : null,
                    $name !== null ? (string)$name : null,
                    (string)$clientName
                ) implements ItemWithIdAndNameModel {
                    public function __construct(
                        private ?int $id,
                        private ?string $name,
                        public string $clientName
                    ) {
                    }

                    public function getId(): ?int
                    {
                        return $this->id;
                    }

                    public function getName(): ?string
                    {
                        return $this->name;
                    }
                };
            },
            $this->accountService->getForUser()
        );

        $this->view->assign(
            'accounts',
            SelectItemAdapter::factory($accounts)
                ->getItemsFromModelSelected([$publicLink->getItemId()])
        );

        $this->view->assign('nextAction', $this->acl->getRouteFor(AclActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $baseUrl = rtrim($this->configData->getApplicationUrl() ?: $this->uriContext->getWebUri(), '/');

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
