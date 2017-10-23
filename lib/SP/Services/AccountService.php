<?php

namespace SP\Services;

use Defuse\Crypto\Exception\CryptoException;
use SP\Account\AccountHistory;
use SP\Account\AccountTags;
use SP\Account\UserAccounts;
use SP\Core\Acl;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\AccountPassData;
use SP\Account\AccountUtil;
use SP\DataModel\GroupAccountsData;
use SP\Log\Log;
use SP\Mgmt\Groups\GroupAccounts;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Core\Crypt\Session as CryptSession;

/**
 * Class AccountService
 *
 * @package Services
 */
class AccountService extends Service
{
    /**
     * @param $id
     */
    public function getAccount($id)
    {

    }

    /**
     * @param $id
     * @return AccountPassData
     */
    public function getAccountPass($id)
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

        return DbWrapper::getResults($Data);
    }

    /**
     * @param $id
     * @return AccountPassData
     */
    public function getAccountPassHistory($id)
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

        return DbWrapper::getResults($Data);
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

        return DbWrapper::getQuery($Data);
    }

    /**
     * Logs account action
     *
     * @param int $id
     * @param int $actionId
     * @return \SP\Core\Messages\LogMessage
     */
    public function logAccountAction($id, $actionId)
    {
        $query = /** @lang SQL */
            'SELECT account_id, account_name, customer_name FROM account_data_v WHERE account_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $account = DbWrapper::getResults($Data);

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(Acl::getActionInfo($actionId));
        $LogMessage->addDetails(__('ID', false), $id);
        $LogMessage->addDetails(__('Cuenta', false), $account->customer_name . ' / ' . $account->account_name);
        $Log->writeLog();

        return $LogMessage;
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param AccountExtData $accountData
     * @return AccountExtData
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function createAccount(AccountExtData $accountData)
    {
        $this->getPasswordEncrypted($accountData);

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
        $Data->addParam($accountData->getAccountCustomerId(), 'accountCustomerId');
        $Data->addParam($accountData->getAccountCategoryId(), 'accountCategoryId');
        $Data->addParam($accountData->getAccountName(), 'accountName');
        $Data->addParam($accountData->getAccountLogin(), 'accountLogin');
        $Data->addParam($accountData->getAccountUrl(), 'accountUrl');
        $Data->addParam($accountData->getAccountPass(), 'accountPass');
        $Data->addParam($accountData->getAccountKey(), 'accountKey');
        $Data->addParam($accountData->getAccountNotes(), 'accountNotes');
        $Data->addParam($accountData->getAccountUserId(), 'accountUserId');
        $Data->addParam($accountData->getAccountUserGroupId() ?: $this->session->getUserData()->getUserGroupId(), 'accountUserGroupId');
        $Data->addParam($accountData->getAccountUserId(), 'accountUserEditId');
        $Data->addParam($accountData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
        $Data->addParam($accountData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        $Data->addParam($accountData->getAccountIsPrivate(), 'accountIsPrivate');
        $Data->addParam($accountData->getAccountIsPrivateGroup(), 'accountIsPrivateGroup');
        $Data->addParam($accountData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($accountData->getAccountParentId(), 'accountParentId');
        $Data->setOnErrorMessage(__('Error al crear la cuenta', false));

        DbWrapper::getQuery($Data);

        $accountData->setAccountId(DbWrapper::$lastId);

        $this->addAccountItems($accountData);

        return $accountData;
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
                throw new QueryException(SPException::SP_ERROR, __('Error interno', false));
            }

            return $accountData;
        } catch (CryptoException $e) {
            throw new SPException(SPException::SP_ERROR, __('Error interno', false));
        }
    }

    /**
     * Adds external items to the account
     *
     * @param AccountExtData $accountData
     */
    protected function addAccountItems(AccountExtData $accountData)
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
     * Actualiza los datos de una cuenta en la BBDD.
     *
     * @param AccountExtData $accountData
     * @return AccountExtData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function editAccount(AccountExtData $accountData)
    {
        $accountAcl = $this->session->getAccountAcl($accountData->getAccountId());

        // Guardamos una copia de la cuenta en el histórico
        AccountHistory::addHistory($accountData->getAccountId());

        $this->updateAccountItems($accountData);

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

        if ($accountData->getAccountUserGroupId()) {
            $fields[] = 'account_userGroupId = :accountUserGroupId';

            $Data->addParam($accountData->getAccountUserGroupId(), 'accountUserGroupId');
        }

        if (null !== $accountAcl && $accountAcl->getStoredAcl()->isShowPermission()) {
            $fields[] = 'account_otherUserEdit = :accountOtherUserEdit';
            $fields[] = 'account_otherGroupEdit = :accountOtherGroupEdit';

            $Data->addParam($accountData->getAccountOtherUserEdit(), 'accountOtherUserEdit');
            $Data->addParam($accountData->getAccountOtherGroupEdit(), 'accountOtherGroupEdit');
        }

        $query = /** @lang SQL */
            'UPDATE accounts SET ' . implode(',', $fields) . ' WHERE account_id = :accountId';

        $Data->setQuery($query);
        $Data->addParam($accountData->getAccountCustomerId(), 'accountCustomerId');
        $Data->addParam($accountData->getAccountCategoryId(), 'accountCategoryId');
        $Data->addParam($accountData->getAccountName(), 'accountName');
        $Data->addParam($accountData->getAccountLogin(), 'accountLogin');
        $Data->addParam($accountData->getAccountUrl(), 'accountUrl');
        $Data->addParam($accountData->getAccountNotes(), 'accountNotes');
        $Data->addParam($accountData->getAccountUserEditId(), 'accountUserEditId');
        $Data->addParam($accountData->getAccountPassDateChange(), 'accountPassDateChange');
        $Data->addParam($accountData->getAccountIsPrivate(), 'accountIsPrivate');
        $Data->addParam($accountData->getAccountIsPrivateGroup(), 'accountIsPrivateGroup');
        $Data->addParam($accountData->getAccountParentId(), 'accountParentId');
        $Data->addParam($accountData->getAccountId(), 'accountId');
        $Data->setOnErrorMessage(__('Error al modificar la cuenta', false));

        DbWrapper::getQuery($Data);

        return $accountData;
    }

    /**
     * Updates external items for the account
     *
     * @param AccountExtData $accountData
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function updateAccountItems(AccountExtData $accountData)
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
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param AccountExtData $accountData
     * @throws \SP\Core\Exceptions\SPException
     */
    public function editAccountPass(AccountExtData $accountData)
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
        $Data->setOnErrorMessage(__('Error al actualizar la clave', false));

        DbWrapper::getQuery($Data);
    }

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param int $accountId
     * @param int $historyId El Id del registro en el histórico
     * @throws \SP\Core\Exceptions\SPException
     */
    public function editAccountRestore($historyId, $accountId)
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
        $Data->setOnErrorMessage(__('Error al restaurar cuenta', false));

        DbWrapper::getQuery($Data);
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param array|int $id
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

        DbWrapper::getQuery($Data);

        return $Data->getQueryNumRows() === 1;
    }
}