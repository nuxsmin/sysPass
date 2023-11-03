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

namespace SP\Domain\Account\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Crypt\CryptInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\CryptException;
use SP\Core\Exceptions\SPException;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Ports\AccountCryptServiceInterface;
use SP\Domain\Account\Ports\AccountHistoryServiceInterface;
use SP\Domain\Account\Ports\AccountServiceInterface;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Crypt\Services\UpdateMasterPassRequest;
use SP\Domain\Task\Services\TaskFactory;
use SP\Util\Util;
use function SP\__;
use function SP\__u;
use function SP\logger;

/**
 * Class AccountCryptService
 *
 * @package SP\Domain\Account\Services
 */
final class AccountCryptService extends Service implements AccountCryptServiceInterface
{
    public function __construct(
        Application $application,
        private AccountServiceInterface $accountService,
        private AccountHistoryServiceInterface $accountHistoryService,
        private CryptInterface $crypt
    ) {
        parent::__construct($application);
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @throws ServiceException
     */
    public function updateMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest): void
    {
        try {
            $this->eventDispatcher->notify(
                'update.masterPassword.accounts.start',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Update Master Password'))
                )
            );

            $task = $updateMasterPassRequest->getTask();

            if (null !== $task) {
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
                function (int $accountId, EncryptedPassword $encryptedPassword) {
                    $this->accountService->updatePasswordMasterPass($accountId, $encryptedPassword);
                },
                $updateMasterPassRequest
            );

            $this->eventDispatcher->notify(
                'update.masterPassword.accounts.end',
                new Event($this, $eventMessage)
            );
        } catch (Exception $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            throw new ServiceException(
                __u('Error while updating the accounts\' passwords'),
                SPException::ERROR,
                null,
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param  \SP\Domain\Account\Models\Account[]  $accounts
     * @param  callable  $passUpdater
     * @param  \SP\Domain\Crypt\Services\UpdateMasterPassRequest  $updateMasterPassRequest
     *
     * @return \SP\Core\Events\EventMessage
     */
    private function processAccounts(
        array $accounts,
        callable $passUpdater,
        UpdateMasterPassRequest $updateMasterPassRequest
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
        $currentMasterPassHash = $updateMasterPassRequest->getCurrentHash();

        $task = $updateMasterPassRequest->getTask();

        foreach ($accounts as $account) {
            // No realizar cambios si está en modo demo
            if ($configData->isDemoEnabled()) {
                $accountsOk[] = $account->getId();
                continue;
            }

            if ($counter % 100 === 0) {
                $eta = Util::getETA($startTime, $counter, $numAccounts);

                if (null !== $task) {
                    $taskMessage = TaskFactory::createMessage(
                        $task->getTaskId(),
                        __('Update Master Password')
                    )->setMessage(
                        sprintf(__('Accounts updated: %d / %d - ETA: %ds (%.2f/s)'), $counter, $numAccounts, ...$eta)
                    )->setProgress(round(($counter * 100) / $numAccounts, 2));

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
                $eventMessage->addDetail($account->getName(), $account->getId());
                continue;
            }

            try {
                $encryptedPassword = $this->getPasswordEncrypted(
                    $this->crypt->decrypt(
                        $account->getPass(),
                        $account->getKey(),
                        $updateMasterPassRequest->getCurrentMasterPass()
                    ),
                    $updateMasterPassRequest->getNewMasterPass()
                );

                // Call the specific updater
                $passUpdater($account->getId(), $encryptedPassword);

                $accountsOk[] = $account->getId();
                $counter++;
            } catch (SPException $e) {
                $this->eventDispatcher->notify('exception', new Event($e));

                $errorCount++;

                $eventMessage->addDescription(__u('Error while updating the account\'s password'));
                $eventMessage->addDetail($account->getName(), $account->getId());
            }
        }

        $eventMessage->addDetail(__u('Accounts updated'), implode(',', $accountsOk));
        $eventMessage->addDetail(__u('Errors'), $errorCount);

        return $eventMessage;
    }

    /**
     * Devolver los datos de la clave encriptados
     *
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function getPasswordEncrypted(string $pass, ?string $masterPass = null): EncryptedPassword
    {
        try {
            if ($masterPass === null) {
                $masterPass = $this->getMasterKeyFromContext();
            }

            if (empty($masterPass)) {
                throw new ServiceException(__u('Master password not set'));
            }

            $key = $this->crypt->makeSecuredKey($masterPass);

            $encryptedPassword = new EncryptedPassword(
                $this->crypt->encrypt($pass, $key, $masterPass),
                $key
            );

            if (strlen($encryptedPassword->getPass()) > 1000 || strlen($encryptedPassword->getKey()) > 1000) {
                throw new ServiceException(__u('Internal error'));
            }

            return $encryptedPassword;
        } catch (CryptException $e) {
            throw new ServiceException(__u('Internal error'), SPException::ERROR, null, $e->getCode(), $e);
        }
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @throws ServiceException
     */
    public function updateHistoryMasterPassword(
        UpdateMasterPassRequest $updateMasterPassRequest
    ): void {
        try {
            $this->eventDispatcher->notify(
                'update.masterPassword.accountsHistory.start',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Update Master Password (H)'))
                )
            );

            $task = $updateMasterPassRequest->getTask();

            if (null !== $task) {
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
                function (int $accountId, EncryptedPassword $encryptedPassword) {
                    $this->accountHistoryService->updatePasswordMasterPass($accountId, $encryptedPassword);
                },
                $updateMasterPassRequest
            );

            $this->eventDispatcher->notify(
                'update.masterPassword.accountsHistory.end',
                new Event($this, $eventMessage)
            );
        } catch (Exception $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

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
