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
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\ItemPreset\Models\Password;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Modules\Web\Controllers\Helpers\Account\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;
use function SP\processException;

/**
 * Class ViewPassController
 */
final class ViewPassController extends AccountControllerBase
{
    use JsonTrait;

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
     * @param int $parentId
     *
     * @return bool|null
     * @throws SPException
     */
    public function viewPassAction(int $id, int $parentId = 0): ?bool
    {
        try {
            $account = $this->accountService->getPasswordForId($id);

            $passwordPreset = $this->getPasswordPreset();
            $useImage = $this->configData->isAccountPassToImage()
                        || ($passwordPreset !== null && $passwordPreset->isUseImage());

            $this->view->assign('isLinked', $parentId > 0);

            $data = $this->accountPasswordHelper->getPasswordView($account, $useImage);

            $this->accountService->incrementDecryptCounter($id);

            $this->eventDispatcher->notify(
                'show.account.pass',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Password viewed'))
                                ->addDetail(__u('Account'), $account->getName())
                )
            );

            return $this->returnJsonResponseData($data);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
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
