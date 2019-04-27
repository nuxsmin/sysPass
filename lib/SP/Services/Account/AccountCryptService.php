<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Services\Account;

use Defuse\Crypto\Exception\CryptoException;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Crypt\Crypt;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Services\Crypt\UpdateMasterPassRequest;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Services\Task\TaskFactory;
use SP\Util\Util;

/**
 * Class AccountCryptService
 *
 * @package SP\Services\Account
 */
final class AccountCryptService extends Service
{
    /**
     * @var AccountService
     */
    private $accountService;
    /**
     * @var AccountHistoryService
     */
    private $accountHistoryService;
    /**
     * @var UpdateMasterPassRequest
     */
    private $request;

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @param UpdateMasterPassRequest $updateMasterPassRequest
     *
     * @throws ServiceException
     */
    public function updateMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest)
    {
        $this->request = $updateMasterPassRequest;

        try {
            $this->eventDispatcher->notifyEvent('update.masterPassword.accounts.start',
                new Event($this, EventMessage::factory()->addDescription(__u('Update Master Password')))
            );

            if ($this->request->useTask()) {
                $task = $this->request->getTask();

                TaskFactory::update($task,
                    TaskFactory::createMessage($task->getTaskId(), __u('Update Master Password'))
                );
            }

            $eventMessage = $this->processAccounts($this->accountService->getAccountsPassData(), function ($request) {
                $this->accountService->updatePasswordMasterPass($request);
            });

            $this->eventDispatcher->notifyEvent('update.masterPassword.accounts.end', new Event($this, $eventMessage));
        } catch (Exception $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new ServiceException(
                __u('Error while updating the accounts\' passwords'),
                ServiceException::ERROR,
                null,
                $e->getCode(),
                $e);
        }
    }

    /**
     * @param array    $accounts
     * @param callable $passUpdater
     *
     * @return EventMessage
     * @throws ServiceException
     */
    private function processAccounts(array $accounts, callable $passUpdater)
    {
        set_time_limit(0);

        $accountsOk = [];
        $errorCount = 0;
        $counter = 0;
        $startTime = time();
        $numAccounts = count($accounts);

        if ($numAccounts === 0) {
            throw new ServiceException(__u('Error while retrieving the accounts\' passwords'), ServiceException::ERROR);
        }

        $configData = $this->config->getConfigData();
        $currentMasterPassHash = $this->request->getCurrentHash();

        if ($this->request->useTask()) {
            $task = $this->request->getTask();
        }

        $eventMessage = EventMessage::factory();

        foreach ($accounts as $account) {
            // No realizar cambios si está en modo demo
            if ($configData->isDemoEnabled()) {
                $accountsOk[] = $account->id;
                continue;
            }

            if ($counter % 100 === 0) {
                $eta = Util::getETA($startTime, $counter, $numAccounts);

                if (isset($task)) {
                    $taskMessage = TaskFactory::createMessage($task->getTaskId(), __('Update Master Password'))
                        ->setMessage(sprintf(__('Accounts updated: %d / %d'), $counter, $numAccounts))
                        ->setProgress(round(($counter * 100) / $numAccounts, 2))
                        ->setTime(sprintf('ETA: %ds (%.2f/s)', $eta[0], $eta[1]));

                    TaskFactory::update($task, $taskMessage);

                    logger($taskMessage->composeText());
                } else {
                    logger(
                        sprintf(__('Updated accounts: %d / %d - %d%% - ETA: %ds (%.2f/s)'),
                            $counter,
                            $numAccounts,
                            round(($counter * 100) / $numAccounts, 2),
                            $eta[0], $eta[1])
                    );
                }
            }

            if (isset($account->mPassHash) && $account->mPassHash !== $currentMasterPassHash) {
                $eventMessage->addDescription(__u('The record\'s master password does not match'));
                $eventMessage->addDetail($account->name, $account->id);
                continue;
            }

            $request = new AccountPasswordRequest();
            $request->id = $account->id;

            try {
                $passData = $this->accountService->getPasswordEncrypted(
                    Crypt::decrypt($account->pass, $account->key, $this->request->getCurrentMasterPass()),
                    $this->request->getNewMasterPass()
                );

                $request->key = $passData['key'];
                $request->pass = $passData['pass'];

                // Call the specific updater
                $passUpdater($request);

                $accountsOk[] = $account->id;
                $counter++;
            } catch (SPException $e) {
                $errorCount++;

                $eventMessage->addDescription(__u('Error while updating the account\'s password'));
                $eventMessage->addDetail($account->name, $account->id);
            } catch (CryptoException $e) {
                $errorCount++;

                $eventMessage->addDescription(__u('Error while updating the account\'s password'));
                $eventMessage->addDetail($account->name, $account->id);
            }
        }

        $eventMessage->addDetail(__u('Accounts updated'), implode(',', $accountsOk));
        $eventMessage->addDetail(__u('Errors'), $errorCount);

        return $eventMessage;
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @param UpdateMasterPassRequest $updateMasterPassRequest
     *
     * @throws ServiceException
     */
    public function updateHistoryMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest)
    {
        $this->request = $updateMasterPassRequest;

        try {
            $this->eventDispatcher->notifyEvent('update.masterPassword.accountsHistory.start',
                new Event($this, EventMessage::factory()->addDescription(__u('Update Master Password (H)')))
            );

            if ($this->request->useTask()) {
                $task = $this->request->getTask();

                TaskFactory::update($task,
                    TaskFactory::createMessage($task->getTaskId(), __u('Update Master Password (H)'))
                );
            }

            $eventMessage = $this->processAccounts($this->accountHistoryService->getAccountsPassData(), function ($request) {
                /** @var AccountPasswordRequest $request */
                $request->hash = $this->request->getHash();

                $this->accountHistoryService->updatePasswordMasterPass($request);
            });

            $this->eventDispatcher->notifyEvent('update.masterPassword.accountsHistory.end', new Event($this, $eventMessage));
        } catch (Exception $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new ServiceException(
                __u('Error while updating the accounts\' passwords in history'),
                ServiceException::ERROR,
                null,
                $e->getCode(),
                $e);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->accountService = $this->dic->get(AccountService::class);
        $this->accountHistoryService = $this->dic->get(AccountHistoryService::class);
    }
}