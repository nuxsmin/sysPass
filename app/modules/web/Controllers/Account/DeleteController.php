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
use SP\Core\UI\ThemeIcons;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Web\Controllers\Helpers\Account\AccountHelper;
use SP\Modules\Web\Util\ErrorUtil;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__;
use function SP\processException;

/**
 * Class DeleteController
 */
final class DeleteController extends AccountControllerBase
{
    private readonly ThemeIcons $icons;

    public function __construct(
        Application                     $application,
        WebControllerHelper             $webControllerHelper,
        private readonly AccountHelper  $accountHelper,
        private readonly AccountService $accountService
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

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
            $this->accountHelper->initializeFor(AclActionsInterface::ACCOUNT_DELETE);

            $accountEnrichedDto = $this->accountService->withTags(
                $this->accountService->withUserGroups(
                    $this->accountService->withUsers(
                        new AccountEnrichedDto($this->accountService->getByIdEnriched($id))
                    )
                )
            );

            $this->accountHelper->setViewForAccount($accountEnrichedDto);

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

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account');
        }
    }
}
