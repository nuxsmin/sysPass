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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

class AccountHistory extends AccountBase implements AccountInterface
{
    private $_isDelete = false;
    private $_isModify = false;

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @return false|array Con los registros con id como clave y fecha - usuario como valor
     */
    public static function getAccountList($accountId)
    {
        $query = 'SELECT acchistory_id,'
            . 'acchistory_dateEdit,'
            . 'u1.user_login as user_edit,'
            . 'u2.user_login as user_add,'
            . 'acchistory_dateAdd '
            . 'FROM accHistory '
            . 'LEFT JOIN usrData u1 ON acchistory_userEditId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON acchistory_userId = u2.user_id '
            . 'WHERE acchistory_accountId = :id '
            . 'ORDER BY acchistory_id DESC';

        $data['id'] = $accountId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

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
        return $this->_isDelete;
    }

    /**
     * @param boolean $isDelete
     */
    public function setIsDelete($isDelete)
    {
        $this->_isDelete = $isDelete;
    }

    /**
     * @return boolean
     */
    public function isIsModify()
    {
        return $this->_isModify;
    }

    /**
     * @param boolean $isModify
     */
    public function setIsModify($isModify)
    {
        $this->_isModify = $isModify;
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
        $demoEnabled = Util::demoIsEnabled();

        $log = new Log(_('Actualizar Clave Maestra (H)'));
        $log->addDescription(_('Inicio'));
        $log->writeLog();

        $log->resetDescription();

        if (!Crypt::checkCryptModule()) {
            $log->addDescription(_('Error en el módulo de encriptación'));
            $log->writeLog();
            return false;
        }

        $accountsPass = $this->getAccountsPassData();

        if (!$accountsPass) {
            $log->addDescription(_('Error al obtener las claves de las cuentas'));
            $log->writeLog();

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
                $log->addDescription(_('La clave maestra del registro no coincide') . ' (' . $account->acchistory_id . ')');
                continue;
            }

            if (strlen($account->acchistory_IV) < 32) {
                $log->addDescription(_('IV de encriptación incorrecto') . ' (' . $account->acchistory_id . ')');
                continue;
            }

            $decryptedPass = Crypt::getDecrypt($account->acchistory_pass, $currentMasterPass, $account->acchistory_IV);
            $this->setAccountPass(Crypt::mkEncrypt($decryptedPass, $newMasterPass));
            $this->setAccountIV(Crypt::$strInitialVector);

            if ($this->getAccountPass() === false) {
                $errorCount++;
                continue;
            }

            if (!$this->updateAccountPass($account->acchistory_id, $newHash)) {
                $errorCount++;
                $log->addDescription(_('Fallo al actualizar la clave del histórico') . ' (' . $account->acchistory_id . ')');
                continue;
            }

            $idOk[] = $account->acchistory_id;
        }

        // Vaciar el array de mensaje de log
        if (count($log->getDescription()) > 0) {
            $log->writeLog();
            $log->resetDescription();
        }

        if ($idOk) {
            $log->addDescription(_('Registros actualizados') . ': ' . implode(',', $idOk));
            $log->writeLog();
            $log->resetDescription();
        }

        $log->addDescription(_('Fin'));
        $log->writeLog();

        if ($errorCount > 0) {
            return false;
        }

        return true;
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas del histórico.
     *
     * @return false|array con los datos de la clave
     */
    protected function getAccountsPassData()
    {
        $query = 'SELECT acchistory_id, acchistory_pass, acchistory_IV FROM accHistory';

        DB::setReturnArray();

        return DB::getResults($query, __FUNCTION__);
    }

    /**
     * Comprueba el hash de la clave maestra del registro de histórico de una cuenta.
     *
     * @param int $id opcional, con el Id del registro a comprobar
     * @return bool
     */
    public function checkAccountMPass($id = null)
    {
        $query = 'SELECT acchistory_mPassHash ' .
            'FROM accHistory ' .
            'WHERE acchistory_id = :id ' .
            'AND acchistory_mPassHash = :mPassHash';

        $data['id'] = (is_null($id)) ? $this->getAccountId() : $id;
        $data['mPassHash'] = Config::getConfigDbValue('masterPwd');

        return (DB::getResults($query, __FUNCTION__, $data) !== false);
    }

