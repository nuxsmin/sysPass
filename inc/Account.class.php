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

/**
 * Esta clase es la encargada de realizar las operaciones sobre las cuentas de sysPass.
 */
class Account extends AccountBase implements AccountInterface
{
    /**
     * @var array Variable para la caché de parámetros de una cuenta.
     */
    private $_cacheParams;

    /**
     * Obtener los datos de usuario y modificador de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|object con el id de usuario y modificador.
     */
    public static function getAccountRequestData($accountId)
    {
        $query = 'SELECT account_userId,'
            . 'account_userEditId,'
            . 'account_name,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

        $data['id'] = $accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes;
    }

    /**
     * Obtiene el listado con el nombre de los usuaios de una cuenta.
     *
     * @param int $accountId con el Id de la cuenta
     * @return false|array con los nombres de los usuarios ordenados
     */
    public static function getAccountUsersName($accountId)
    {
        $query = 'SELECT user_name '
            . 'FROM accUsers '
            . 'JOIN usrData ON accuser_userId = user_id '
            . 'WHERE accuser_accountId = :id';

        $data['id'] = $accountId;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        if (!is_array($queryRes)) {
            return false;
        }

        foreach ($queryRes as $users) {
            $usersName[] = $users->user_name;
        }

        sort($usersName, SORT_STRING);

        return $usersName;
    }

    /**
     * Actualiza los datos de una cuenta en la BBDD.
     *
     * @return bool
     */
    public function updateAccount()
    {
        $Log = new Log(__FUNCTION__);

        // Guardamos una copia de la cuenta en el histórico
        if (!AccountHistory::addHistory($this->getAccountId(), false)) {
            $Log->addDescription(_('Error al actualizar el historial'));
            $Log->writeLog();
            return false;
        }

        $Log->setAction(_('Actualizar Cuenta'));

        if (!Groups::updateGroupsForAccount($this->getAccountId(), $this->getAccountUserGroupsId())) {
            $Log->addDescription(_('Error al actualizar los grupos secundarios'));
            $Log->writeLog();
            $Log->resetDescription();
        }

        if (!UserUtil::updateUsersForAccount($this->getAccountId(), $this->getAccountUsersId())) {
            $Log->addDescription(_('Error al actualizar los usuarios de la cuenta'));
            $Log->writeLog();
            $Log->resetDescription();
        }

        $query = 'UPDATE accounts SET '
            . 'account_customerId = :accountCustomerId,'
            . 'account_categoryId = :accountCategoryId,'
            . 'account_name = :accountName,'
            . 'account_login = :accountLogin,'
            . 'account_url = :accountUrl,'
            . 'account_notes = :accountNotes,'
            . 'account_userEditId = :accountUserEditId,'
            . 'account_userGroupId = :accountUserGroupId,'
            . 'account_dateEdit = NOW(),'
            . 'account_otherUserEdit = :accountOtherUserEdit,'
            . 'account_otherGroupEdit = :accountOtherGroupEdit '
            . 'WHERE account_id = :accountId';

        $data['accountCustomerId'] = $this->getAccountCustomerId();
        $data['accountCategoryId'] = $this->getAccountCategoryId();
        $data['accountName'] = $this->getAccountName();
        $data['accountLogin'] = $this->getAccountLogin();
        $data['accountUrl'] = $this->getAccountUrl();
        $data['accountNotes'] = $this->getAccountNotes();
        $data['accountUserEditId'] = $this->getAccountUserEditId();
        $data['accountUserGroupId'] = ($this->getAccountUserGroupId()) ? $this->getAccountUserGroupId() : 'account_userGroupId';
        $data['accountOtherUserEdit'] = intval($this->getAccountOtherUserEdit());
        $data['accountOtherGroupEdit'] = intval($this->getAccountOtherGroupEdit());
        $data['accountId'] = $this->getAccountId();

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $accountInfo = array('customer_name');
        $this->getAccountInfoById($accountInfo);

        $Log->addDescription(Html::strongText(_('Cliente') . ': ') . $this->_cacheParams['customer_name']);
        $Log->addDescription(Html::strongText(_('Cuenta') . ': ') . $this->getAccountName() . " (" . $this->getAccountId() . ")");
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param $id int El Id del registro en el histórico
     * @return bool
     */
    public function restoreFromHistory($id)
    {
        $Log = new Log(__FUNCTION__);

        // Guardamos una copia de la cuenta en el histórico
        if (!AccountHistory::addHistory($this->getAccountId(), false)) {
            $Log->addDescription(_('Error al actualizar el historial'));
            $Log->writeLog();
            return false;
        }

        $query = 'UPDATE accounts dst, '
            . '(SELECT * FROM accHistory WHERE acchistory_id = :id) src SET '
            . 'dst.account_customerId = src.acchistory_customerId,'
            . 'dst.account_categoryId = src.acchistory_categoryId,'
            . 'dst.account_name = src.acchistory_name,'
            . 'dst.account_login = src.acchistory_login,'
            . 'dst.account_url = src.acchistory_url,'
            . 'dst.account_notes = src.acchistory_notes,'
            . 'dst.account_userEditId = :accountUserEditId,'
            . 'dst.account_dateEdit = NOW(),'
            . 'dst.account_otherUserEdit = src.acchistory_otherUserEdit + 0,'
            . 'dst.account_otherGroupEdit = src.acchistory_otherGroupEdit + 0,'
            . 'dst.account_pass = src.acchistory_pass,'
            . 'dst.account_IV = src.acchistory_IV '
            . 'WHERE dst.account_id = :accountId';

        $data['id'] = $id;
        $data['accountId'] = $this->getAccountId();
        $data['accountUserEditId'] = $this->getAccountUserEditId();

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $accountInfo = array('customer_name', 'account_name');
        $this->getAccountInfoById($accountInfo);

        $Log->setAction(_('Restaurar Cuenta'));
        $Log->addDescription(Html::strongText(_('Cliente') . ': ') . $this->_cacheParams['customer_name']);
        $Log->addDescription(Html::strongText(_('Cuenta') . ': ') . $this->_cacheParams['account_name'] . " (" . $this->getAccountId() . ")");

        $Log->writeLog();
        Email::sendEmail($Log);

        return true;
    }

    /**
     * Obtener los datos de una cuenta con el id.
     * Se guardan los datos en la variable $cacheParams de la clase para consultarlos
     * posteriormente.
     *
     * @param array $params con los campos de la BBDD a obtener
     * @return bool
     */
    private function getAccountInfoById($params)
    {
        if (!is_array($params)) {
            return false;
        }

        if (is_array($this->_cacheParams)) {
            $cache = true;

            foreach ($params as $param) {
                if (!array_key_exists($param, $this->_cacheParams)) {
                    $cache = false;
                }
            }

            if ($cache) {
                return true;
            }
        }

        $query = 'SELECT ' . implode(',', $params) . ' '
            . 'FROM accounts '
            . 'LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id '
            . 'LEFT JOIN usrData u1 ON account_userId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON account_userEditId = u2.user_id '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->getAccountId();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $param => $value) {
            $this->_cacheParams[$param] = $value;
        }

        return true;
    }

