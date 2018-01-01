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

namespace SP\Services\Account;

use Defuse\Crypto\Exception\CryptoException;
use SP\Account\AccountHistory;
use SP\Account\AccountTags;
use SP\Account\AccountUtil;
use SP\Account\UserAccounts;
use SP\Core\Acl\Acl;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountPassData;
use SP\DataModel\GroupAccountsData;
use SP\DataModel\ItemSearchData;
use SP\Log\Log;
use SP\Mgmt\Groups\GroupAccounts;
use SP\Mgmt\Groups\GroupAccountsUtil;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Services\ServiceItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class AccountService
 *
 * @package Services
 */
class AccountService extends Service implements ServiceItemInterface
{
    use ServiceItemTrait;

    /**
     * @param $id
     * @return AccountPassData
     */
    public function getPasswordForId($id)
    {
        $Data = new QueryData();
        $Data->setMapClassName(AccountPassData::class);
        $Data->setLimit(1);

        $Data->setSelect('account_id, account_name, account_login, account_pass, account_key, account_parentId');
        $Data->setFrom('accounts');

        $queryWhere = AccountUtil::getAccountFilterUser($Data, $this->session);
        $queryWhere[] = 'account_id = ?';
        $Data->addParam($id);

        $Data->setWhere($queryWhere);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * @param $id
     * @return AccountPassData
     */
    public function getPasswordHistoryForId($id)
    {
        $Data = new QueryData();
        $Data->setMapClassName(AccountPassData::class);
        $Data->setLimit(1);

        $Data->setSelect('acchistory_id AS account_id, acchistory_name AS account_name, acchistory_login AS account_login, acchistory_pass AS account_pass, acchistory_key AS account_key, acchistory_parentId  AS account_parentId');
        $Data->setFrom('accHistory');

        $queryWhere = AccountUtil::getAccountHistoryFilterUser($Data, $this->session);
        $queryWhere[] = 'acchistory_id = ?';
        $Data->addParam($id);

        $Data->setWhere($queryWhere);

        return DbWrapper::getResults($Data, $this->db);
    }

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @param int $id
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function incrementDecryptCounter($id)
    {
        $query = /** @lang SQL */
            'UPDATE accounts SET account_countDecrypt = (account_countDecrypt + 1) WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Logs account action
     *
     * @param int $id
     * @param int $actionId
     * @return \SP\Core\Messages\LogMessage
     */
    public function logAction($id, $actionId)
    {
        $query = /** @lang SQL */
            'SELECT account_id, account_name, customer_name FROM account_data_v WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $account = DbWrapper::getResults($Data, $this->db);

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(Acl::getActionInfo($actionId));
        $LogMessage->addDetails(__u('ID'), $id);
        $LogMessage->addDetails(__u('Cuenta'), $account->customer_name . ' / ' . $account->account_name);
        $Log->writeLog();

        return $LogMessage;
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param AccountExtData $itemData
     * @return AccountExtData
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function create($itemData)
    {
        $this->getPasswordEncrypted($itemData);

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
        $Data->addParam($itemData->getAccountCustomerId(), 'accountCustomerId');
        $Data->addParam($itemData->getAccountCategoryId(), 'accountCategoryId');
        $Data->addParam($itemData->getAccountName(), 'accountName');
        $Data->addParam($itemData->getAccountLogin(), 'accountLogin');
        $Data->addParam($itemData->getAccountUrl(), 'accountUrl');
        $Data->addParam($itemData->getAccountPass(), 'accountPass');
        $Data->addParam($itemData->getAccountKey(), 'accountKey');
        $Data->addParam($itemData->getAccountNotes(), 'accountNotes');
        $Data->addParam($itemData->getAccountUserId(), 'accountUserId');
        $Data->addParam($itemData->getAccountUserGroupId() ?: $this->session->getUserData()->getUserGroupId(), 'accountUserGroupId');
        $Data->addParam($itemData->getAccountUserId(), 'accountUserEditId');
        $Data->addParam($itemData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
        $Data->addParam($itemData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        $Data->addParam($itemData->getAccountIsPrivate(), 'accountIsPrivate');
        $Data->addParam($itemData->getAccountIsPrivateGroup(), 'accountIsPrivateGroup');
        $Data->addParam($itemData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($itemData->getAccountParentId(), 'accountParentId');
        $Data->setOnErrorMessage(__u('Error al crear la cuenta'));

        DbWrapper::getQuery($Data, $this->db);

        $itemData->setAccountId($this->db->getLastId());

        $this->addItems($itemData);

        return $itemData;
    }

    /**
     * Devolver los datos de la clave encriptados
     *
     * @param AccountExtData $accountData
     * @param string         $masterPass Clave maestra a utilizar
     * @return AccountExtData
     * @throws QueryException
     * @throws SPException
     */
    public function getPasswordEncrypted(AccountExtData $accountData, $masterPass = null)
    {
        try {
            $masterPass = $masterPass ?: CryptSession::getSessionKey();
            $securedKey = Crypt::makeSecuredKey($masterPass);

            $accountData->setAccountPass(Crypt::encrypt($accountData->getAccountPass(), $securedKey, $masterPass));
            $accountData->setAccountKey($securedKey);

            if (strlen($securedKey) > 1000 || strlen($accountData->getAccountPass()) > 1000) {
                throw new QueryException(SPException::SP_ERROR, __u('Error interno'));
            }

            return $accountData;
        } catch (CryptoException $e) {
            throw new SPException(SPException::SP_ERROR, __u('Error interno'));
        }
    }

    /**
     * Adds external items to the account
     *
     * @param AccountExtData $accountData
     */
    protected function addItems(AccountExtData $accountData)
    {
        try {
            if (is_array($accountData->getAccountUserGroupsId())) {
                $GroupAccounsData = new GroupAccountsData();
                $GroupAccounsData->setAccgroupAccountId($accountData->getAccountId());
                $GroupAccounsData->setGroups($accountData->getAccountUserGroupsId());

                GroupAccounts::getItem($GroupAccounsData)->add();
            }

            if (is_array($accountData->getAccountUsersId())) {
                UserAccounts::addUsersForAccount($accountData->getAccountId(), $accountData->getAccountUsersId());
            }

            if (is_array($accountData->getTags())) {
                $AccountTags = new AccountTags();
                $AccountTags->addTags($accountData);
            }
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, $e->getMessage(), Log::ERROR);
        }
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param AccountExtData $accountData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function editPassword(AccountExtData $accountData)
    {
        AccountHistory::addHistory($accountData->getAccountId());

        $this->getPasswordEncrypted($accountData);

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
        $Data->addParam($accountData->getAccountPass(), 'accountPass');
        $Data->addParam($accountData->getAccountKey(), 'accountKey');
        $Data->addParam($accountData->getAccountUserEditId(), 'accountUserEditId');
        $Data->addParam($accountData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($accountData->getAccountId(), 'accountId');
        $Data->setOnErrorMessage(__u('Error al actualizar la clave'));

        DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param int $accountId
     * @param int $historyId El Id del registro en el histórico
     * @throws \SP\Core\Exceptions\SPException
     */
    public function editRestore($historyId, $accountId)
    {
        // Guardamos una copia de la cuenta en el histórico
        AccountHistory::addHistory($accountId);

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
        $Data->addParam($historyId, 'id');
        $Data->addParam($this->session->getUserData()->getUserId(), 'accountUserEditId');
        $Data->setOnErrorMessage(__u('Error al restaurar cuenta'));

        DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param array|int $id
     * @return bool Los ids de las cuentas eliminadas
     * @throws SPException
     */
    public function delete($id)
    {
        if (is_array($id)) {
            foreach ($id as $accountId) {
                $this->delete($accountId);
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
        $Data->setOnErrorMessage(__u('Error al eliminar la cuenta'));

        DbWrapper::getQuery($Data, $this->db);

        return $Data->getQueryNumRows() === 1;
    }

    /**
     * Updates an item
     *
     * @param AccountExtData $itemData
     * @return mixed
     * @throws SPException
     */
    public function update($itemData)
    {
        $accountAcl = $this->session->getAccountAcl($itemData->getAccountId());

        // Guardamos una copia de la cuenta en el histórico
        AccountHistory::addHistory($itemData->getAccountId());

        $this->updateItems($itemData);

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

        if ($itemData->getAccountUserGroupId()) {
            $fields[] = 'account_userGroupId = :accountUserGroupId';

            $Data->addParam($itemData->getAccountUserGroupId(), 'accountUserGroupId');
        }

        if (null !== $accountAcl && $accountAcl->getStoredAcl()->isShowPermission()) {
            $fields[] = 'account_otherUserEdit = :accountOtherUserEdit';
            $fields[] = 'account_otherGroupEdit = :accountOtherGroupEdit';

            $Data->addParam($itemData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
            $Data->addParam($itemData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        }

        $query = /** @lang SQL */
            'UPDATE accounts SET ' . implode(',', $fields) . ' WHERE account_id = :accountId';

        $Data->setQuery($query);
        $Data->addParam($itemData->getAccountCustomerId(), 'accountCustomerId');
        $Data->addParam($itemData->getAccountCategoryId(), 'accountCategoryId');
        $Data->addParam($itemData->getAccountName(), 'accountName');
        $Data->addParam($itemData->getAccountLogin(), 'accountLogin');
        $Data->addParam($itemData->getAccountUrl(), 'accountUrl');
        $Data->addParam($itemData->getAccountNotes(), 'accountNotes');
        $Data->addParam($itemData->getAccountUserEditId(), 'accountUserEditId');
        $Data->addParam($itemData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($itemData->getAccountIsPrivate(), 'accountIsPrivate');
        $Data->addParam($itemData->getAccountIsPrivateGroup(), 'accountIsPrivateGroup');
        $Data->addParam($itemData->getAccountParentId(), 'accountParentId');
        $Data->addParam($itemData->getAccountId(), 'accountId');
        $Data->setOnErrorMessage(__u('Error al modificar la cuenta'));

        DbWrapper::getQuery($Data, $this->db);

        return $itemData;
    }

    /**
     * Updates external items for the account
     *
     * @param AccountExtData $accountData
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function updateItems(AccountExtData $accountData)
    {
        $accountAcl = $this->session->getAccountAcl($accountData->getAccountId());

        try {
            if (null !== $accountAcl && $accountAcl->getStoredAcl()->isShowPermission()) {
                $GroupAccountsData = new GroupAccountsData();
                $GroupAccountsData->setAccgroupAccountId($accountData->getAccountId());
                $GroupAccountsData->setGroups($accountData->getUserGroupsId());

                GroupAccounts::getItem($GroupAccountsData)->update();
                UserAccounts::updateUsersForAccount($accountData->getAccountId(), $accountData->getUsersId());
            }
        } catch (SPException $e) {
            Log::writeNewLog(__FUNCTION__, $e->getMessage(), Log::ERROR);
        }

        if (is_array($accountData->getTags())) {
            $AccountTags = new AccountTags();
            $AccountTags->addTags($accountData, true);
        }
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT * FROM account_data_v WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName(AccountExtData::class);
        $Data->addParam($id);

        /** @var AccountExtData|array $queryRes */
        $queryRes = DbWrapper::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_CRITICAL, __u('No se pudieron obtener los datos de la cuenta'));
        }

        if (is_array($queryRes) && count($queryRes) === 0) {
            throw new SPException(SPException::SP_CRITICAL, __u('La cuenta no existe'));
        }

        // Obtener los usuarios y grupos secundarios  y las etiquetas
        $queryRes->setUsersId(UserAccounts::getUsersForAccount($queryRes->getAccountId()));
        $queryRes->setUserGroupsId(GroupAccountsUtil::getGroupsForAccount($queryRes->getAccountId()));
        $queryRes->setTags(AccountTags::getTags($queryRes));

        return $queryRes;
    }

    /**
     * Returns all the items
     *
     */
    public function getAll()
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     */
    public function getByIdBatch(array $ids)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     */
    public function deleteByIdBatch(array $ids)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     */
    public function checkInUse($id)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('account_id, account_name, customer_name');
        $Data->setFrom('accounts LEFT JOIN customers ON account_customerId = customer_id');
        $Data->setOrder('account_name');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('account_name LIKE ? OR customer_name LIKE ?');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        $queryRes = DbWrapper::getResultsArray($Data, $this->db);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
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
        $Data->addParam($id);

        return DbWrapper::getQuery($Data, $this->db);
    }

    /**
     * Obtener los datos de una cuenta.
     *
     * @param $id
     * @return AccountExtData
     * @throws SPException
     */
    public function getDataForLink($id)
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
        $Data->setMapClassName(AccountExtData::class);
        $Data->addParam($id);

        /** @var AccountExtData|array $queryRes */
        $queryRes = DbWrapper::getResults($Data, $this->db);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, __u('No se pudieron obtener los datos de la cuenta'));
        }

        if (is_array($queryRes) && count($queryRes) === 0) {
            throw new SPException(SPException::SP_ERROR, __u('La cuenta no existe'));
        }

        return $queryRes;
    }
}