    /**
     * Obtener los datos de una cuenta para mostrar la clave
     * Esta funcion realiza la consulta a la BBDD y devuelve los datos.
     *
     * @return object|false
     */
    public function getAccountPassData()
    {
        $query = 'SELECT acchistory_name AS name,'
            . 'acchistory_userId AS userId,'
            . 'acchistory_userGroupId AS groupId,'
            . 'acchistory_login AS login,'
            . 'acchistory_pass AS pass,'
            . 'acchistory_IV AS iv '
            . 'FROM accHistory '
            . 'WHERE acchistory_id = :id LIMIT 1';

        $data['id'] = $this->getAccountId();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        $this->setAccountUserId($queryRes->userId);
        $this->setAccountUserGroupId($queryRes->groupId);
        $this->setAccountPass($queryRes->pass);
        $this->setAccountIV($queryRes->iv);

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
        $query = 'UPDATE accHistory SET '
            . 'acchistory_pass = :accountPass,'
            . 'acchistory_IV = :accountIV,'
            . 'acchistory_mPassHash = :newHash '
            . 'WHERE acchistory_id = :id';

        $data['accountPass'] = $this->getAccountPass();
        $data['accountIV'] = $this->getAccountIV();
        $data['newHash'] = $newHash;
        $data['id'] = $id;

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Obtener los datos del histórico de una cuenta.
     * Esta funcion realiza la consulta a la BBDD y guarda los datos del histórico
     * en las variables de la clase.
     *
     * @return object
     * @throws Exception
     */
    public function getAccountData()
    {
        $query = 'SELECT acchistory_accountId as account_id,'
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

        $data['id'] = $this->getAccountId();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            throw new \Exception(_('No se pudieron obtener los datos de la cuenta'));
        }

        $this->setAccountUserId($queryRes->account_userId);
        $this->setAccountUserGroupId($queryRes->account_userGroupId);
        $this->setAccountOtherUserEdit($queryRes->account_otherUserEdit);
        $this->setAccountOtherGroupEdit($queryRes->account_otherGroupEdit);

        return $queryRes;
    }

    /**
     * Crear una cuenta en el historial
     *
     * @return bool
     */
    public function createAccount()
    {
        // FIXME: continuar

        $query = 'INSERT INTO accHistory SET '
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

        $data['account_id'] = $this->getAccountId();
        $data['accountCustomerId'] = $this->getAccountCustomerId();
        $data['accountCategoryId'] = $this->getAccountCategoryId();
        $data['accountName'] = $this->getAccountName();
        $data['accountLogin'] = $this->getAccountLogin();
        $data['accountUrl'] = $this->getAccountUrl();
        $data['accountPass'] = $this->getAccountPass();
        $data['accountIV'] = $this->getAccountIV();
        $data['accountNotes'] = $this->getAccountNotes();
        $data['accountUserId'] = $this->getAccountUserId();
        $data['accountUserGroupId'] = $this->getAccountUserGroupId();
        $data['accountOtherUserEdit'] = $this->getAccountOtherUserEdit();
        $data['accountOtherGroupEdit'] = $this->getAccountOtherGroupEdit();
        $data['isModify'] = $this->isIsModify();
        $data['isDelete'] = $this->isIsDelete();
        $data['masterPwd'] = Config::getConfigDbValue('masterPwd');

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
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
        $query = 'DELETE FROM accHistory WHERE acchistory_id = :id LIMIT 1';

        $data['id'] = $this->getAccountId();

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
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
        $query = 'INSERT INTO accHistory '
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

        $data['account_id'] = $id;
        $data['isModify'] = ($isDelete === false) ? 1 : 0;
        $data['isDelete'] = ($isDelete === false) ? 0 : 1;
        $data['masterPwd'] = Config::getConfigDbValue('masterPwd');

        return DB::getQuery($query, __FUNCTION__, $data);
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
        $query = 'SELECT acchistory_accountId FROM accHistory WHERE acchistory_id = :id LIMIT 1';

        $data['id'] = $historyId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, _('No se pudieron obtener los datos de la cuenta'), 0);
        }

        return $queryRes->acchistory_accountId;
    }
}