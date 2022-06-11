<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\DataModel\ItemPreset\Password;
use SP\Domain\Account\AccountServiceInterface;
use SP\Domain\ItemPreset\ItemPresetInterface;
use SP\Domain\ItemPreset\ItemPresetServiceInterface;
use SP\Modules\Web\Controllers\Helpers\Account\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class ViewPassController
 */
final class ViewPassController extends AccountControllerBase
{
    use JsonTrait;

    private AccountServiceInterface    $accountService;
    private AccountPasswordHelper      $accountPasswordHelper;
    private ItemPresetServiceInterface $itemPresetService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountServiceInterface $accountService,
        AccountPasswordHelper $accountPasswordHelper,
        ItemPresetServiceInterface $itemPresetService
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountService = $accountService;
        $this->accountPasswordHelper = $accountPasswordHelper;
        $this->itemPresetService = $itemPresetService;
    }

    /**
     * Display account's password
     *
     * @param  int  $id  Account's ID
     * @param  int  $parentId
     *
     * @return bool
     * @throws \JsonException
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

            $this->eventDispatcher->notifyEvent(
                'show.account.pass',
                new Event(
                    $this, EventMessage::factory()
                    ->addDescription(__u('Password viewed'))
                    ->addDetail(__u('Account'), $account->getName())
                )
            );

            return $this->returnJsonResponseData($data);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return Password
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\NoSuchPropertyException
     * @throws \SP\Core\Exceptions\QueryException
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