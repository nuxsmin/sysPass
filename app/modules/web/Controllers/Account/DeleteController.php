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
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\UI\ThemeIcons;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Web\Controllers\Helpers\Account\AccountHelper;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Util\ErrorUtil;

/**
 * Class DeleteController
 */
final class DeleteController extends AccountControllerBase
{
    private AccountHelper                                    $accountHelper;
    private ThemeIcons     $icons;
    private AccountService $accountService;

    public function __construct(
        Application             $application,
        WebControllerHelper     $webControllerHelper,
        AccountHelper           $accountHelper,
        AccountService $accountService
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountHelper = $accountHelper;
        $this->accountService = $accountService;

        $this->icons = $this->theme->getIcons();
    }

    /**
     * Delete action
     *
     * @param int|null $id Account's ID
     */
    public function deleteAction(?int $id = null): void
    {
        try {
            $accountEnrichedDto = $this->accountService->getByIdEnriched($id);
            $accountEnrichedDto = $this->accountService->withUsers($accountEnrichedDto);
            $accountEnrichedDto = $this->accountService->withUserGroups($accountEnrichedDto);
            $accountEnrichedDto = $this->accountService->withTags($accountEnrichedDto);

            $this->accountHelper->setViewForAccount($accountEnrichedDto, AclActionsInterface::ACCOUNT_DELETE);

            $this->view->addTemplate('account');
            $this->view->assign(
                'title',
                [
                    'class' => 'titleRed',
                    'name' => __('Remove Account'),
                    'icon' => $this->icons->delete()->getIcon(),
                ]
            );
            $this->view->assign('formRoute', 'account/saveDelete');

            $this->eventDispatcher->notify('show.account.delete', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify(
                'exception',
                new Event($e)
            );

            if ($this->isAjax === false && !$this->view->isUpgraded()) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }
}
