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


use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use JsonException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountServiceInterface;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\Helpers\Account\AccountPasswordHelper;
use SP\Modules\Web\Controllers\Helpers\HelperException;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class CopyPassController
 */
final class CopyPassController extends AccountControllerBase
{
    use JsonTrait;

    private AccountServiceInterface $accountService;
    private AccountPasswordHelper                            $accountPasswordHelper;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountServiceInterface $accountService,
        AccountPasswordHelper $accountPasswordHelper
    ) {
        parent::__construct(
            $application,
            $webControllerHelper
        );

        $this->accountService = $accountService;
        $this->accountPasswordHelper = $accountPasswordHelper;
    }

    /**
     * Copy account's password
     *
     * @param  int  $id  Account's ID
     *
     * @return bool
     * @throws BadFormatException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     * @throws JsonException
     * @throws ConstraintException
     * @throws QueryException
     * @throws HelperException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function copyPassAction(int $id): bool
    {
        $account = $this->accountService->getPasswordForId($id);

        $data = [
            'accpass' => $this->accountPasswordHelper->getPasswordClear($account),
        ];

        $this->eventDispatcher->notify(
            'copy.account.pass',
            new Event(
                $this, EventMessage::factory()
                ->addDescription(__u('Password copied'))
                ->addDetail(__u('Account'), $account->getName())
            )
        );

        return $this->returnJsonResponseData($data);
    }
}
