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

use SP\Config\ConfigDB;
use SP\Core\OldCrypt;
use SP\Core\Exceptions\SPException;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Checks;

defined('APP_ROOT') || die();

/**
 * Class AccountHistory par el manejo del historial de cuentas
 *
 * @package SP
 */
class AccountHistory extends AccountBase implements AccountInterface
{
    protected $id;
    /**
     * @var bool
     */
    private $isDelete = false;
    /**
     * @var bool
     */
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
            . 'WHERE acchistory_accountId = ? '
            . 'ORDER BY acchistory_id DESC';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId);

        $arrHistory = [];

        foreach (DB::getResultsArray($Data) as $history) {
            // Comprobamos si la entrada en el historial es la primera (no tiene editor ni fecha de edición)
            if (empty($history->acchistory_dateEdit) || $history->acchistory_dateEdit === '0000-00-00 00:00:00') {
                $date = $history->acchistory_dateAdd . ' - ' . $history->user_add;
            } else {
                $date = $history->acchistory_dateEdit . ' - ' . $history->user_edit;
            }

            $arrHistory[$history->acchistory_id] = $date;
        }

        return $arrHistory;
    }

    /**
     * Crear un nuevo registro de histório de cuenta en la BBDD.
     *
     * @param int|array $id       Id de la cuenta primaria
     * @param bool      $isDelete indica que la cuenta es eliminada
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function addHistory($id, $isDelete = false)
    {
        $Data = new QueryData();
        $Data->addParam(($isDelete === false) ? 1 : 0);
        $Data->addParam(($isDelete === true) ? 1 : 0);
        $Data->addParam(ConfigDB::getValue('masterPwd'));

        if (is_array($id)) {
            $querySelect = /** @lang SQL */
                'SELECT account_id,'
                . 'account_categoryId,'
                . 'account_customerId,'
                . 'account_name,'
                . 'account_login,'
                . 'account_url,'
                . 'account_pass,'
                . 'account_key,'
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
                . 'account_isPrivate,'
                . 'account_isPrivateGroup,'
                . '?,?,? '
                . 'FROM accounts WHERE account_id IN (' . implode(',', array_fill(0, count($id), '?')) . ')';

            foreach ($id as $param) {
                $Data->addParam($param);
            }
        } else {
            $querySelect = /** @lang SQL */
                'SELECT account_id,'
                . 'account_categoryId,'
                . 'account_customerId,'
                . 'account_name,'
                . 'account_login,'
                . 'account_url,'
                . 'account_pass,'
                . 'account_key,'
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
                . 'account_isPrivate,'
                . 'account_isPrivateGroup,'
                . '?,?,? '
                . 'FROM accounts WHERE account_id = ?';

            $Data->addParam($id);
        }

        $query = /** @lang SQL */
            'INSERT INTO accHistory '
            . '(acchistory_accountId,'
            . 'acchistory_categoryId,'
            . 'acchistory_customerId,'
            . 'acchistory_name,'
            . 'acchistory_login,'
            . 'acchistory_url,'
            . 'acchistory_pass,'
            . 'acchistory_key,'
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
            . 'accHistory_isPrivate,'
            . 'accHistory_isPrivateGroup,'
            . 'acchistory_isModify,'
            . 'acchistory_isDeleted,'
            . 'acchistory_mPassHash)';

        $Data->setQuery($query . ' ' . $querySelect);
        $Data->setOnErrorMessage(__('Error al actualizar el historial', false));

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
            'SELECT acchistory_accountId FROM accHistory WHERE acchistory_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($historyId);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, __('No se pudieron obtener los datos de la cuenta', false), 0);
        }

        return $queryRes->acchistory_accountId;
    }

    /**
     * Actualiza el hash de las cuentas en el histórico.
     *
     * @param $newHash string El nuevo hash de la clave maestra
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public static function updateAccountsMPassHash($newHash)
    {
        $query = /** @lang SQL */
            'UPDATE accHistory SET '
            . 'acchistory_mPassHash = ? '
            . 'WHERE acchistory_mPassHash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($newHash);
        $Data->addParam(ConfigDB::getValue('masterPwd'));

        return DB::getQuery($Data);
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
            'WHERE acchistory_id = ? ' .
            'AND acchistory_mPassHash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(null === $id ? $this->accountData->getAccountId() : $id);
        $Data->addParam(ConfigDB::getValue('masterPwd'));

        return (DB::getResults($Data) !== false);
    }

    /**
     * Actualiza la clave del histórico de una cuenta en la BBDD.
     *
     * @param object $AccountData Objeto con los datos de la cuenta
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function updateAccountPass($AccountData)
    {
        $query = /** @lang SQL */
            'UPDATE accHistory SET '
            . 'acchistory_pass = :accountPass,'
            . 'acchistory_key = :accountKey,'
            . 'acchistory_mPassHash = :hash '
            . 'WHERE acchistory_id = :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($AccountData->id, 'id');
        $Data->addParam($AccountData->pass, 'accountPass');
        $Data->addParam($AccountData->key, 'accountKey');
        $Data->addParam($AccountData->hash, 'hash');

        return DB::getQuery($Data);
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
            'SELECT acchistory_name AS account_name,'
            . 'acchistory_userId AS account_userId,'
            . 'acchistory_userGroupId AS account_userGroupId,'
            . 'acchistory_login AS account_login,'
            . 'acchistory_pass AS account_pass,'
            . 'acchistory_key AS account_key,'
            . 'customer_name '
            . 'FROM accHistory '
            . 'LEFT JOIN customers ON acchistory_customerId = customer_id '
            . 'WHERE acchistory_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClass($this->accountData);
        $Data->addParam($this->getId());

        return DB::getResults($Data);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
            . 'acchistory_key as account_key,'
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
            . 'acchistory_isPrivate + 0 AS account_isPrivate,'
            . 'acchistory_isPrivateGroup + 0 AS account_isPrivateGroup,'
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
            . 'WHERE acchistory_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClass($this->accountData);
        $Data->addParam($this->getId());

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, __('No se pudieron obtener los datos de la cuenta', false));
        }

        $this->accountData = $queryRes;

        return $queryRes;
    }

    /**
     * Crear una cuenta en el historial
     *
     * @param bool $encryptPass
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function createAccount($encryptPass = true)
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
            . 'acchistory_key = :accountKey,'
            . 'acchistory_notes = :accountNotes,'
            . 'acchistory_dateAdd = :accountDateAdd,'
            . 'acchistory_dateEdit = :accountDateEdit,'
            . 'acchistory_countView = :accountCountView,'
            . 'acchistory_countDecrypt  = :accountCountDecrypt,'
            . 'acchistory_userId = :accountUserId,'
            . 'acchistory_userGroupId = :accountUserGroupId,'
            . 'acchistory_otherUserEdit = :accountOtherUserEdit,'
            . 'acchistory_otherGroupEdit = :accountOtherGroupEdit,'
            . 'acchistory_isPrivate = :isPrivate,'
            . 'acchistory_isPrivateGroup = :isPrivateGroup,'
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
        $Data->addParam($this->accountData->getAccountKey(), 'accountKey');
        $Data->addParam($this->accountData->getAccountNotes(), 'accountNotes');
        $Data->addParam($this->accountData->getAccountUserId(), 'accountUserId');
        $Data->addParam($this->accountData->getAccountUserGroupId(), 'accountUserGroupId');
        $Data->addParam($this->accountData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
        $Data->addParam($this->accountData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        $Data->addParam($this->accountData->getAccountIsPrivate(), 'isPrivate');
        $Data->addParam($this->accountData->getAccountIsPrivateGroup(), 'isPrivateGroup');
        $Data->addParam($this->isIsModify(), 'isModify');
        $Data->addParam($this->isIsDelete(), 'isDelete');
        $Data->addParam(ConfigDB::getValue('masterPwd'), 'masterPwd');

        return DB::getQuery($Data);
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
     * Eliminar una cuenta del historial
     *
     * @param $id
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function deleteAccount($id)
    {
        if (is_array($id)) {
            foreach ($id as $accountId) {
                $this->deleteAccount($accountId);
            }

            return true;
        }

        $query = /** @lang SQL */
            'DELETE FROM accHistory WHERE acchistory_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar la cuenta', false));

        DB::getQuery($Data);

        return $Data->getQueryNumRows() === 1;
    }
}