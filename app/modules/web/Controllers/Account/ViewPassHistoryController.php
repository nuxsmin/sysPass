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

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\ItemPreset\Models\Password;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\Helpers\Account\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Helpers\HelperException;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class ViewPassHistoryController
 */
final class ViewPassHistoryController extends AccountControllerBase
{
    public function __construct(
        Application                            $application,
        WebControllerHelper                    $webControllerHelper,
        private readonly AccountService        $accountService,
        private readonly AccountPasswordHelper $accountPasswordHelper,
        private readonly ItemPresetService     $itemPresetService
    ) {
        parent::__construct($application, $webControllerHelper);
    }

    /**
     * Display account's password
     *
     * @param int $id Account's ID
     *
     * @return ActionResponse
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws CryptException
     * @throws NoSuchItemException
     * @throws HelperException
     */
    #[Action(ResponseType::JSON)]
    public function viewPassHistoryAction(int $id): ActionResponse
    {
        $account = $this->accountService->getPasswordHistoryForId($id);

        $passwordPreset = $this->getPasswordPreset();
        $useImage = $this->configData->isAccountPassToImage()
                    || ($passwordPreset !== null && $passwordPreset->isUseImage());

        $this->view->assign('isLinked', 0);

        $data = $this->accountPasswordHelper->getPasswordView($account, $useImage);

        $this->eventDispatcher->notify(
            'show.account.pass.history',
            new Event(
                $this,
                EventMessage::build(__u('Password viewed'))
                            ->addDetail(__u('Account'), $account->getName())
            )
        );

        return ActionResponse::ok('', $data);
    }

    /**
     * @return Password|null
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function getPasswordPreset(): ?Password
    {
        $itemPreset = $this->itemPresetService->getForCurrentUser(ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD);

        if ($itemPreset !== null && $itemPreset->getFixed() === 1) {
            return $itemPreset->hydrate(Password::class);
        }

        return null;
    }
}
