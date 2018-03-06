<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Crypt\Crypt;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Core\OldCrypt;
use SP\Core\TaskFactory;
use SP\Services\Crypt\UpdateMasterPassRequest;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Util\Util;

/**
 * Class AccountCryptService
 *
 * @package SP\Services\Account
 */
class AccountCryptService extends Service
{
    /**
     * @var AccountService
     */
    protected $accountService;
    /**
     * @var AccountHistoryService
     */
    protected $accountHistoryService;
    /**
     * @var UpdateMasterPassRequest
     */
    protected $request;


    /**
     * Actualiza las claves de todas las cuentas con la clave maestra actual
     * usando nueva encriptación.
     *
     * @param UpdateMasterPassRequest $updateMasterPassRequest
     * @throws ServiceException
     */
    public function updateOldPass(UpdateMasterPassRequest $updateMasterPassRequest)
    {
        set_time_limit(0);

        $this->request = $updateMasterPassRequest;

        $accountsOk = [];
        $errorCount = 0;

        $this->eventDispatcher->notifyEvent('update.masterPassword.accounts.start',
            new Event($this, EventMessage::factory()->addDescription(__u('Actualizar Clave Maestra')))
        );

        if (!OldCrypt::checkCryptModule()) {
            throw new ServiceException(__u('Error en el módulo de encriptación'), ServiceException::ERROR);
        }

        $accountsPass = $this->accountService->getAccountsPassData();
        $numAccounts = count($accountsPass);

        if ($numAccounts === 0) {
            throw new ServiceException(__u('Error al obtener las claves de las cuentas'), ServiceException::ERROR);
        }

        if ($this->request->useTask()) {
            $taskId = $this->request->getTask()->getTaskId();

            TaskFactory::update($taskId, TaskFactory::createMessage($taskId, __('Actualizar Clave Maestra')));
        }

        $counter = 0;
        $startTime = time();
        $configData = $this->config->getConfigData();
        $eventMessage = EventMessage::factory();

        foreach ($accountsPass as $account) {
            // No realizar cambios si está en modo demo
            if ($configData->isDemoEnabled()) {
                $accountsOk[] = $account->id;
                continue;
            }

            if ($counter % 100 === 0) {
                $eta = Util::getETA($startTime, $counter, $numAccounts);

                if (isset($taskId)) {
                    $taskMessage = TaskFactory::createMessage($taskId, __('Actualizar Clave Maestra'))
                        ->setMessage(sprintf(__('Cuentas actualizadas: %d / %d'), $counter, $numAccounts))
                        ->setProgress(round(($counter * 100) / $numAccounts, 2))
                        ->setTime(sprintf('ETA: %ds (%.2f/s)', $eta[0], $eta[1]));


                    TaskFactory::update($taskId, $taskMessage);

                    debugLog($taskMessage->composeText());
                } else {
                    debugLog(
                        sprintf(__('Cuentas actualizadas: %d / %d - %d%% - ETA: %ds (%.2f/s)'),
                            $counter,
                            $numAccounts,
                            round(($counter * 100) / $numAccounts, 2),
                            $eta[0], $eta[1])
                    );
                }
            }

            $accountRequest = new AccountPasswordRequest();
            $accountRequest->id = $account->id;

            try {
                $passData = $this->accountService->getPasswordEncrypted(
                    OldCrypt::getDecrypt($account->pass, $account->key, $this->request->getCurrentMasterPass()),
                    $this->request->getNewMasterPass()
                );

                $accountRequest->key = $passData['key'];
                $accountRequest->pass = $passData['pass'];

                $this->accountService->updatePasswordMasterPass($accountRequest);

                $accountsOk[] = $account->id;
                $counter++;
            } catch (SPException $e) {
                $errorCount++;
                $eventMessage->addDescription(__u('Fallo al actualizar la clave de la cuenta'));
                $eventMessage->addDetail($account->name, $account->id);
            }
        }

        $eventMessage->addDetail(__u('Cuentas actualizadas'), implode(',', $accountsOk));
        $eventMessage->addDetail(__u('Errores'), $errorCount);

        $this->eventDispatcher->notifyEvent('update.masterPassword.accounts.end', new Event($this, $eventMessage));
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @param UpdateMasterPassRequest $updateMasterPassRequest
     * @throws ServiceException
     */
    public function updateMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest)
    {
        $this->request = $updateMasterPassRequest;

        try {
            $this->eventDispatcher->notifyEvent('update.masterPassword.accounts.start',
                new Event($this, EventMessage::factory()->addDescription(__u('Actualizar Clave Maestra')))
            );

            if ($this->request->useTask()) {
                $taskId = $this->request->getTask();

                TaskFactory::update($taskId, TaskFactory::createMessage($taskId, __u('Actualizar Clave Maestra')));
            }

            $eventMessage = $this->processAccounts($this->accountService->getAccountsPassData(), function ($request) {
                $this->accountService->updatePasswordMasterPass($request);
            });

            $this->eventDispatcher->notifyEvent('update.masterPassword.accounts.end', new Event($this, $eventMessage));
        } catch (\Exception $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new ServiceException(
                __u('Errores al actualizar las claves de las cuentas'),
                ServiceException::ERROR,
                null,
                $e->getCode(),
                $e);
        }
    }

