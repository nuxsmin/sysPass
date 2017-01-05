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

use SP\Core\ActionsInterface;
use SP\Core\Crypt;
use SP\DataModel\AccountExtData;
use SP\DataModel\CustomFieldData;
use SP\DataModel\GroupAccountsData;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\Files\FileUtil;
use SP\Mgmt\Groups\GroupAccounts;
use SP\Mgmt\Groups\GroupAccountsUtil;
use SP\Storage\DB;
use SP\Log\Email;
use SP\Html\Html;
use SP\Log\Log;
use SP\Core\Session;
use SP\Core\Exceptions\SPException;
use SP\Storage\QueryData;
use SP\Util\Checks;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones sobre las cuentas de sysPass.
 */
class Account extends AccountBase implements AccountInterface
{
    /**
     * @var array Variable para la caché de parámetros de una cuenta.
     */
    private $cacheParams;

    /**
     * Actualiza los datos de una cuenta en la BBDD.
     *
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateAccount()
    {
        $Log = new Log(_('Actualizar Cuenta'));

        // Guardamos una copia de la cuenta en el histórico
        if (!AccountHistory::addHistory($this->accountData->getAccountId(), false)) {
            $Log->addDescription(_('Error al actualizar el historial'));
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al modificar la cuenta'));
        }

        $GroupAccountsData = new GroupAccountsData();
        $GroupAccountsData->setAccgroupAccountId($this->accountData->getAccountId());
        $GroupAccountsData->setGroups($this->accountData->getUserGroupsId());

        try {
            GroupAccounts::getItem($GroupAccountsData)->update();
        } catch (SPException $e) {
            $Log->addDescription($e->getMessage());
            $Log->writeLog();
            $Log->resetDescription();
        }

        if (!UserAccounts::updateUsersForAccount($this->accountData->getAccountId(), $this->accountData->getUsersId())) {
            $Log->addDescription(_('Error al actualizar los usuarios de la cuenta'));
            $Log->writeLog();
            $Log->resetDescription();
        }

        if (is_array($this->accountData->getTags())) {
            $AccountTags = new AccountTags();
            $AccountTags->addTags($this->accountData);
        }

        $Data = new QueryData();

        if ($this->accountData->getAccountUserGroupId()) {
            $query = /** @lang SQL */
                'UPDATE accounts SET '
                . 'account_customerId = :accountCustomerId,'
                . 'account_categoryId = :accountCategoryId,'
                . 'account_name = :accountName,'
                . 'account_login = :accountLogin,'
                . 'account_url = :accountUrl,'
                . 'account_notes = :accountNotes,'
                . 'account_userEditId = :accountUserEditId,'
                . 'account_userGroupId = :accountUserGroupId,'
                . 'account_dateEdit = NOW(),'
                . 'account_passDateChange = :accountPassDateChange,'
                . 'account_otherUserEdit = :accountOtherUserEdit,'
                . 'account_otherGroupEdit = :accountOtherGroupEdit, '
                . 'account_isPrivate = :accountIsPrivate, '
                . 'account_parentId = :accountParentId '
                . 'WHERE account_id = :accountId';

