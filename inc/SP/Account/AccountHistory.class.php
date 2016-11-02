<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Account;

use SP\Config\ConfigDB;
use SP\Core\Crypt;
use SP\Storage\DB;
use SP\Log\Log;
use SP\Core\Exceptions\SPException;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class AccountHistory par el manejo del historial de cuentas
 *
 * @package SP
 */
class AccountHistory extends AccountBase implements AccountInterface
{
    private $isDelete = false;
    private $isModify = false;

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @param $accountId
     * @return array|false Con los registros con id como clave y fecha - usuario como valor
     */
    public static function getAccountList($accountId)
    {
        $query = /** @lang SQL */
            'SELECT acchistory_id,'
            . 'acchistory_dateEdit,'
            . 'u1.user_login as user_edit,'
            . 'u2.user_login as user_add,'
            . 'acchistory_dateAdd '
            . 'FROM accHistory '
            . 'LEFT JOIN usrData u1 ON acchistory_userEditId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON acchistory_userId = u2.user_id '
            . 'WHERE acchistory_accountId = :id '
            . 'ORDER BY acchistory_id DESC';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'id');

        DB::setReturnArray();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        $arrHistory = array();

        foreach ($queryRes as $history) {
            // Comprobamos si la entrada en el historial es la primera (no tiene editor ni fecha de edición)
            if ($history->acchistory_dateEdit === null || $history->acchistory_dateEdit == '0000-00-00 00:00:00') {
                $arrHistory[$history->acchistory_id] = $history->acchistory_dateAdd . ' - ' . $history->user_add;
            } else {
                $arrHistory[$history->acchistory_id] = $history->acchistory_dateEdit . ' - ' . $history->user_edit;
            }
        }