    /**
     * @param array    $accounts
     * @param callable $passUpdater
     * @return EventMessage
     * @throws ServiceException
     */
    protected function processAccounts(array $accounts, callable $passUpdater)
    {
        set_time_limit(0);

        $accountsOk = [];
        $errorCount = 0;
        $counter = 0;
        $startTime = time();
        $numAccounts = count($accounts);

        if ($numAccounts === 0) {
            throw new ServiceException(__u('Error al obtener las claves de las cuentas'), ServiceException::ERROR);
        }

        $configData = $this->config->getConfigData();
        $currentMasterPassHash = $this->request->getCurrentHash();

        if ($this->request->useTask()) {
            $taskId = $this->request->getTask()->getTaskId();
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

                if (isset($taskId)) {
                    $taskMessage = TaskFactory::createMessage($taskId, __('Actualizar Clave Maestra'))
                        ->setMessage(sprintf(__('Cuentas actualizadas: %d / %d'), $counter, $numAccounts))
                        ->setProgress(round(($counter * 100) / $numAccounts, 2))
                        ->setTime(sprintf('ETA: %ds (%.2f/s)', $eta[0], $eta[1]));

                    TaskFactory::update($taskId, $taskMessage);

                    debugLog($taskMessage->composeText());
                } else {
                    debugLog(
                        sprintf(__('Cuentas actualizadas: %d / %d - %d%% - ETA: %ds (%.2f/s)'),
                            $counter,
                            $numAccounts,
                            round(($counter * 100) / $numAccounts, 2),
                            $eta[0], $eta[1])
                    );
                }
            }

            if (isset($account->mPassHash) && $account->mPassHash !== $currentMasterPassHash) {
                $eventMessage->addDescription(__u('La clave maestra del registro no coincide'));
                $eventMessage->addDetail($account->name, $account->id);
                continue;
            }

            $request = new AccountPasswordRequest();
            $request->id = $account->id;

            try {
                $passData = $this->accountService->getPasswordEncrypted(
                    Crypt::decrypt($account->pass, Crypt::unlockSecuredKey($account->key, $this->request->getCurrentMasterPass())),
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
                $eventMessage->addDescription(__u('Fallo al actualizar la clave de la cuenta'));
                $eventMessage->addDetail($account->name, $account->id);
            } catch (CryptoException $e) {
                $errorCount++;

                $eventMessage->addDescription(__u('Fallo al actualizar la clave de la cuenta'));
                $eventMessage->addDetail($account->name, $account->id);
            }
        }

        $eventMessage->addDetail(__u('Cuentas actualizadas'), implode(',', $accountsOk));
        $eventMessage->addDetail(__u('Errores'), $errorCount);

        return $eventMessage;
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @param UpdateMasterPassRequest $updateMasterPassRequest
     * @throws ServiceException
     */
    public function updateHistoryMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest)
    {
        $this->request = $updateMasterPassRequest;

        try {
            $this->eventDispatcher->notifyEvent('update.masterPassword.accountsHistory.start',
                new Event($this, EventMessage::factory()->addDescription(__u('Actualizar Clave Maestra (H)')))
            );

            $taskId = $this->request->getTask();

            if ($this->request->useTask()) {
                TaskFactory::update($taskId, TaskFactory::createMessage($taskId, __u('Actualizar Clave Maestra (H)')));
            }

            $eventMessage = $this->processAccounts($this->accountHistoryService->getAccountsPassData(), function ($request) {
                /** @var AccountPasswordRequest $request */
                $request->hash = $this->request->getHash();

                $this->accountHistoryService->updatePasswordMasterPass($request);
            });

            $this->eventDispatcher->notifyEvent('update.masterPassword.accountsHistory.end', new Event($this, $eventMessage));
        } catch (\Exception $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new ServiceException(
                __u('Errores al actualizar las claves de las cuentas del histórico'),
                ServiceException::ERROR,
                null,
                $e->getCode(),
                $e);
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->accountService = $this->dic->get(AccountService::class);
        $this->accountHistoryService = $this->dic->get(AccountHistoryService::class);
    }
}