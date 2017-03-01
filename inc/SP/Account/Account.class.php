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
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\DataModel\GroupAccountsData;
use SP\Log\Log;
use SP\Mgmt\Groups\GroupAccounts;
use SP\Mgmt\Groups\GroupAccountsUtil;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de realizar las operaciones sobre las cuentas de sysPass.
 */
class Account extends AccountBase implements AccountInterface
{
    /**
     * Actualiza los datos de una cuenta en la BBDD.
     *
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateAccount()
    {
        $Acl = Session::getAccountAcl($this->accountData->getAccountId());

        // Guardamos una copia de la cuenta en el histórico
        AccountHistory::addHistory($this->accountData->getAccountId(), false);

        try {
            if ($Acl->getStoredAcl()->isShowPermission()) {
                $GroupAccountsData = new GroupAccountsData();
                $GroupAccountsData->setAccgroupAccountId($this->accountData->getAccountId());
                $GroupAccountsData->setGroups($this->accountData->getUserGroupsId());

                GroupAccounts::getItem($GroupAccountsData)->update();
                UserAccounts::updateUsersForAccount($this->accountData->getAccountId(), $this->accountData->getUsersId());
            }
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, $e->getMessage(), Log::ERROR);
        }

        if (is_array($this->accountData->getTags())) {
            $AccountTags = new AccountTags();
            $AccountTags->addTags($this->accountData, true);
        }

        $Data = new QueryData();

        $fields = [
            'account_customerId = :accountCustomerId',
            'account_categoryId = :accountCategoryId',
            'account_name = :accountName',
            'account_login = :accountLogin',
            'account_url = :accountUrl',
            'account_notes = :accountNotes',
            'account_userEditId = :accountUserEditId',
            'account_dateEdit = NOW()',
            'account_passDateChange = :accountPassDateChange',
            'account_isPrivate = :accountIsPrivate',
            'account_isPrivateGroup = :accountIsPrivateGroup',
            'account_parentId = :accountParentId'
        ];

        if ($this->accountData->getAccountUserGroupId()) {
            $fields[] = 'account_userGroupId = :accountUserGroupId';

            $Data->addParam($this->accountData->getAccountUserGroupId(), 'accountUserGroupId');
        }

        if ($Acl->getStoredAcl()->isShowPermission()) {
            $fields[] = 'account_otherUserEdit = :accountOtherUserEdit';
            $fields[] = 'account_otherGroupEdit = :accountOtherGroupEdit';

            $Data->addParam($this->accountData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
            $Data->addParam($this->accountData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        }

        $query = /** @lang SQL */
            'UPDATE accounts SET ' . implode(',', $fields) . ' WHERE account_id = :accountId';

        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountCustomerId(), 'accountCustomerId');
        $Data->addParam($this->accountData->getAccountCategoryId(), 'accountCategoryId');
        $Data->addParam($this->accountData->getAccountName(), 'accountName');
        $Data->addParam($this->accountData->getAccountLogin(), 'accountLogin');
        $Data->addParam($this->accountData->getAccountUrl(), 'accountUrl');
        $Data->addParam($this->accountData->getAccountNotes(), 'accountNotes');
        $Data->addParam($this->accountData->getAccountUserEditId(), 'accountUserEditId');
        $Data->addParam($this->accountData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($this->accountData->getAccountIsPrivate(), 'accountIsPrivate');
        $Data->addParam($this->accountData->getAccountIsPrivateGroup(), 'accountIsPrivateGroup');
        $Data->addParam($this->accountData->getAccountParentId(), 'accountParentId');
        $Data->addParam($this->accountData->getAccountId(), 'accountId');
        $Data->setOnErrorMessage(__('Error al modificar la cuenta', false));

        DB::getQuery($Data);

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
        // Guardamos una copia de la cuenta en el histórico
        AccountHistory::addHistory($this->accountData->getAccountId(), false);

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
            . 'dst.account_key = src.acchistory_key,'
            . 'dst.account_passDate = src.acchistory_passDate,'
            . 'dst.account_passDateChange = src.acchistory_passDateChange, '
            . 'dst.account_parentId = src.acchistory_parentId, '
            . 'dst.account_isPrivate = src.accHistory_isPrivate, '
            . 'dst.account_isPrivateGroup = src.accHistory_isPrivateGroup '
            . 'WHERE dst.account_id = src.acchistory_accountId';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id, 'id');
        $Data->addParam($this->accountData->getAccountUserEditId(), 'accountUserEditId');
        $Data->setOnErrorMessage(__('Error al restaurar cuenta', false));

        DB::getQuery($Data);

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
            'SELECT * FROM account_data_v WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClass($this->accountData);
        $Data->addParam($this->accountData->getAccountId());

        /** @var AccountExtData|array $queryRes */
        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, __('No se pudieron obtener los datos de la cuenta', false));
        } elseif (is_array($queryRes) && count($queryRes) === 0) {
            throw new SPException(SPException::SP_CRITICAL, __('La cuenta no existe', false));
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
     * @param bool $encryptPass Encriptar la clave?
     * @return $this
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws SPException
     */
    public function createAccount($encryptPass = true)
    {
        if ($encryptPass === true) {
            $this->setPasswordEncrypted();
        }

        $query = /** @lang SQL */
            'INSERT INTO accounts SET '
            . 'account_customerId = :accountCustomerId,'
            . 'account_categoryId = :accountCategoryId,'
            . 'account_name = :accountName,'
            . 'account_login = :accountLogin,'
            . 'account_url = :accountUrl,'
            . 'account_pass = :accountPass,'
            . 'account_key = :accountKey,'
            . 'account_notes = :accountNotes,'
            . 'account_dateAdd = NOW(),'
            . 'account_userId = :accountUserId,'
            . 'account_userGroupId = :accountUserGroupId,'
            . 'account_userEditId = :accountUserEditId,'
            . 'account_otherUserEdit = :accountOtherUserEdit,'
            . 'account_otherGroupEdit = :accountOtherGroupEdit,'
            . 'account_isPrivate = :accountIsPrivate,'
            . 'account_isPrivateGroup = :accountIsPrivateGroup,'
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
        $Data->addParam($this->accountData->getAccountKey(), 'accountKey');
        $Data->addParam($this->accountData->getAccountNotes(), 'accountNotes');
        $Data->addParam($this->accountData->getAccountUserId(), 'accountUserId');
        $Data->addParam($this->accountData->getAccountUserGroupId() ?: Session::getUserData()->getUserGroupId(), 'accountUserGroupId');
        $Data->addParam($this->accountData->getAccountUserId(), 'accountUserEditId');
        $Data->addParam($this->accountData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
        $Data->addParam($this->accountData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        $Data->addParam($this->accountData->getAccountIsPrivate(), 'accountIsPrivate');
        $Data->addParam($this->accountData->getAccountIsPrivateGroup(), 'accountIsPrivateGroup');
        $Data->addParam($this->accountData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($this->accountData->getAccountParentId(), 'accountParentId');
        $Data->setOnErrorMessage(__('Error al crear la cuenta', false));

        DB::getQuery($Data);

        $this->accountData->setAccountId(DB::$lastId);

        try {
            if (is_array($this->accountData->getAccountUserGroupsId())) {
                $GroupAccounsData = new GroupAccountsData();
                $GroupAccounsData->setAccgroupAccountId($this->accountData->getAccountId());
                $GroupAccounsData->setGroups($this->accountData->getAccountUserGroupsId());

                GroupAccounts::getItem($GroupAccounsData)->add();
            }

            if (is_array($this->accountData->getAccountUsersId())) {
                UserAccounts::addUsersForAccount($this->accountData->getAccountId(), $this->accountData->getAccountUsersId());
            }

            if (is_array($this->accountData->getTags())) {
                $AccountTags = new AccountTags();
                $AccountTags->addTags($this->accountData);
            }
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, $e->getMessage(), Log::ERROR);
        }


        return $this;
    }

    /**
     * Devolver los datos de la clave encriptados
     *
     * @param string $masterPass Clave maestra a utilizar
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function setPasswordEncrypted($masterPass = null)
    {
        try {
            $masterPass = $masterPass ?: CryptSession::getSessionKey();
            $securedKey = Crypt::makeSecuredKey($masterPass);

            $this->accountData->setAccountPass(Crypt::encrypt($this->accountData->getAccountPass(), $securedKey, $masterPass));
            $this->accountData->setAccountKey($securedKey);

            if (strlen($securedKey) > 1000 || strlen($this->accountData->getAccountPass()) > 1000) {
                throw new QueryException(SPException::SP_ERROR, __('Error interno', false));
            }
        } catch (CryptoException $e) {
            throw new SPException(SPException::SP_ERROR, __('Error interno', false));
        }
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param int|array $id
     * @return bool Los ids de las cuentas eliminadas
     * @throws SPException
     */
    public function deleteAccount($id)
    {
        if (is_array($id)) {
            foreach ($id as $accountId) {
                $this->deleteAccount($accountId);
            }

            return true;
        }

        // Guardamos una copia de la cuenta en el histórico
        AccountHistory::addHistory($id, true);

        $Data = new QueryData();

        $query = /** @lang SQL */
            'DELETE FROM accounts WHERE account_id = ? LIMIT 1';

        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar la cuenta', false));

        DB::getQuery($Data);

        return $Data->getQueryNumRows() === 1;
    }

    /**
     * Incrementa el contador de visitas de una cuenta en la BBDD
     *
     * @param int $id
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function incrementViewCounter($id = null)
    {
        $query = /** @lang SQL */
            'UPDATE accounts SET account_countView = (account_countView + 1) WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id ?: $this->accountData->getAccountId());

        return DB::getQuery($Data);
    }

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @param null $id
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function incrementDecryptCounter($id = null)
    {
        $query = /** @lang SQL */
            'UPDATE accounts SET account_countDecrypt = (account_countDecrypt + 1) WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id ?: $this->accountData->getAccountId());

        return DB::getQuery($Data);
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param bool $isMassive para no actualizar el histórico ni enviar mensajes
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateAccountPass($isMassive = false)
    {
        // No actualizar el histórico si es por cambio de clave maestra o restauración
        if (!$isMassive) {
            AccountHistory::addHistory($this->accountData->getAccountId(), false);

            $this->setPasswordEncrypted();
        }

        $query = /** @lang SQL */
            'UPDATE accounts SET '
            . 'account_pass = :accountPass,'
            . 'account_key = :accountKey,'
            . 'account_userEditId = :accountUserEditId,'
            . 'account_dateEdit = NOW(), '
            . 'account_passDate = UNIX_TIMESTAMP(), '
            . 'account_passDateChange = :accountPassDateChange '
            . 'WHERE account_id = :accountId';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->accountData->getAccountPass(), 'accountPass');
        $Data->addParam($this->accountData->getAccountKey(), 'accountKey');
        $Data->addParam($this->accountData->getAccountUserEditId(), 'accountUserEditId');
        $Data->addParam($this->accountData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($this->accountData->getAccountId(), 'accountId');
        $Data->setOnErrorMessage(__('Error al actualizar la clave', false));

        DB::getQuery($Data);

        return true;
    }

    /**
     * Obtener los datos de una cuenta para mostrar la clave
     * Esta funcion realiza la consulta a la BBDD y devuelve los datos.
     *
     * @return AccountData|false
     */
    public function getAccountPassData()
    {
        $query = /** @lang SQL */
            'SELECT account_name,'
            . 'account_userId,'
            . 'account_userGroupId,'
            . 'account_login,'
            . 'account_pass,'
            . 'account_key,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClass($this->accountData);
        $Data->addParam($this->accountData->getAccountId());

        // Obtener los usuarios y grupos secundarios
        $this->accountData->setUsersId(UserAccounts::getUsersForAccount($this->accountData->getAccountId()));
        $this->accountData->setUserGroupsId(GroupAccountsUtil::getGroupsForAccount($this->accountData->getAccountId()));

        return DB::getResults($Data);
    }

    /**
     * Obtener los datos de una cuenta.
     * Esta funcion realiza la consulta a la BBDD y guarda los datos en las variables de la clase.
     *
     * @return AccountExtData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getDataForLink()
    {
        $query = /** @lang SQL */
            'SELECT account_name,'
            . 'account_login,'
            . 'account_pass,'
            . 'account_key,'
            . 'account_url,'
            . 'account_notes,'
            . 'category_name,'
            . 'customer_name '
            . 'FROM accounts '
            . 'LEFT JOIN customers ON account_customerId = customer_id '
            . 'LEFT JOIN categories ON account_categoryId = category_id '
            . 'WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClass($this->accountData);
        $Data->addParam($this->accountData->getAccountId());

        /** @var AccountExtData|array $queryRes */
        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, __('No se pudieron obtener los datos de la cuenta', false));
        } elseif (is_array($queryRes) && count($queryRes) === 0) {
            throw new SPException(SPException::SP_CRITICAL, __('La cuenta no existe', false));
        }

        return $this->accountData;
    }
}