        return $arrHistory;
    }

    /**
     * @return boolean
     */
    public function isIsDelete()
    {
        return $this->isDelete;
    }

    /**
     * @param boolean $isDelete
     */
    public function setIsDelete($isDelete)
    {
        $this->isDelete = $isDelete;
    }

    /**
     * @return boolean
     */
    public function isIsModify()
    {
        return $this->isModify;
    }

    /**
     * @param boolean $isModify
     */
    public function setIsModify($isModify)
    {
        $this->isModify = $isModify;
    }

    /**
     * Actualiza las claves de todas las cuentas en el histórico con la nueva clave maestra.
     *
     * @param string $currentMasterPass con la clave maestra actual
     * @param string $newMasterPass     con la nueva clave maestra
     * @param string $newHash           con el nuevo hash de la clave maestra
     * @return bool
     */
    public function updateAccountsMasterPass($currentMasterPass, $newMasterPass, $newHash = null)
    {
        $idOk = array();
        $errorCount = 0;
        $demoEnabled = Checks::demoIsEnabled();

        $Log = new Log(_('Actualizar Clave Maestra (H)'));
        $Log->addDescription(_('Inicio'));
        $Log->writeLog();

        $Log->resetDescription();

        if (!Crypt::checkCryptModule()) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error en el módulo de encriptación'));
            $Log->writeLog();
            return false;
        }

        $accountsPass = $this->getAccountsPassData();

        if (!$accountsPass) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al obtener las claves de las cuentas'));
            $Log->writeLog();
            return false;
        }

        foreach ($accountsPass as $account) {
            $this->setAccountId($account->acchistory_id);

            // No realizar cambios si está en modo demo
            if ($demoEnabled) {
                $idOk[] = $account->acchistory_id;
                continue;
            }

            if (!$this->checkAccountMPass()) {
                $errorCount++;
                $Log->addDescription(_('La clave maestra del registro no coincide') . ' (' . $account->acchistory_id . ') ' .  $account->acchistory_name);
                continue;
            }

            if (strlen($account->acchistory_pass) === 0){
                $Log->addDescription(_('Clave de cuenta vacía') . ' (' . $account->acchistory_id . ') ' . $account->acchistory_name);
                continue;
            }

            if (strlen($account->acchistory_IV) < 32) {
                $Log->addDescription(_('IV de encriptación incorrecto') . ' (' . $account->acchistory_id . ') ' .  $account->acchistory_name);
            }

            $decryptedPass = Crypt::getDecrypt($account->acchistory_pass, $account->acchistory_IV);
            $this->setAccountPass(Crypt::mkEncrypt($decryptedPass, $newMasterPass));
            $this->setAccountIV(Crypt::$strInitialVector);

            if ($this->getAccountPass() === false) {
                $errorCount++;
                $Log->addDescription(_('No es posible desencriptar la clave de la cuenta') . ' (' . $account->acchistory_id . ') ' . $account->acchistory_name);
                continue;
            }

            if (!$this->updateAccountPass($account->acchistory_id, $newHash)) {
                $errorCount++;
                $Log->addDescription(_('Fallo al actualizar la clave del histórico') . ' (' . $account->acchistory_id . ') ' .  $account->acchistory_name);
                continue;
            }

            $idOk[] = $account->acchistory_id;
        }

        // Vaciar el array de mensaje de log
        if (count($Log->getDescription()) > 0) {
            $Log->writeLog();
            $Log->resetDescription();
        }

        if ($idOk) {
            $Log->addDetails(_('Registros actualizados'),implode(',', $idOk));
            $Log->writeLog();
            $Log->resetDescription();
        }

        $Log->addDescription(_('Fin'));
        $Log->writeLog();

        return true;
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas del histórico.
     *
     * @return false|array con los datos de la clave
     */
    protected function getAccountsPassData()
    {
        $query = /** @lang SQL */
            'SELECT acchistory_id, acchistory_name, acchistory_pass, acchistory_IV FROM accHistory';

        $Data = new QueryData();
        $Data->setQuery($query);

        DB::setReturnArray();

        return DB::getResults($Data);
    }

    /**
     * Comprueba el hash de la clave maestra del registro de histórico de una cuenta.
     *
     * @param int $id opcional, con el Id del registro a comprobar
     * @return bool
     */
    public function checkAccountMPass($id = null)
    {
        $query = /** @lang SQL */
            'SELECT acchistory_mPassHash ' .
            'FROM accHistory ' .
            'WHERE acchistory_id = :id ' .
            'AND acchistory_mPassHash = :mPassHash';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam((is_null($id)) ? $this->getAccountId() : $id, 'id');
        $Data->addParam(ConfigDB::getValue('masterPwd'), 'mPassHash');

        return (DB::getResults($Data) !== false);
    }

    /**
     * Obtener los datos de una cuenta para mostrar la clave
     * Esta funcion realiza la consulta a la BBDD y devuelve los datos.
     *
     * @return object|false
     */
    public function getAccountPassData()
    {
        $query = /** @lang SQL */
            'SELECT acchistory_name AS name,'
            . 'acchistory_userId AS userId,'
            . 'acchistory_userGroupId AS groupId,'
            . 'acchistory_login AS login,'
            . 'acchistory_pass AS pass,'
            . 'acchistory_IV AS iv '
            . 'FROM accHistory '
            . 'WHERE acchistory_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountId(), 'id');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        $this->accountData->setAccountUserId($queryRes->userId);
        $this->accountData->setAccountUserGroupId($queryRes->groupId);
        $this->accountData->setAccountPass($queryRes->pass);
        $this->accountData->setAccountIV($queryRes->iv);

        return $queryRes;
    }

    /**
     * Actualiza la clave del histórico de una cuenta en la BBDD.
     *
     * @param int    $id      con el id del registro a actualizar
     * @param string $newHash con el hash de la clave maestra
     * @return bool
     */
    public function updateAccountPass($id, $newHash)
    {
        $query = /** @lang SQL */
            'UPDATE accHistory SET '
            . 'acchistory_pass = :accountPass,'
            . 'acchistory_IV = :accountIV,'
            . 'acchistory_mPassHash = :newHash '
            . 'WHERE acchistory_id = :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');
        $Data->addParam($this->getAccountPass(), 'accountPass');
        $Data->addParam($this->getAccountIV(), 'accountIV');
        $Data->addParam($newHash, 'newHash');

        return DB::getQuery($Data);
    }

    /**
     * Obtener los datos del histórico de una cuenta.
     * Esta funcion realiza la consulta a la BBDD y guarda los datos del histórico
     * en las variables de la clase.
     *
     * @return object
     * @throws SPException
     */
    public function getData()
    {
        $query = /** @lang SQL */
            'SELECT acchistory_accountId as account_id,'
            . 'acchistory_customerId as account_customerId,'
            . 'acchistory_categoryId as account_categoryId,'
            . 'acchistory_name as account_name,'
            . 'acchistory_login as account_login,'
            . 'acchistory_url as account_url,'
            . 'acchistory_pass as account_pass,'
            . 'acchistory_IV as account_IV,'
            . 'acchistory_notes as account_notes,'
            . 'acchistory_countView as account_countView,'
            . 'acchistory_countDecrypt as account_countDecrypt,'
            . 'acchistory_dateAdd as account_dateAdd,'
            . 'acchistory_dateEdit as account_dateEdit,'
            . 'acchistory_userId as account_userId,'
            . 'acchistory_userGroupId as account_userGroupId,'
            . 'acchistory_userEditId as account_userEditId,'
            . 'acchistory_isModify,'
            . 'acchistory_isDeleted,'
            . 'acchistory_otherUserEdit + 0 AS account_otherUserEdit,'
            . 'acchistory_otherGroupEdit + 0 AS account_otherGroupEdit,'
            . 'u1.user_name,'
            . 'u1.user_login,'
            . 'usergroup_name,'
            . 'u2.user_name as user_editName,'
            . 'u2.user_login as user_editLogin,'
            . 'category_name, customer_name '
            . 'FROM accHistory '
            . 'LEFT JOIN categories ON acchistory_categoryId = category_id '
            . 'LEFT JOIN usrGroups ON acchistory_userGroupId = usergroup_id '
            . 'LEFT JOIN usrData u1 ON acchistory_userId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON acchistory_userEditId = u2.user_id '
            . 'LEFT JOIN customers ON acchistory_customerId = customer_id '
            . 'WHERE acchistory_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName('SP\DataModel\AccountExtData');
        $Data->addParam($this->accountData->getAccountId(), 'id');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, _('No se pudieron obtener los datos de la cuenta'));
        }

        $this->accountData = $queryRes;

        return $queryRes;
    }

    /**
     * Crear una cuenta en el historial
     *
     * @return bool
     */
    public function createAccount()
    {
        $query = /** @lang SQL */
            'INSERT INTO accHistory SET '
            . 'acchistory_accountId = :account_id,'
            . 'acchistory_customerId = :accountCustomerId,'
            . 'acchistory_categoryId = :accountCategoryId,'
            . 'acchistory_name = :accountName,'
            . 'acchistory_login = :accountLogin,'
            . 'acchistory_url = :accountUrl,'
            . 'acchistory_pass = :accountPass,'
            . 'acchistory_IV = :accountIV,'
            . 'acchistory_notes = :accountNotes,'
            . 'acchistory_dateAdd = :accountDateAdd,'
            . 'acchistory_dateEdit = :accountDateEdit,'
            . 'acchistory_countView = :accountCountView,'
            . 'acchistory_countDecrypt  = :accountCountDecrypt,'
            . 'acchistory_userId = :accountUserId,'
            . 'acchistory_userGroupId = :accountUserGroupId,'
            . 'acchistory_otherUserEdit = :accountOtherUserEdit,'
            . 'acchistory_otherGroupEdit = :accountOtherGroupEdit,'
            . 'acchistory_isModify = :isModify,'
            . 'acchistory_isDeleted = :isDelete,'
            . 'acchistory_mPassHash = :masterPwd';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountId(), 'account_id');
        $Data->addParam($this->accountData->getAccountCustomerId(), 'accountCustomerId');
        $Data->addParam($this->accountData->getAccountCategoryId(), 'accountCategoryId');
        $Data->addParam($this->accountData->getAccountName(), 'accountName');
        $Data->addParam($this->accountData->getAccountLogin(), 'accountLogin');
        $Data->addParam($this->accountData->getAccountUrl(), 'accountUrl');
        $Data->addParam($this->accountData->getAccountPass(), 'accountPass');
        $Data->addParam($this->accountData->getAccountIV(), 'accountIV');
        $Data->addParam($this->accountData->getAccountNotes(), 'accountNotes');
        $Data->addParam($this->accountData->getAccountUserId(), 'accountUserId');
        $Data->addParam($this->accountData->getAccountUserGroupId(), 'accountUserGroupId');
        $Data->addParam($this->accountData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
        $Data->addParam($this->accountData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        $Data->addParam($this->isIsModify(), 'isModify');
        $Data->addParam($this->isIsDelete(), 'isDelete');
        $Data->addParam(ConfigDB::getValue('masterPwd'), 'masterPwd');

        if (DB::getQuery($Data) === false) {
            return false;
        }

        return true;
    }

    /**
     * Eliminar una cuenta del historial
     *
     * @return bool
     */
    public function deleteAccount()
    {
        $query = /** @lang SQL */
            'DELETE FROM accHistory WHERE acchistory_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountId(), 'id');

        if (DB::getQuery($Data) === false) {
            return false;
        }

        return true;
    }

    /**
     * Crear un nuevo registro de histório de cuenta en la BBDD.
     *
     * @param int  $id       el id de la cuenta primaria
     * @param bool $isDelete indica que la cuenta es eliminada
     * @return bool
     */
    public static function addHistory($id, $isDelete = false)
    {
        $query = /** @lang SQL */
            'INSERT INTO accHistory '
            . '(acchistory_accountId,'
            . 'acchistory_categoryId,'
            . 'acchistory_customerId,'
            . 'acchistory_name,'
            . 'acchistory_login,'
            . 'acchistory_url,'
            . 'acchistory_pass,'
            . 'acchistory_IV,'
            . 'acchistory_notes,'
            . 'acchistory_countView,'
            . 'acchistory_countDecrypt,'
            . 'acchistory_dateAdd,'
            . 'acchistory_dateEdit,'
            . 'acchistory_userId,'
            . 'acchistory_userGroupId,'
            . 'acchistory_userEditId,'
            . 'acchistory_otherUserEdit,'
            . 'acchistory_otherGroupEdit,'
            . 'acchistory_isModify,'
            . 'acchistory_isDeleted,'
            . 'acchistory_mPassHash) '
            . 'SELECT account_id,'
            . 'account_categoryId,'
            . 'account_customerId,'
            . 'account_name,'
            . 'account_login,'
            . 'account_url,'
            . 'account_pass,'
            . 'account_IV,'
            . 'account_notes,'
            . 'account_countView,'
            . 'account_countDecrypt,'
            . 'account_dateAdd,'
            . 'account_dateEdit,'
            . 'account_userId,'
            . 'account_userGroupId,'
            . 'account_userEditId,'
            . 'account_otherUserEdit,'
            . 'account_otherGroupEdit,'
            . ':isModify,'
            . ':isDelete,'
            . ':masterPwd '
            . 'FROM accounts WHERE account_id = :account_id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'account_id');
        $Data->addParam(($isDelete === false) ? 1 : 0, 'isModify');
        $Data->addParam(($isDelete === true) ? 1 : 1, 'isDelete');
        $Data->addParam(ConfigDB::getValue('masterPwd'), 'masterPwd');

        return DB::getQuery($Data);
    }

    /**
     * Obtener el Id padre de una cuenta en el histórico.
     *
     * @param $historyId int El id de la cuenta en el histórico
     * @return int El id de la cuenta padre
     * @throws SPException
     */
    public static function getAccountIdFromId($historyId)
    {
        $query = /** @lang SQL */
            'SELECT acchistory_accountId FROM accHistory WHERE acchistory_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($historyId, 'id');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, _('No se pudieron obtener los datos de la cuenta'), 0);
        }

        return $queryRes->acchistory_accountId;
    }

    /**
     * Actualiza el hash de las cuentas en el histórico.
     *
     * @param $newHash string El nuevo hash de la clave maestra
     * @return bool
     */
    public static function updateAccountsMPassHash($newHash)
    {
        $query = /** @lang SQL */
            'UPDATE accHistory SET '
            . 'acchistory_mPassHash = :newHash '
            . 'WHERE acchistory_mPassHash = :oldHash';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($newHash, 'newHash');
        $Data->addParam(ConfigDB::getValue('masterPwd'), 'oldHash');

        return DB::getQuery($Data);
    }
}