    /**
     * Obtener los datos de una cuenta.
     * Esta funcion realiza la consulta a la BBDD y guarda los datos en las variables de la clase.
     *
     * @return object
     * @throws SPException
     */
    public function getAccountData()
    {
        $query = 'SELECT account_id,'
            . 'account_name,'
            . 'account_categoryId,'
            . 'account_userId,'
            . 'account_customerId,'
            . 'account_userGroupId,'
            . 'account_userEditId,'
            . 'category_name,'
            . 'account_login,'
            . 'account_url,'
//            . 'account_pass,'
//            . 'account_IV,'
            . 'account_notes,'
            . 'account_countView,'
            . 'account_countDecrypt,'
            . 'account_dateAdd,'
            . 'account_dateEdit,'
            . 'BIN(account_otherUserEdit) AS account_otherUserEdit,'
            . 'BIN(account_otherGroupEdit) AS account_otherGroupEdit,'
            . 'u1.user_name,'
            . 'u1.user_login,'
            . 'u2.user_name as user_editName,'
            . 'u2.user_login as user_editLogin,'
            . 'usergroup_name,'
            . 'customer_name, '
            . 'CONCAT(account_name,account_categoryId,account_customerId,account_login,account_url,account_notes,BIN(account_otherUserEdit),BIN(account_otherGroupEdit)) as modHash '
            . 'FROM accounts '
            . 'LEFT JOIN categories ON account_categoryId = category_id '
            . 'LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id '
            . 'LEFT JOIN usrData u1 ON account_userId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON account_userEditId = u2.user_id '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->getAccountId();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, _('No se pudieron obtener los datos de la cuenta'));
        }

        $this->setAccountUserId($queryRes->account_userId);
        $this->setAccountUserGroupId($queryRes->account_userGroupId);
        $this->setAccountOtherUserEdit($queryRes->account_otherUserEdit);
        $this->setAccountOtherGroupEdit($queryRes->account_otherGroupEdit);
        $this->setAccountModHash($queryRes->modHash);

        return $queryRes;
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @return bool
     */
    public function createAccount()
    {
        $query = 'INSERT INTO accounts SET '
            . 'account_customerId = :accountCustomerId,'
            . 'account_categoryId = :accountCategoryId,'
            . 'account_name = :accountName,'
            . 'account_login = :accountLogin,'
            . 'account_url = :accountUrl,'
            . 'account_pass = :accountPass,'
            . 'account_IV = :accountIV,'
            . 'account_notes = :accountNotes,'
            . 'account_dateAdd = NOW(),'
            . 'account_userId = :accountUserId,'
            . 'account_userGroupId = :accountUserGroupId,'
            . 'account_otherUserEdit = :accountOtherUserEdit,'
            . 'account_otherGroupEdit = :accountOtherGroupEdit';

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

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->setAccountId(DB::$lastId);

        $Log = new Log(__FUNCTION__);

        if (is_array($this->getAccountUserGroupsId())) {
            if (!Groups::addGroupsForAccount($this->getAccountId(), $this->getAccountUserGroupsId())) {
                $Log->addDescription(_('Error al actualizar los grupos secundarios'));
                $Log->writeLog();
                $Log->resetDescription();
            }
        }

        if (is_array($this->getAccountUsersId())) {
            if (!UserUtil::addUsersForAccount($this->getAccountId(), $this->getAccountUsersId())) {
                $Log->addDescription(_('Error al actualizar los usuarios de la cuenta'));
                $Log->writeLog();
                $Log->resetDescription();
            }
        }

        $accountInfo = array('customer_name');
        $this->getAccountInfoById($accountInfo);

        $Log->setAction(_('Nueva Cuenta'));
        $Log->addDescription(Html::strongText(_('Cliente') . ': ') . $this->_cacheParams['customer_name']);
        $Log->addDescription(Html::strongText(_('Cuenta') . ': ') . $this->getAccountName() . " (" . $this->getAccountId() . ")");
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @return bool
     */
    public function deleteAccount()
    {
        // Guardamos una copia de la cuenta en el histórico
        AccountHistory::addHistory($this->getAccountId(), true) || die (_('ERROR: Error en la operación.'));

        $accountInfo = array('account_name,customer_name');
        $this->getAccountInfoById($accountInfo);

        $Log = new Log(_('Eliminar Cuenta'));
        $Log->addDescription(Html::strongText(_('Cliente') . ': ') . $this->_cacheParams['customer_name']);
        $Log->addDescription(Html::strongText(_('Cuenta') . ': ') . $this->_cacheParams['account_name'] . " (" . $this->getAccountId() . ")");

        $query = 'DELETE FROM accounts WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->getAccountId();

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        if (!Groups::deleteGroupsForAccount($this->getAccountId())) {
            $Log->addDescription(_('Error al eliminar grupos asociados a la cuenta'));
        }

        if (!UserUtil::deleteUsersForAccount($this->getAccountId())) {
            $Log->addDescription(_('Error al eliminar usuarios asociados a la cuenta'));
        }

        if (!Files::deleteAccountFiles($this->getAccountId())) {
            $Log->addDescription(_('Error al eliminar archivos asociados a la cuenta'));
        }

        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Incrementa el contador de visitas de una cuenta en la BBDD
     *
     * @return bool
     */
    public function incrementViewCounter()
    {
        $query = 'UPDATE accounts SET account_countView = (account_countView + 1) WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->getAccountId();

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @return bool
     */
    public function incrementDecryptCounter()
    {
        $query = 'UPDATE accounts SET account_countDecrypt = (account_countDecrypt + 1) WHERE account_id = :id LIMIT 1';

        $data['id'] = $this->getAccountId();

        return DB::getQuery($query, __FUNCTION__, $data);
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @param string $currentMasterPass con la clave maestra actual
     * @param string $newMasterPass     con la nueva clave maestra
     * @param string $newHash           con el nuevo hash de la clave maestra
     * @return bool
     */
    public function updateAccountsMasterPass($currentMasterPass, $newMasterPass, $newHash = null)
    {
        $accountsOk = array();
        $userId = Session::getUserId();
        $demoEnabled = Util::demoIsEnabled();
        $errorCount = 0;

        $Log = new Log(_('Actualizar Clave Maestra'));
        $Log->addDescription(_('Inicio'));
        $Log->writeLog();
        $Log->resetDescription();

        if (!Crypt::checkCryptModule()) {
            $Log->addDescription(_('Error en el módulo de encriptación'));
            $Log->writeLog();
            return false;
        }

        $accountsPass = $this->getAccountsPassData();

        if (!$accountsPass) {
            $Log->addDescription(_('Error al obtener las claves de las cuentas'));
            $Log->writeLog();
            return false;
        }

        foreach ($accountsPass as $account) {
            $this->setAccountId($account->account_id);
            $this->setAccountUserEditId($userId);

            // No realizar cambios si está en modo demo
            if ($demoEnabled) {
                $accountsOk[] = $this->getAccountId();
                continue;
            }

            if (strlen($account->account_pass) === 0){
                $Log->addDescription(_('Clave de cuenta vacía') . ' (' . $account->account_id . ') ' . $account->account_name);
                continue;
            }

            if (strlen($account->account_IV) < 32) {
                $Log->addDescription(_('IV de encriptación incorrecto') . ' (' . $account->account_id . ') ' . $account->account_name);
            }

            $decryptedPass = Crypt::getDecrypt($account->account_pass, $account->account_IV);
            $this->setAccountPass(Crypt::mkEncrypt($decryptedPass, $newMasterPass));
            $this->setAccountIV(Crypt::$strInitialVector);

            if ($this->getAccountPass() === false) {
                $errorCount++;
                $Log->addDescription(_('No es posible desencriptar la clave de la cuenta') . ' (' . $account->account_id . ') ' . $account->account_name);
                continue;
            }

            if (!$this->updateAccountPass(true)) {
                $errorCount++;
                $Log->addDescription(_('Fallo al actualizar la clave de la cuenta') . ' (' . $this->getAccountId() . ') ' .  $account->acchistory_name);
                continue;
            }

            $accountsOk[] = $this->getAccountId();
        }

        // Vaciar el array de mensajes de log
        if (count($Log->getDescription()) > 0) {
            $Log->writeLog();
            $Log->resetDescription();
        }

        if ($accountsOk) {
            $Log->addDescription(_('Cuentas actualizadas') . ': ' . implode(',', $accountsOk));
            $Log->writeLog();
            $Log->resetDescription();
        }

        $Log->addDescription(_('Fin'));
        $Log->writeLog();

        Email::sendEmail($Log);

        return true;
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return false|array Con los datos de la clave
     */
    protected function getAccountsPassData()
    {
        $query = 'SELECT account_id, account_name, account_pass, account_IV FROM accounts';

        return DB::getResults($query, __FUNCTION__);
    }

    /**
     * Obtener los datos de una cuenta para mostrar la clave
     * Esta funcion realiza la consulta a la BBDD y devuelve los datos.
     *
     * @return object|false
     */
    public function getAccountPassData()
    {
        $query = 'SELECT account_name AS name,'
            . 'account_userId AS userId,'
            . 'account_userGroupId AS groupId,'
            . 'account_login AS login,'
            . 'account_pass AS pass,'
            . 'account_IV AS iv,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

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
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param bool $isMassive para no actualizar el histórico ni enviar mensajes
     * @param bool $isRestore indica si es una restauración
     * @return bool
     */
    public function updateAccountPass($isMassive = false, $isRestore = false)
    {
        $Log = new Log(__FUNCTION__);

        // No actualizar el histórico si es por cambio de clave maestra o restauración
        if (!$isMassive && !$isRestore) {
            // Guardamos una copia de la cuenta en el histórico
            if (!AccountHistory::addHistory($this->getAccountId(), false)) {
                $Log->addDescription(_('Error al actualizar el historial'));
                $Log->writeLog();
                return false;
            }
        }

        $query = 'UPDATE accounts SET '
            . 'account_pass = :accountPass,'
            . 'account_IV = :accountIV,'
            . 'account_userEditId = :accountUserEditId,'
            . 'account_dateEdit = NOW() '
            . 'WHERE account_id = :accountId';

        $data['accountPass'] = $this->getAccountPass();
        $data['accountIV'] = $this->getAccountIV();
        $data['accountUserEditId'] = $this->getAccountUserEditId();
        $data['accountId'] = $this->getAccountId();


        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        // No escribir en el log ni enviar correos si la actualización es
        // por cambio de clave maestra o restauración
        if (!$isMassive && !$isRestore) {
            $accountInfo = array('customer_name', 'account_name');
            $this->getAccountInfoById($accountInfo);

            $Log->setAction(_('Modificar Clave'));
            $Log->addDescription(Html::strongText(_('Cliente') . ': ') . $this->_cacheParams['customer_name']);
            $Log->addDescription(Html::strongText(_('Cuenta') . ': ') . $this->_cacheParams['account_name'] . " (" . $this->getAccountId() . ")");
            $Log->writeLog();

            Email::sendEmail($Log);
        }

        return true;
    }

    /**
     * Obtener los datos de todas las cuentas
     *
     * @return array
     * @throws SPException
     */
    public static function getAccountsData()
    {
        $query = 'SELECT account_id,'
            . 'account_name,'
            . 'account_categoryId,'
            . 'account_customerId,'
            . 'account_login,'
            . 'account_url,'
            . 'account_pass,'
            . 'account_IV,'
            . 'account_notes '
            . 'FROM accounts';

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, _('No se pudieron obtener los datos de las cuentas'));
        }

        return $queryRes;
    }
}