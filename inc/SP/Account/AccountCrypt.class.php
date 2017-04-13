<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Account;

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\QueryException;
use SP\Core\Messages\TaskMessage;
use SP\Core\OldCrypt;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\Core\Task;
use SP\Core\TaskFactory;
use SP\DataModel\AccountData;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Util;

/**
 * Class AccountCrypt
 *
 * @package SP\Account
 */
class AccountCrypt
{
    /**
     * Actualiza las claves de todas las cuentas con la clave maestra actual
     * usando nueva encriptación.
     *
     * @param string $currentMasterPass
     * @return bool
     */
    public function updateOldPass(&$currentMasterPass)
    {
        set_time_limit(0);

        $accountsOk = [];
        $userId = Session::getUserData()->getUserId();
        $demoEnabled = Checks::demoIsEnabled();
        $errorCount = 0;

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Actualizar Clave Maestra', false));
        $LogMessage->addDescription(__('Inicio', false));
        $Log->writeLog(true);

        if (!OldCrypt::checkCryptModule()) {
            $LogMessage->addDescription(__('Error en el módulo de encriptación', false));
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();
            return false;
        }

        $accountsPass = $this->getAccountsPassData();
        $numAccounts = count($accountsPass);

        if ($numAccounts === 0) {
            $LogMessage->addDescription(__('Error al obtener las claves de las cuentas', false));
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();
            return false;
        }

        $AccountDataBase = new AccountData();

        TaskFactory::$Message->setTask(__('Actualizar Clave Maestra'));
        TaskFactory::sendTaskMessage();

        $counter = 0;
        $startTime = time();

        foreach ($accountsPass as $account) {
            // No realizar cambios si está en modo demo
            if ($demoEnabled) {
                $accountsOk[] = $account->account_id;
                continue;
            }

            if ($LogMessage->getDetailsCounter() >= 100) {
                $Log->writeLog(false, true);
            }

            if ($counter % 100 === 0) {
                $eta = Util::getETA($startTime, $counter, $numAccounts);

                TaskFactory::$Message->setMessage(__('Cuentas actualizadas') . ': ' . $counter . '/' . $numAccounts);
                TaskFactory::$Message->setProgress(round(($counter * 100) / $numAccounts, 2));
                TaskFactory::$Message->setTime(sprintf('ETA: %ds (%.2f/s)', $eta[0], $eta[1]));
                TaskFactory::sendTaskMessage();

                debugLog(TaskFactory::$Message->composeText());
            }

            $AccountData = clone $AccountDataBase;

            $AccountData->setAccountId($account->account_id);
            $AccountData->setAccountUserEditId($userId);

//            } elseif (strlen($account->account_key) < 32) {
//                $LogMessage->addDetails(__('IV de encriptación incorrecto', false), sprintf('%s (%d)', $account->account_name, $account->account_id));
//            }

            try {
                $decryptedPass = OldCrypt::getDecrypt($account->account_pass, $account->account_key, $currentMasterPass);

                $securedKey = Crypt::makeSecuredKey($currentMasterPass);

                $AccountData->setAccountPass(Crypt::encrypt($decryptedPass, $securedKey, $currentMasterPass));
                $AccountData->setAccountKey($securedKey);

                if (strlen($securedKey) > 1000 || strlen($AccountData->getAccountPass()) > 1000) {
                    throw new QueryException(SPException::SP_ERROR, __('Error interno', false));
                }

                $Account = new Account($AccountData);
                $Account->updateAccountPass(true);

                $accountsOk[] = $account->account_id;
                $counter++;
            } catch (SPException $e) {
                $errorCount++;
                $LogMessage->addDetails(__('Fallo al actualizar la clave de la cuenta', false), sprintf('%s (%d)', $account->account_name, $account->account_id));
            } catch (CryptoException $e) {
                $errorCount++;
                $LogMessage->addDetails(__('Fallo al actualizar la clave de la cuenta', false), sprintf('%s (%d)', $account->account_name, $account->account_id));
            }
        }

        $LogMessage->addDetails(__('Cuentas actualizadas', false), implode(',', $accountsOk));
        $LogMessage->addDetails(__('Errores', false), $errorCount);
        $Log->writeLog();

        Email::sendEmail($LogMessage);

        return true;
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return array Con los datos de la clave
     */
    protected function getAccountsPassData()
    {
        $query = /** @lang SQL */
            'SELECT account_id, account_name, account_pass, account_key 
            FROM accounts WHERE BIT_LENGTH(account_pass) > 0';

        $Data = new QueryData();
        $Data->setQuery($query);

        return DB::getResultsArray($Data);
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @param string $currentMasterPass
     * @param string $newMasterPass
     * @return bool
     */
    public function updatePass($currentMasterPass, $newMasterPass)
    {
        set_time_limit(0);

        $accountsOk = [];
        $userId = Session::getUserData()->getUserId();
        $demoEnabled = Checks::demoIsEnabled();
        $errorCount = 0;

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Actualizar Clave Maestra', false));
        $LogMessage->addDescription(__('Inicio', false));
        $Log->writeLog(true);

        $accountsPass = $this->getAccountsPassData();
        $numAccounts = count($accountsPass);

        if ($numAccounts === 0) {
            $LogMessage->addDescription(__('Error al obtener las claves de las cuentas', false));
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();
            return false;
        }

        $AccountDataBase = new AccountData();

        TaskFactory::$Message->setTask(__('Actualizar Clave Maestra'));
        TaskFactory::sendTaskMessage();

        $counter = 0;
        $startTime = time();

        foreach ($accountsPass as $account) {
            // No realizar cambios si está en modo demo
            if ($demoEnabled) {
                $accountsOk[] = $account->account_id;
                continue;
            } elseif ($LogMessage->getDetailsCounter() >= 100) {
                $Log->writeLog(false, true);
            }

            if ($counter % 100 === 0) {
                $eta = Util::getETA($startTime, $counter, $numAccounts);

                TaskFactory::$Message->setMessage(__('Cuentas actualizadas') . ': ' . $counter . '/' . $numAccounts);
                TaskFactory::$Message->setProgress(round(($counter * 100) / $numAccounts, 2));
                TaskFactory::$Message->setTime(sprintf('ETA: %ds (%.2f/s)', $eta[0], $eta[1]));
                TaskFactory::sendTaskMessage();

                debugLog(TaskFactory::$Message->composeText());
            }

            $AccountData = clone $AccountDataBase;

            $AccountData->setAccountId($account->account_id);
            $AccountData->setAccountUserEditId($userId);

            try {
                $currentSecuredKey = Crypt::unlockSecuredKey($account->account_key, $currentMasterPass);
                $decryptedPass = Crypt::decrypt($account->account_pass, $currentSecuredKey);

                $newSecuredKey = Crypt::makeSecuredKey($newMasterPass);
                $AccountData->setAccountPass(Crypt::encrypt($decryptedPass, $newSecuredKey, $newMasterPass));
                $AccountData->setAccountKey($newSecuredKey);

                if (strlen($newSecuredKey) > 1000 || strlen($AccountData->getAccountPass()) > 1000) {
                    throw new QueryException(SPException::SP_ERROR, __('Error interno', false));
                }

                $Account = new Account($AccountData);
                $Account->updateAccountPass(true);

                $accountsOk[] = $account->account_id;
                $counter++;
            } catch (SPException $e) {
                $errorCount++;
                $LogMessage->addDetails(__('Fallo al actualizar la clave de la cuenta', false), sprintf('%s (%d)', $account->account_name, $account->account_id));
            } catch (CryptoException $e) {
                $errorCount++;
                $LogMessage->addDetails(__('Fallo al actualizar la clave de la cuenta', false), sprintf('%s (%d)', $account->account_name, $account->account_id));
            }
        }

        $LogMessage->addDetails(__('Cuentas actualizadas', false), implode(',', $accountsOk));
        $LogMessage->addDetails(__('Errores', false), $errorCount);
        $Log->writeLog();

        Email::sendEmail($LogMessage);

        return true;
    }
}