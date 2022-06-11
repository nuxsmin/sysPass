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

namespace SP\Domain\Account\Services;

use Defuse\Crypto\Exception\CryptoException;
use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Crypt;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Domain\Account\AccountCryptServiceInterface;
use SP\Domain\Account\AccountHistoryServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Crypt\Services\UpdateMasterPassRequest;
use SP\Domain\Task\Services\TaskFactory;
use SP\Util\Util;

/**
 * Class AccountCryptService
 *
 * @package SP\Domain\Account\Services
 */
final class AccountCryptService extends Service implements AccountCryptServiceInterface
{
    private AccountService           $accountService;
    private AccountHistoryService    $accountHistoryService;
    private ?UpdateMasterPassRequest $request = null;

    public function __construct(
        Application $application,
        AccountService $accountService,
        AccountHistoryServiceInterface $accountHistoryService
    ) {
        parent::__construct($application);

        $this->accountService = $accountService;
        $this->accountHistoryService = $accountHistoryService;
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @throws ServiceException
     */
    public function updateMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest): void
    {
        $this->request = $updateMasterPassRequest;

        try {
            $this->eventDispatcher->notifyEvent(
                'update.masterPassword.accounts.start',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Update Master Password'))
                )
            );

            if ($this->request->useTask()) {
                $task = $this->request->getTask();

                TaskFactory::update(
                    $task,
                    TaskFactory::createMessage(
                        $task->getTaskId(),
                        __u('Update Master Password')
                    )
                );
            }

            $eventMessage = $this->processAccounts(
                $this->accountService->getAccountsPassData(),
                function (AccountPasswordRequest $request) {
                    $this->accountService->updatePasswordMasterPass($request);
                }
            );

            $this->eventDispatcher->notifyEvent(
                'update.masterPassword.accounts.end',
                new Event($this, $eventMessage)
            );
        } catch (Exception $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new ServiceException(
                __u('Error while updating the accounts\' passwords'),
                SPException::ERROR,
                null,
                $e->getCode(),
                $e
            );
        }
    }

    private function processAccounts(
        array $accounts,
        callable $passUpdater
    ): EventMessage {
        set_time_limit(0);

        $accountsOk = [];
        $errorCount = 0;
        $counter = 0;
        $startTime = time();
        $numAccounts = count($accounts);

        $eventMessage = EventMessage::factory();

        if ($numAccounts === 0) {
            $eventMessage->addDescription(__u('There are no accounts for processing'));
            $eventMessage->addDetail(__u('Accounts updated'), __u('N/A'));
            $eventMessage->addDetail(__u('Errors'), 0);

            return $eventMessage;
        }

        $configData = $this->config->getConfigData();
        $currentMasterPassHash = $this->request->getCurrentHash();

        if ($this->request->useTask()) {
            $task = $this->request->getTask();
        }

        foreach ($accounts as $account) {
            // No realizar cambios si está en modo demo
            if ($configData->isDemoEnabled()) {
                $accountsOk[] = $account->id;
                continue;
            }

            if ($counter % 100 === 0) {
                $eta = Util::getETA($startTime, $counter, $numAccounts);

                if (isset($task)) {
                    $taskMessage = TaskFactory::createMessage(
                        $task->getTaskId(),
                        __('Update Master Password')
                    )->setMessage(sprintf(__('Accounts updated: %d / %d'), $counter, $numAccounts))
                        ->setProgress(round(($counter * 100) / $numAccounts, 2))
                        ->setTime(sprintf('ETA: %ds (%.2f/s)', $eta[0], $eta[1]));

                    TaskFactory::update($task, $taskMessage);

                    logger($taskMessage->composeText());
                } else {
                    logger(
                        sprintf(
                            __('Updated accounts: %d / %d - %d%% - ETA: %ds (%.2f/s)'),
                            $counter,
                            $numAccounts,
                            round(($counter * 100) / $numAccounts, 2),
                            $eta[0],
                            $eta[1]
                        )
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
            } catch (SPException|CryptoException $e) {
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
     * @throws ServiceException
     */
    public function updateHistoryMasterPassword(
        UpdateMasterPassRequest $updateMasterPassRequest
    ): void {
        $this->request = $updateMasterPassRequest;

        try {
            $this->eventDispatcher->notifyEvent(
                'update.masterPassword.accountsHistory.start',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Update Master Password (H)'))
                )
            );

            if ($this->request->useTask()) {
                $task = $this->request->getTask();

                TaskFactory::update(
                    $task,
                    TaskFactory::createMessage(
                        $task->getTaskId(),
                        __u('Update Master Password (H)')
                    )
                );
            }

            $eventMessage = $this->processAccounts(
                $this->accountHistoryService->getAccountsPassData(),
                function (AccountPasswordRequest $request) {
                    $request->hash = $this->request->getHash();

                    $this->accountHistoryService->updatePasswordMasterPass($request);
                }
            );

            $this->eventDispatcher->notifyEvent(
                'update.masterPassword.accountsHistory.end',
                new Event($this, $eventMessage)
            );
        } catch (Exception $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new ServiceException(
                __u('Error while updating the accounts\' passwords in history'),
                SPException::ERROR,
                null,
                $e->getCode(),
                $e
            );
        }
    }
}