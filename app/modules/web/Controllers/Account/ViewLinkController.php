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

namespace SP\Modules\Web\Controllers\Account;


use Exception;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Crypt\Vault;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\AccountExtData;
use SP\Domain\Account\Services\PublicLinkService;
use SP\Http\Uri;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Util\ErrorUtil;
use SP\Util\ImageUtil;
use SP\Util\ImageUtilInterface;
use SP\Util\Util;

/**
 * Class ViewLinkController
 */
final class ViewLinkController extends AccountControllerBase
{
    private \SP\Domain\Account\Ports\AccountServiceInterface $accountService;
    private ThemeIcons                                       $icons;
    private PublicLinkService                                $publicLinkService;
    private ImageUtil                                        $imageUtil;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        \SP\Domain\Account\Ports\AccountServiceInterface $accountService,
        \SP\Domain\Account\Ports\PublicLinkServiceInterface $publicLinkService,
        ImageUtilInterface $imageUtil
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountService = $accountService;
        $this->publicLinkService = $publicLinkService;
        $this->imageUtil = $imageUtil;

        $this->icons = $this->theme->getIcons();
    }

    /**
     * View public link action
     *
     * @param  string  $hash  Link's hash
     */
    public function viewLinkAction(string $hash): void
    {
        try {
            $this->layoutHelper->getPublicLayout('account-link', 'account');

            $publicLinkData = $this->publicLinkService->getByHash($hash);

            if (time() < $publicLinkData->getDateExpire()
                && $publicLinkData->getCountViews() < $publicLinkData->getMaxCountViews()
            ) {
                $this->publicLinkService->addLinkView($publicLinkData);

                $this->accountService->incrementViewCounter($publicLinkData->getItemId());
                $this->accountService->incrementDecryptCounter($publicLinkData->getItemId());

                /** @var Vault $vault */
                $vault = unserialize($publicLinkData->getData(), ['allowed_classes' => [Vault::class]]);

                /** @var AccountExtData $accountData */
                $accountData = Util::unserialize(
                    AccountExtData::class,
                    $vault->getData($this->publicLinkService->getPublicLinkKey($publicLinkData->getHash())->getKey())
                );

                $this->view->assign(
                    'title',
                    [
                        'class' => 'titleNormal',
                        'name'  => __('Account Details'),
                        'icon'  => $this->icons->getIconView()->getIcon(),
                    ]
                );

                $this->view->assign('isView', true);
                $this->view->assign(
                    'useImage',
                    $this->configData->isPublinksImageEnabled()
                    || $this->configData->isAccountPassToImage()
                );

                if ($this->view->useImage) {
                    $this->view->assign(
                        'accountPassImage',
                        $this->imageUtil->convertText($accountData->getPass())
                    );
                } else {
                    $this->view->assign(
                        'copyPassRoute',
                        Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW_PASS)
                    );
                }

                $this->view->assign('accountData', $accountData);

                $clientAddress = $this->configData->isDemoEnabled()
                    ? '***'
                    : $this->request->getClientAddress(true);

                $baseUrl = ($this->configData->getApplicationUrl() ?: BootstrapBase::$WEBURI).BootstrapBase::$SUBURI;

                $deepLink = new Uri($baseUrl);
                $deepLink->addParam('r', Acl::getActionRoute(ActionsInterface::ACCOUNT_VIEW).'/'.$accountData->getId());

                $this->eventDispatcher->notify(
                    'show.account.link',
                    new Event(
                        $this, EventMessage::factory()
                        ->addDescription(__u('Link viewed'))
                        ->addDetail(__u('Account'), $accountData->getName())
                        ->addDetail(__u('Client'), $accountData->getClientName())
                        ->addDetail(__u('Agent'), $this->request->getHeader('User-Agent'))
                        ->addDetail(__u('HTTPS'), $this->request->isHttps() ? __u('ON') : __u('OFF'))
                        ->addDetail(__u('IP'), $clientAddress)
                        ->addDetail(__u('Link'), $deepLink->getUriSigned($this->configData->getPasswordSalt()))
                        ->addExtra('userId', $publicLinkData->getUserId())
                        ->addExtra('notify', $publicLinkData->isNotify())
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
