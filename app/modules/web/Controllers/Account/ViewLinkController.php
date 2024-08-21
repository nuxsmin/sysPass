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

namespace SP\Modules\Web\Controllers\Account;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\UI\ThemeIcons;
use SP\Domain\Account\Dtos\AccountViewDto;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Http\Providers\Uri;
use SP\Domain\Image\Ports\ImageService;
use SP\Modules\Web\Util\ErrorUtil;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class ViewLinkController
 */
final class ViewLinkController extends AccountControllerBase
{
    private readonly ThemeIcons $icons;

    public function __construct(
        Application                        $application,
        WebControllerHelper                $webControllerHelper,
        private readonly AccountService    $accountService,
        private readonly PublicLinkService $publicLinkService,
        private readonly ImageService      $imageUtil
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->icons = $this->theme->getIcons();
    }

    /**
     * View public link action
     *
     * @param string $hash Link's hash
     */
    public function viewLinkAction(string $hash): void
    {
        try {
            $this->layoutHelper->getPublicLayout('account-link', 'account');

            $publicLink = $this->publicLinkService->getByHash($hash);

            if (time() < $publicLink->getDateExpire()
                && $publicLink->getCountViews() < $publicLink->getMaxCountViews()
            ) {
                $this->publicLinkService->addLinkView($publicLink);

                $this->accountService->incrementViewCounter($publicLink->getItemId());
                $this->accountService->incrementDecryptCounter($publicLink->getItemId());

                $vault = Serde::deserialize($publicLink->getData(), VaultInterface::class);

                $accountViewDto = AccountViewDto::fromModel(
                    Serde::deserialize(
                        $vault->getData(
                            $this->publicLinkService->getPublicLinkKey($publicLink->getHash())->getKey()
                        ),
                        Simple::class
                    )
                );

                $this->view->assign(
                    'title',
                    [
                        'class' => 'titleNormal',
                        'name' => __('Account Details'),
                        'icon' => $this->icons->view()->getIcon(),
                    ]
                );

                $this->view->assign('isView', true);
                $useImage = $this->configData->isPublinksImageEnabled()
                            || $this->configData->isAccountPassToImage();
                $this->view->assign('useImage', $useImage);

                if ($useImage) {
                    $this->view->assign(
                        'accountPassImage',
                        $this->imageUtil->convertText($accountViewDto->pass)
                    );
                } else {
                    $this->view->assign(
                        'copyPassRoute',
                        $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW_PASS)
                    );
                }

                $this->view->assign('accountData', $accountViewDto);

                $clientAddress = $this->configData->isDemoEnabled()
                    ? '***'
                    : $this->request->getClientAddress(true);

                $baseUrl = ($this->configData->getApplicationUrl() ?: $this->uriContext->getWebUri()) .
                           $this->uriContext->getSubUri();

                $deepLink = new Uri($baseUrl);
                $deepLink->addParam(
                    'r',
                    sprintf(
                        "%s/%s",
                        $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_VIEW),
                        $accountViewDto->getId()
                    )
                );

                $this->eventDispatcher->notify(
                    'show.account.link',
                    new Event(
                        $this,
                        EventMessage::build(__u('Link viewed'))
                                    ->addDetail(__u('Account'), $accountViewDto->getName())
                                    ->addDetail(__u('Client'), $accountViewDto->getClientName())
                                    ->addDetail(__u('Agent'), $this->request->getHeader('User-Agent'))
                                    ->addDetail(__u('HTTPS'), $this->request->isHttps() ? __u('ON') : __u('OFF'))
                                    ->addDetail(__u('IP'), $clientAddress)
                                    ->addDetail(
                                        __u('Link'),
                                        $deepLink->getUriSigned($this->configData->getPasswordSalt())
                                    )
                                    ->addExtra('userId', $publicLink->getUserId())
                                    ->addExtra('notify', $publicLink->isNotify())
                    )
                );
            } else {
                ErrorUtil::showErrorInView(
                    $this->view,
                    ErrorUtil::ERR_PAGE_NO_PERMISSION,
                    true,
                    'account-link'
                );
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            ErrorUtil::showExceptionInView($this->view, $e, 'account-link');
        }
    }
}
