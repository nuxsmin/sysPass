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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Events\Event;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Web\Util\ErrorUtil;

use function SP\__;
use function SP\processException;

/**
 * Class EditPassController
 */
final class EditPassController extends AccountViewBase
{
    /**
     * Obtener los datos para mostrar el interface para modificar la clave de cuenta
     *
     * @param int $id Account's ID
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function editPassAction(int $id): void
    {
        try {
            $this->accountHelper->initializeFor(AclActionsInterface::ACCOUNT_EDIT_PASS);

            $accountEnrichedDto = new AccountEnrichedDto($this->accountService->getByIdEnriched($id));
            $accountEnrichedDto = $this->accountService->withUsers($accountEnrichedDto);
            $accountEnrichedDto = $this->accountService->withUserGroups($accountEnrichedDto);

            $this->accountHelper->setViewForAccount($accountEnrichedDto);

            $this->view->addTemplate('account-editpass');
            $this->view->assign(
                'title',
                [
                    'class' => 'titleOrange',
                    'name' => __('Edit Account Password'),
                    'icon' => $this->icons->editPass()->getIcon(),
                ]
            );
            $this->view->assign('formRoute', 'account/saveEditPass');

            $this->eventDispatcher->notify('show.account.editpass', new Event($this));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            $this->view();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            if ($this->isAjax === false) {
                $this->upgradeView();
            }

            ErrorUtil::showExceptionInView($this->view, $e, 'account-editpass');
        }
    }
}