            $Data->addParam($this->accountData->getAccountUserGroupId(), 'accountUserGroupId');
        } else {
            $query = /** @lang SQL */
                'UPDATE accounts SET '
                . 'account_customerId = :accountCustomerId,'
                . 'account_categoryId = :accountCategoryId,'
                . 'account_name = :accountName,'
                . 'account_login = :accountLogin,'
                . 'account_url = :accountUrl,'
                . 'account_notes = :accountNotes,'
                . 'account_userEditId = :accountUserEditId,'
                . 'account_dateEdit = NOW(),'
                . 'account_passDateChange = :accountPassDateChange,'
                . 'account_otherUserEdit = :accountOtherUserEdit,'
                . 'account_otherGroupEdit = :accountOtherGroupEdit, '
                . 'account_isPrivate = :accountIsPrivate, '
                . 'account_parentId = :accountParentId '
                . 'WHERE account_id = :accountId';

        }

        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountCustomerId(), 'accountCustomerId');
        $Data->addParam($this->accountData->getAccountCategoryId(), 'accountCategoryId');
        $Data->addParam($this->accountData->getAccountName(), 'accountName');
        $Data->addParam($this->accountData->getAccountLogin(), 'accountLogin');
        $Data->addParam($this->accountData->getAccountUrl(), 'accountUrl');
        $Data->addParam($this->accountData->getAccountNotes(), 'accountNotes');
        $Data->addParam($this->accountData->getAccountUserEditId(), 'accountUserEditId');
        $Data->addParam($this->accountData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($this->accountData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
        $Data->addParam($this->accountData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        $Data->addParam($this->accountData->getAccountIsPrivate(), 'accountIsPrivate');
        $Data->addParam($this->accountData->getAccountParentId(), 'accountParentId');
        $Data->addParam($this->accountData->getAccountId(), 'accountId');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al modificar la cuenta'));
        }

        $accountInfo = ['customer_name'];
        $this->getAccountInfoById($accountInfo);

        $Log->addDetails(Html::strongText(_('Cliente')), $this->cacheParams['customer_name']);
        $Log->addDetails(Html::strongText(_('Cuenta')), sprintf('%s (%s)', $this->accountData->getAccountName(), $this->accountData->getAccountId()));
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

        if (is_array($this->cacheParams)) {
            $cache = true;

            foreach ($params as $param) {
                if (!array_key_exists($param, $this->cacheParams)) {
                    $cache = false;
                }
            }

            if ($cache) {
                return true;
            }
        }

        $query = /** @lang SQL */
            'SELECT ' . implode(',', $params) . ' '
            . 'FROM accounts '
            . 'LEFT JOIN usrGroups ug ON account_userGroupId = usergroup_id '
            . 'LEFT JOIN usrData u1 ON account_userId = u1.user_id '
            . 'LEFT JOIN usrData u2 ON account_userEditId = u2.user_id '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountId(), 'id');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        foreach ($queryRes as $param => $value) {
            $this->cacheParams[$param] = $value;
        }

        return true;
    }

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param $id int El Id del registro en el histórico
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function restoreFromHistory($id)
    {
        $Log = new Log(_('Restaurar Cuenta'));

        // Guardamos una copia de la cuenta en el histórico
        if (!AccountHistory::addHistory($this->accountData->getAccountId(), false)) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al actualizar el historial'));
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al restaurar cuenta'));
        }

        $query = /** @lang SQL */
            'UPDATE accounts dst, '
            . '(SELECT * FROM accHistory WHERE acchistory_id = :id) src SET '
            . 'dst.account_customerId = src.acchistory_customerId,'
            . 'dst.account_categoryId = src.acchistory_categoryId,'
            . 'dst.account_name = src.acchistory_name,'
            . 'dst.account_login = src.acchistory_login,'
            . 'dst.account_url = src.acchistory_url,'
            . 'dst.account_notes = src.acchistory_notes,'
            . 'dst.account_userGroupId = src.acchistory_userGroupId,'
            . 'dst.account_userEditId = :accountUserEditId,'
            . 'dst.account_dateEdit = NOW(),'
            . 'dst.account_otherUserEdit = src.acchistory_otherUserEdit + 0,'
            . 'dst.account_otherGroupEdit = src.acchistory_otherGroupEdit + 0,'
            . 'dst.account_pass = src.acchistory_pass,'
            . 'dst.account_IV = src.acchistory_IV,'
            . 'dst.account_passDate = src.acchistory_passDate,'
            . 'dst.account_passDateChange = src.acchistory_passDateChange, '
            . 'dst.account_parentId = src.acchistory_parentId '
            . 'WHERE dst.account_id = src.acchistory_accountId';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');
        $Data->addParam($this->accountData->getAccountUserEditId(), 'accountUserEditId');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al restaurar cuenta'));
        }

        $accountInfo = ['customer_name', 'account_name'];
        $this->getAccountInfoById($accountInfo);

        $Log->addDetails(Html::strongText(_('Cliente')), $this->cacheParams['customer_name']);
        $Log->addDetails(Html::strongText(_('Cuenta')), sprintf('%s (%s)', $this->cacheParams['account_name'], $this->accountData->getAccountId()));

        $Log->writeLog();
        Email::sendEmail($Log);

        return true;
    }

    /**
     * Obtener los datos de una cuenta.
     * Esta funcion realiza la consulta a la BBDD y guarda los datos en las variables de la clase.
     *
     * @return AccountExtData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getData()
    {
        $query = /** @lang SQL */
            'SELECT * FROM account_data_v WHERE account_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClass($this->accountData);
        $Data->addParam($this->accountData->getAccountId(), 'id');

        /** @var AccountExtData|array $queryRes */
        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, _('No se pudieron obtener los datos de la cuenta'));
        } elseif (is_array($queryRes) && count($queryRes) === 0){
            throw new SPException(SPException::SP_CRITICAL, _('La cuenta no existe'));
        }

        // Obtener los usuarios y grupos secundarios  y las etiquetas
        $this->accountData->setUsersId(UserAccounts::getUsersForAccount($this->accountData->getAccountId()));
        $this->accountData->setUserGroupsId(GroupAccountsUtil::getGroupsForAccount($this->accountData->getAccountId()));
        $this->accountData->setTags(AccountTags::getTags($queryRes));

        return $this->accountData;
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function createAccount()
    {
        $this->setPasswordEncrypted();

        $query = /** @lang SQL */
            'INSERT INTO accounts SET '
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
            . 'account_otherGroupEdit = :accountOtherGroupEdit,'
            . 'account_isPrivate = :accountIsPrivate,'
            . 'account_passDate = UNIX_TIMESTAMP(),'
            . 'account_passDateChange = :accountPassDateChange,'
            . 'account_parentId = :accountParentId';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountCustomerId(), 'accountCustomerId');
        $Data->addParam($this->accountData->getAccountCategoryId(), 'accountCategoryId');
        $Data->addParam($this->accountData->getAccountName(), 'accountName');
        $Data->addParam($this->accountData->getAccountLogin(), 'accountLogin');
        $Data->addParam($this->accountData->getAccountUrl(), 'accountUrl');
        $Data->addParam($this->accountData->getAccountPass(), 'accountPass');
        $Data->addParam($this->accountData->getAccountIV(), 'accountIV');
        $Data->addParam($this->accountData->getAccountNotes(), 'accountNotes');
        $Data->addParam($this->accountData->getAccountUserId(), 'accountUserId');
        $Data->addParam($this->accountData->getAccountUserGroupId() ?: Session::getUserData()->getUserGroupId(), 'accountUserGroupId');
        $Data->addParam($this->accountData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
        $Data->addParam($this->accountData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        $Data->addParam($this->accountData->getAccountIsPrivate(), 'accountIsPrivate');
        $Data->addParam($this->accountData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($this->accountData->getAccountParentId(), 'accountParentId');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al crear la cuenta'));
        }

        $this->accountData->setAccountId(DB::$lastId);

        $Log = new Log(_('Nueva Cuenta'));

        try {
            if (is_array($this->accountData->getAccountUserGroupsId())) {
                $GroupAccounsData = new GroupAccountsData();
                $GroupAccounsData->setAccgroupAccountId($this->accountData->getAccountId());
                $GroupAccounsData->setGroups($this->accountData->getAccountUserGroupsId());

                GroupAccounts::getItem($GroupAccounsData)->add();
            }
        } catch (SPException $e) {
            $Log->addDescription($e->getMessage());
            $Log->writeLog();
            $Log->resetDescription();
        }

        if (is_array($this->accountData->getAccountUsersId())
            && !UserAccounts::addUsersForAccount($this->accountData->getAccountId(), $this->accountData->getAccountUsersId())
        ) {
            $Log->addDescription(_('Error al actualizar los usuarios de la cuenta'));
            $Log->writeLog();
            $Log->resetDescription();
        }

        if (is_array($this->accountData->getTags())) {
            $AccountTags = new AccountTags();
            $AccountTags->addTags($this->accountData);
        }

        $accountInfo = ['customer_name'];
        $this->getAccountInfoById($accountInfo);

        $Log->addDetails(Html::strongText(_('Cliente')), $this->cacheParams['customer_name']);
        $Log->addDetails(Html::strongText(_('Cuenta')), sprintf('%s (%s)', $this->accountData->getAccountName(), $this->accountData->getAccountId()));
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * Devolver los datos de la clave encriptados
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function setPasswordEncrypted()
    {
        $pass = Crypt::encryptData($this->accountData->getAccountPass());

        $this->accountData->setAccountPass($pass['data']);
        $this->accountData->setAccountIV($pass['iv']);
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function deleteAccount()
    {
        $Log = new Log(_('Eliminar Cuenta'));

        // Guardamos una copia de la cuenta en el histórico
        if (!AccountHistory::addHistory($this->accountData->getAccountId(), true)) {
            $Log->addDescription(_('Error al actualizar el historial'));
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al eliminar la cuenta'));
        }

        $accountInfo = array('account_name,customer_name');
        $this->getAccountInfoById($accountInfo);

        $Log->addDetails(Html::strongText(_('Cliente')), $this->cacheParams['customer_name']);
        $Log->addDetails(Html::strongText(_('Cuenta')), sprintf('%s (%s)', $this->accountData->getAccountName(), $this->accountData->getAccountId()));

        $query = /** @lang SQL */
            'DELETE FROM accounts WHERE account_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountId(), 'id');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al eliminar la cuenta'));
        }

        try {
            GroupAccounts::getItem()->delete($this->accountData->getAccountId());
            FileUtil::deleteAccountFiles($this->accountData->getAccountId());

            $CustomFieldData = new CustomFieldData();
            $CustomFieldData->setModule(ActionsInterface::ACTION_ACC);
            CustomField::getItem($CustomFieldData)->delete($this->accountData->getAccountId());
        } catch (SPException $e) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription($e->getMessage());
        }

        if (!UserAccounts::deleteUsersForAccount($this->accountData->getAccountId())) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al eliminar usuarios asociados a la cuenta'));
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
        $query = /** @lang SQL */
            'UPDATE accounts SET account_countView = (account_countView + 1) WHERE account_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountId(), 'id');

        return DB::getQuery($Data);
    }

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @return bool
     */
    public function incrementDecryptCounter()
    {
        $query = /** @lang SQL */
            'UPDATE accounts SET account_countDecrypt = (account_countDecrypt + 1) WHERE account_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountId(), 'id');

        return DB::getQuery($Data);
    }

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @param string $currentMasterPass con la clave maestra actual
     * @param string $newMasterPass     con la nueva clave maestra
     * @param string $newHash           con el nuevo hash de la clave maestra
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateAccountsMasterPass($currentMasterPass, $newMasterPass, $newHash = null)
    {
        $accountsOk = array();
        $userId = Session::getUserData()->getUserId();
        $demoEnabled = Checks::demoIsEnabled();
        $errorCount = 0;

        $Log = new Log(_('Actualizar Clave Maestra'));
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
            $this->accountData->setAccountId($account->account_id);
            $this->accountData->setAccountUserEditId($userId);

            // No realizar cambios si está en modo demo
            if ($demoEnabled) {
                $accountsOk[] = $this->accountData->getAccountId();
                continue;
            }

            if (strlen($account->account_pass) === 0) {
                $Log->addDescription(sprintf('%s: (%s) %s', _('Clave de cuenta vacía'), $account->account_id, $account->account_name));
                continue;
            }

            if (strlen($account->account_IV) < 32) {
                $Log->addDescription(sprintf('%s: (%s) %s', _('IV de encriptación incorrecto'), $account->account_id, $account->account_name));
            }

            $decryptedPass = Crypt::getDecrypt($account->account_pass, $account->account_IV);
            $this->accountData->setAccountPass(Crypt::mkEncrypt($decryptedPass, $newMasterPass));
            $this->accountData->setAccountIV(Crypt::$strInitialVector);

            if ($this->accountData->getAccountPass() === false) {
                $errorCount++;
                $Log->addDescription(sprintf('%s: (%s) %s', _('No es posible desencriptar la clave de la cuenta'), $account->account_id, $account->account_name));
                continue;
            }

            if (!$this->updateAccountPass(true)) {
                $errorCount++;
                $Log->addDescription(sprintf('%s: (%s) %s', _('Fallo al actualizar la clave de la cuenta'), $account->account_id, $account->acchistory_name));
                continue;
            }

            $accountsOk[] = $this->accountData->getAccountId();
        }

        // Vaciar el array de mensajes de log
        if (count($Log->getDescription()) > 0) {
            $Log->writeLog();
            $Log->resetDescription();
        }

        if ($accountsOk) {
            $Log->addDetails(_('Cuentas actualizadas'), implode(',', $accountsOk));
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
        $query = /** @lang SQL */
            'SELECT account_id, account_name, account_pass, account_IV FROM accounts';

        $Data = new QueryData();
        $Data->setQuery($query);

        return DB::getResultsArray($Data);
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param bool $isMassive para no actualizar el histórico ni enviar mensajes
     * @param bool $isRestore indica si es una restauración
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateAccountPass($isMassive = false, $isRestore = false)
    {
        $Log = new Log(_('Modificar Clave'));

        // No actualizar el histórico si es por cambio de clave maestra o restauración
        if (!$isMassive
            && !$isRestore
            && !AccountHistory::addHistory($this->accountData->getAccountId(), false)
        ) {
            // Guardamos una copia de la cuenta en el histórico
            $Log->addDescription(_('Error al actualizar el historial'));
            $Log->writeLog();

            throw new SPException(SPException::SP_ERROR, _('Error al actualizar la clave'));
        }

        $this->setPasswordEncrypted();

        $query = /** @lang SQL */
            'UPDATE accounts SET '
            . 'account_pass = :accountPass,'
            . 'account_IV = :accountIV,'
            . 'account_userEditId = :accountUserEditId,'
            . 'account_dateEdit = NOW(), '
            . 'account_passDate = UNIX_TIMESTAMP(), '
            . 'account_passDateChange = :accountPassDateChange '
            . 'WHERE account_id = :accountId';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountPass(), 'accountPass');
        $Data->addParam($this->accountData->getAccountIV(), 'accountIV');
        $Data->addParam($this->accountData->getAccountUserEditId(), 'accountUserEditId');
        $Data->addParam($this->accountData->getAccountId(), 'accountId');
        $Data->addParam($this->accountData->getAccountPassDateChange(), 'accountPassDateChange');


        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al actualizar la clave'));
        }

        // No escribir en el log ni enviar correos si la actualización es
        // por cambio de clave maestra o restauración
        if (!$isMassive && !$isRestore) {
            $accountInfo = ['customer_name', 'account_name'];
            $this->getAccountInfoById($accountInfo);

            $Log->addDetails(Html::strongText(_('Cliente')), $this->cacheParams['customer_name']);
            $Log->addDetails(Html::strongText(_('Cuenta')), sprintf('%s (%s)', $this->cacheParams['account_name'], $this->accountData->getAccountId()));
            $Log->writeLog();

            Email::sendEmail($Log);
        }

        return true;
    }

    /**
     * Obtener los datos de una cuenta para mostrar la clave
     * Esta funcion realiza la consulta a la BBDD y devuelve los datos.
     *
     * @return object|false
     */
    public function getAccountPassData()
    {
        // FIXME: es necesario obtener los grupos secundarios de la cuenta
        $query = /** @lang SQL */
            'SELECT account_name,'
            . 'account_userId,'
            . 'account_userGroupId,'
            . 'account_login,'
            . 'account_pass,'
            . 'account_IV,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClass($this->accountData);
        $Data->addParam($this->accountData->getAccountId(), 'id');

        // Obtener los usuarios y grupos secundarios
        $this->accountData->setUsersId(UserAccounts::getUsersForAccount($this->accountData->getAccountId()));
        $this->accountData->setUserGroupsId(GroupAccountsUtil::getGroupsForAccount($this->accountData->getAccountId()));

        return DB::getResults($Data);
    }
}