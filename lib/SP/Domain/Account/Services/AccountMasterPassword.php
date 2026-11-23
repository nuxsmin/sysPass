<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Domain\Account\Services;

use Exception;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Ports\AccountCryptService;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountMasterPasswordService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Log\LoggerInterface;
use SP\Domain\Core\Log\NullLogger;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Services\Hash;
use function SP\__u;

/**
 * Class AccountMasterPassword
 */
final class AccountMasterPassword extends Service implements AccountMasterPasswordService
{
    public function __construct(
        ConfigFileService $config,
        EventDispatcherInterface $eventDispatcher,
        Context $context,
        private readonly AccountService        $accountService,
        private readonly AccountHistoryService $accountHistoryService,
        private readonly CryptInterface        $crypt,
        private readonly AccountCryptService   $accountCryptService,
        private readonly LoggerInterface       $logger = new NullLogger()
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @param UpdateMasterPassRequest $updateMasterPassRequest
     * @throws ServiceException
     */
    public function updateMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest): void
    {
        try {
            $this->eventDispatcher->notify(
                'update.masterPassword.accounts.start',
                new Event(
                    $this,
                    EventMessage::build()
                                ->addDescription(__u('Update Master Password'))
                )
            );

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

            if ($e instanceof ServiceException) {
                throw $e;
            }

            throw ServiceException::error(
                __u('Error while updating the accounts\' passwords'),
                null,
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param Account[] $accounts
     * @param callable $passUpdater
     * @param UpdateMasterPassRequest $updateMasterPassRequest
     *
     * @return EventMessage
     */
    private function processAccounts(
        array                   $accounts,
        callable                $passUpdater,
        UpdateMasterPassRequest $updateMasterPassRequest
    ): EventMessage {
        set_time_limit(0);

        $accountsOk = [];
        $errorCount = 0;
        $counter = 0;
        $startTime = time();
        $numAccounts = count($accounts);

        $eventMessage = EventMessage::build();

        if ($this->config->getConfigData()->isDemoEnabled()) {
            throw new ServiceException(__u('Master password change is unavailable in demo mode'));
        }

        if ($numAccounts === 0) {
            $eventMessage->addDescription(__u('There are no accounts for processing'));
            $eventMessage->addDetail(__u('Accounts updated'), __u('N/A'));
            $eventMessage->addDetail(__u('Errors'), 0);

            return $eventMessage;
        }

        $verifiedHashes = [];
        foreach ($accounts as $account) {
            if ($counter % 100 === 0) {
                $eta = self::getETA($startTime, $counter, $numAccounts);

                $this->logger->debug(
                    sprintf(
                        __u('Updated accounts: %d / %d - %d%% - ETA: %ds (%.2f/s)'),
                        $counter,
                        $numAccounts,
                        round(($counter * 100) / $numAccounts, 2),
                        $eta[0],
                        $eta[1]
                    )
                );
            }

            if (isset($account->mPassHash)) {
                $hash = $account->mPassHash;
                $verifiedHashes[$hash] ??= Hash::checkMasterKey($updateMasterPassRequest->getCurrentMasterPass(), $hash);
                if (!$verifiedHashes[$hash]) {
                    throw new ServiceException(sprintf(__u('Master password mismatch for record ID %d'), $account->id));
                }
            }

            try {
                $encryptedPassword = $this->accountCryptService
                    ->getPasswordEncrypted(
                        $this->crypt->decrypt(
                            $account->pass,
                            $account->key,
                            $updateMasterPassRequest->getCurrentMasterPass()
                        ),
                        $updateMasterPassRequest->getNewMasterPass()
                    );

                // Call the specific updater
                $passUpdater($account->id, $encryptedPassword);

                $accountsOk[] = $account->id;
                $counter++;
            } catch (SPException $e) {
                $this->eventDispatcher->notify('exception', new Event($e));

                throw new ServiceException(sprintf(__u('Error updating record ID %d'), $account->id), SPException::ERROR, null, $e->getCode(), $e);
            }
        }

        $eventMessage->addDetail(__u('Accounts updated'), implode(',', $accountsOk));
        $eventMessage->addDetail(__u('Errors'), $errorCount);

        return $eventMessage;
    }

    /**
     * Devolver el tiempo aproximado en segundos de una operación
     *
     * @return array Con el tiempo estimado y los elementos por segundo
     */
    public static function getETA(int $startTime, int $numItems, int $totalItems): array
    {
        if ($numItems > 0 && $totalItems > 0) {
            $runtime = time() - $startTime;
            $eta = (int)((($totalItems * $runtime) / $numItems) - $runtime);

            return [$eta, $runtime > 0 ? $numItems / $runtime : 0];
        }

        return [0, 0];
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @throws ServiceException
     */
    public function updateHistoryMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest): void
    {
        try {
            $this->eventDispatcher->notify(
                'update.masterPassword.accountsHistory.start',
                new Event(
                    $this,
                    EventMessage::build()
                                ->addDescription(__u('Update Master Password (H)'))
                )
            );

            $eventMessage = $this->processAccounts(
                $this->accountHistoryService->getAccountsPassData(),
                function (int $accountId, EncryptedPassword $encryptedPassword) use ($updateMasterPassRequest) {
                    $this->accountHistoryService->updatePasswordMasterPass(
                        $accountId,
                        new EncryptedPassword($encryptedPassword->getPass(), $encryptedPassword->getKey(), $updateMasterPassRequest->getHash())
                    );
                },
                $updateMasterPassRequest
            );

            $this->eventDispatcher->notify(
                'update.masterPassword.accountsHistory.end',
                new Event($this, $eventMessage)
            );
        } catch (Exception $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            if ($e instanceof ServiceException) {
                throw $e;
            }

            throw ServiceException::error(
                __u('Error while updating the accounts\' passwords in history'),
                null,
                $e->getCode(),
                $e
            );
        }
    }
}
