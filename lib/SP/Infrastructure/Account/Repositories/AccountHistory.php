<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
 */

declare(strict_types=1);

namespace SP\Infrastructure\Account\Repositories;

use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Ports\AccountHistoryRepository;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use function SP\__u;

/**
 * Class AccountHistory
 */
final class AccountHistory extends BaseRepository implements AccountHistoryRepository
{
    use RepositoryItemTrait;

    /**
     * Obtiene el listado del histórico de una cuenta.
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getHistoryForAccount(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(sprintf('%s AS Account', AccountHistoryModel::TABLE))
            ->cols([
                       'Account.id',
                       'Account.dateEdit',
                       'Account.dateAdd',
                       'User.login AS userAdd',
                       'UserEdit.login AS userEdit',
                   ])
            ->join('INNER', 'User as User', 'Account.userId = User.id')
            ->join('LEFT', 'User as UserEdit', 'Account.userEditId = UserEdit.id')
            ->where('Account.accountId = :id')
            ->bindValues(['id' => $id])
            ->orderBy(['Account.id DESC']);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param AccountHistoryCreateDto $dto
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(AccountHistoryCreateDto $dto): int
    {
        $accountData = $dto->getAccount();

        $query = $this->queryFactory
            ->newInsert()
            ->into(AccountHistoryModel::TABLE)
            ->cols([
                       'accountId' => $accountData->getId(),
                       'clientId' => $accountData->getClientId(),
                       'categoryId' => $accountData->getCategoryId(),
                       'name' => $accountData->getName(),
                       'login' => $accountData->getLogin(),
                       'url' => $accountData->getUrl(),
                       'pass' => $accountData->getPass(),
                       'key' => $accountData->getKey(),
                       'notes' => $accountData->getNotes() ?? '',
                       'userId' => $accountData->getUserId(),
                       'userGroupId' => $accountData->getUserGroupId(),
                       'userEditId' => $accountData->getUserEditId(),
                       'isPrivate' => $accountData->getIsPrivate(),
                       'isPrivateGroup' => $accountData->getIsPrivateGroup(),
                       'passDate' => $accountData->getPassDate(),
                       'passDateChange' => $accountData->getPassDateChange(),
                       'parentId' => $accountData->getParentId(),
                       'countView' => $accountData->getCountView(),
                       'countDecrypt' => $accountData->getCountDecrypt(),
                       'dateAdd' => $accountData->getDateAdd(),
                       'dateEdit' => $accountData->getDateEdit(),
                       'otherUserEdit' => $accountData->getOtherUserEdit(),
                       'otherUserGroupEdit' => $accountData->getOtherUserGroupEdit(),
                       'isModify' => $dto->isModify(),
                       'isDeleted' => $dto->isDelete(),
                       'mPassHash' => $dto->getMasterPassHash(),
                   ]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating history'));

        return $this->db->runQuery($queryData)->getLastId();
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param int $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(AccountHistoryModel::TABLE)
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the account'));

        return $this->db->runQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(sprintf('%s AS Account', AccountHistoryModel::TABLE))
            ->cols([
                       'Account.id',
                       'Account.accountId',
                       'Account.name',
                       'Account.categoryId',
                       'Account.userId',
                       'Account.clientId',
                       'Account.userGroupId',
                       'Account.userEditId',
                       'Account.login',
                       'Account.url',
                       'Account.pass',
                       'Account.`key`',
                       'Account.notes',
                       'Account.countView',
                       'Account.countDecrypt',
                       'Account.dateAdd',
                       'Account.dateEdit',
                       'Account.otherUserEdit',
                       'Account.otherUserGroupEdit',
                       'Account.isPrivate',
                       'Account.isPrivateGroup',
                       'Account.passDate',
                       'Account.passDateChange',
                       'Account.parentId',
                       'Account.isModify',
                       'Account.isDeleted',
                       'Account.mPassHash',
                       'User.name AS userName',
                       'UserGroup.name AS userGroupName',
                       'UserEdit.name AS userEditName',
                       'UserEdit.login AS userEditLogin',
                   ])
            ->join('LEFT', 'User AS User', 'Account.userId = User.id')
            ->join('LEFT', 'UserGroup', 'Account.userGroupId = UserGroup.id')
            ->join('LEFT', 'User AS UserEdit', 'Account.userEditId = UserEdit.id')
            ->where('Account.id = :id')
            ->bindValues(['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)
                              ->setMapClassName(AccountHistoryModel::class)
                              ->setOnErrorMessage(__u('Error while retrieving account\'s data'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns all the items matching the given ids
     *
     * @param int[] $ids
     *
     * @return QueryResult<AccountHistoryModel>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByIds(array $ids): QueryResult
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));

        if ($normalizedIds === []) {
            /** @var QueryResult<AccountHistoryModel> $empty */
            $empty = new QueryResult();

            return $empty;
        }

        $placeholders = [];
        $binds = [];

        foreach ($normalizedIds as $index => $id) {
            $param = 'id' . $index;
            $placeholders[] = ':' . $param;
            $binds[$param] = $id;
        }

        $query = $this->queryFactory
            ->newSelect()
            ->from(sprintf('%s AS Account', AccountHistoryModel::TABLE))
            ->cols([
                       'Account.id',
                       'Account.accountId',
                       'Account.name',
                       'Account.categoryId',
                       'Account.userId',
                       'Account.clientId',
                       'Account.userGroupId',
                       'Account.userEditId',
                       'Account.login',
                       'Account.url',
                       'Account.pass',
                       'Account.`key`',
                       'Account.notes',
                       'Account.countView',
                       'Account.countDecrypt',
                       'Account.dateAdd',
                       'Account.dateEdit',
                       'Account.otherUserEdit',
                       'Account.otherUserGroupEdit',
                       'Account.isPrivate',
                       'Account.isPrivateGroup',
                       'Account.passDate',
                       'Account.passDateChange',
                       'Account.parentId',
                       'Account.isModify',
                       'Account.isDeleted',
                       'Account.mPassHash',
                   ])
            ->where(sprintf('Account.id IN (%s)', implode(', ', $placeholders)))
            ->bindValues($binds);

        $queryData = QueryData::build($query)
                              ->setMapClassName(AccountHistoryModel::class)
                              ->setOnErrorMessage(__u('Error while retrieving account\'s data'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns all history entries for a given account id ordered newest first
     *
     * @return QueryResult<AccountHistoryModel>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByAccountId(int $accountId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(sprintf('%s AS Account', AccountHistoryModel::TABLE))
            ->cols([
                       'Account.id',
                       'Account.accountId',
                       'Account.name',
                       'Account.categoryId',
                       'Account.userId',
                       'Account.clientId',
                       'Account.userGroupId',
                       'Account.userEditId',
                       'Account.login',
                       'Account.url',
                       'Account.pass',
                       'Account.`key`',
                       'Account.notes',
                       'Account.countView',
                       'Account.countDecrypt',
                       'Account.dateAdd',
                       'Account.dateEdit',
                       'Account.otherUserEdit',
                       'Account.otherUserGroupEdit',
                       'Account.isPrivate',
                       'Account.isPrivateGroup',
                       'Account.passDate',
                       'Account.passDateChange',
                       'Account.parentId',
                       'Account.isModify',
                       'Account.isDeleted',
                       'Account.mPassHash',
                   ])
            ->where('Account.accountId = :accountId')
            ->bindValues(['accountId' => $accountId])
            ->orderBy(['Account.id DESC']);

        $queryData = QueryData::build($query)
                              ->setMapClassName(AccountHistoryModel::class)
                              ->setOnErrorMessage(__u('Error while retrieving account\'s data'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Reassigns history rows matching oldAccountId and dateAdd to a new account id
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function reassignAccountId(int $oldAccountId, int $newAccountId, string $dateAdd): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(AccountHistoryModel::TABLE)
            ->cols(['accountId' => $newAccountId])
            ->where('accountId = :oldAccountId')
            ->where('dateAdd = :dateAdd')
            ->bindValues([
                             'oldAccountId' => $oldAccountId,
                             'dateAdd' => $dateAdd,
                         ]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating history'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(sprintf('%s AS Account', AccountHistoryModel::TABLE))
            ->cols([
                       'Account.id',
                       'Account.accountId',
                       'Account.name',
                       'Account.categoryId',
                       'Account.userId',
                       'Account.clientId',
                       'Account.userGroupId',
                       'Account.userEditId',
                       'Account.login',
                       'Account.url',
                       'Account.notes',
                       'Account.countView',
                       'Account.countDecrypt',
                       'Account.dateAdd',
                       'Account.dateEdit',
                       'Account.otherUserEdit',
                       'Account.otherUserGroupEdit',
                       'Account.isPrivate',
                       'Account.isPrivateGroup',
                       'Account.passDate',
                       'Account.passDateChange',
                       'Account.parentId',
                       'Account.isModify',
                       'Account.isDeleted',
                       'Account.mPassHash',
                       'Category.name AS categoryName',
                       'User.name AS userName',
                       'Client.name AS clientName',
                       'UserGroup.name AS userGroupName',
                       'UserEdit.name AS userEditName',
                       'UserEdit.login AS userEditLogin',
                   ])
            ->join('INNER', 'Category', 'Account.categoryId = Category.id')
            ->join('INNER', 'Client', 'Account.clientId = Client.id')
            ->join('INNER', 'UserGroup', 'Account.userGroupId = UserGroup.id')
            ->join('INNER', 'User', 'Account.userId = User.id')
            ->join('LEFT', 'User AS UserEdit', 'Account.userEditId = UserEdit.id')
            ->orderBy(['Account.id DESC']);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));

        if ($normalizedIds === []) {
            return 0;
        }

        $placeholders = [];
        $binds = [];

        foreach ($normalizedIds as $index => $id) {
            $param = 'id' . $index;
            $placeholders[] = ':' . $param;
            $binds[$param] = $id;
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(AccountHistoryModel::TABLE)
            ->where(sprintf('id IN (%s)', implode(', ', $placeholders)))
            ->bindValues($binds);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the accounts'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes all the items for given accounts id
     *
     * @param array $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountIdBatch(array $ids): int
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn(int $id): bool => $id > 0
        )));

        if ($normalizedIds === []) {
            return 0;
        }

        $placeholders = [];
        $binds = [];

        foreach ($normalizedIds as $index => $id) {
            $param = 'id' . $index;
            $placeholders[] = ':' . $param;
            $binds[$param] = $id;
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(AccountHistoryModel::TABLE)
            ->where(sprintf('accountId IN (%s)', implode(', ', $placeholders)))
            ->bindValues($binds);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the accounts'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        $userData = $this->context->getUserData();
        $isAdmin = $userData->isAdminApp || $userData->isAdminAcc;

        $query = $this->queryFactory
            ->newSelect()
            ->from(sprintf('%s AS Account', AccountHistoryModel::TABLE))
            ->cols([
                       'Account.id',
                       'Account.name',
                       'Client.name AS clientName',
                       'Category.name AS categoryName',
                       'Account.isModify',
                       'Account.isDeleted',
                       'IFNULL(Account.dateEdit,Account.dateAdd) as date',
                   ])
            ->join('INNER', 'Category', 'Account.categoryId = Category.id')
            ->join('INNER', 'Client', 'Account.clientId = Client.id')
            ->join('LEFT', sprintf('%s AS LiveAcc', AccountModel::TABLE), 'LiveAcc.id = Account.accountId')
            ->orderBy(['IFNULL(Account.dateEdit,Account.dateAdd) DESC', 'clientName ASC', 'Account.id DESC'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        $bindValues = [];

        if ($isAdmin) {
            $liveBranch = 'LiveAcc.id IS NOT NULL';
            $deletedBranch = '(LiveAcc.id IS NULL'
                . ' AND (SELECT COUNT(*) FROM AccountHistory DelCnt WHERE DelCnt.accountId = Account.accountId AND DelCnt.isDeleted = 1) = 1'
                . ' AND EXISTS (SELECT 1 FROM AccountHistory DelSnap WHERE DelSnap.accountId = Account.accountId AND DelSnap.isDeleted = 1'
                . ' AND (DelSnap.parentId > 0 OR (DelSnap.pass IS NOT NULL AND BIT_LENGTH(DelSnap.pass) > 0 AND DelSnap.key IS NOT NULL AND BIT_LENGTH(DelSnap.key) > 0))))';
        } else {
            $liveBranch = '(LiveAcc.id IS NOT NULL'
                . ' AND (LiveAcc.isPrivate IS NULL OR LiveAcc.isPrivate = 0 OR (LiveAcc.isPrivate = 1 AND LiveAcc.userId = :shLivePrivUserId))'
                . ' AND (LiveAcc.isPrivateGroup IS NULL OR LiveAcc.isPrivateGroup = 0 OR (LiveAcc.isPrivateGroup = 1 AND LiveAcc.userGroupId = :shLivePrivGrpId))'
                . ' AND (LiveAcc.userId = :shLiveOwnerId'
                . ' OR LiveAcc.userGroupId = :shLiveGroupId'
                . ' OR LiveAcc.id IN (SELECT accountId FROM AccountToUser WHERE accountId = LiveAcc.id AND userId = :shLiveAtuUserId'
                . ' UNION ALL SELECT accountId FROM AccountToUserGroup WHERE accountId = LiveAcc.id AND userGroupId = :shLiveAtugGroupId)'
                . ' OR LiveAcc.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = LiveAcc.userGroupId AND userId = :shLiveUtugUserId)'
                . ' OR EXISTS (SELECT 1 FROM AccountToUserGroup aug INNER JOIN UserToUserGroup uug ON uug.userGroupId = aug.userGroupId WHERE aug.accountId = LiveAcc.id AND uug.userId = :shLiveFullGrpUserId)))';

            $deletedBranch = '(LiveAcc.id IS NULL'
                . ' AND (SELECT COUNT(*) FROM AccountHistory DelCnt WHERE DelCnt.accountId = Account.accountId AND DelCnt.isDeleted = 1) = 1'
                . ' AND EXISTS (SELECT 1 FROM AccountHistory DelSnap WHERE DelSnap.accountId = Account.accountId AND DelSnap.isDeleted = 1'
                . ' AND (DelSnap.parentId > 0 OR (DelSnap.pass IS NOT NULL AND BIT_LENGTH(DelSnap.pass) > 0 AND DelSnap.key IS NOT NULL AND BIT_LENGTH(DelSnap.key) > 0))'
                . ' AND (DelSnap.isPrivate IS NULL OR DelSnap.isPrivate = 0 OR (DelSnap.isPrivate = 1 AND DelSnap.userId = :shDelPrivUserId))'
                . ' AND (DelSnap.isPrivateGroup IS NULL OR DelSnap.isPrivateGroup = 0 OR (DelSnap.isPrivateGroup = 1 AND DelSnap.userGroupId = :shDelPrivGrpId))'
                . ' AND (DelSnap.userId = :shDelOwnerId'
                . ' OR DelSnap.userGroupId = :shDelGroupId'
                . ' OR DelSnap.userGroupId IN (SELECT userGroupId FROM UserToUserGroup WHERE userGroupId = DelSnap.userGroupId AND userId = :shDelUtugUserId))))';

            $bindValues = [
                'shLivePrivUserId' => $userData->id,
                'shLivePrivGrpId' => $userData->userGroupId,
                'shLiveOwnerId' => $userData->id,
                'shLiveGroupId' => $userData->userGroupId,
                'shLiveAtuUserId' => $userData->id,
                'shLiveAtugGroupId' => $userData->userGroupId,
                'shLiveUtugUserId' => $userData->id,
                'shLiveFullGrpUserId' => $userData->id,
                'shDelPrivUserId' => $userData->id,
                'shDelPrivGrpId' => $userData->userGroupId,
                'shDelOwnerId' => $userData->id,
                'shDelGroupId' => $userData->userGroupId,
                'shDelUtugUserId' => $userData->id,
            ];
        }

        $query->where(sprintf('(%s OR %s)', $liveBranch, $deletedBranch));

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('(Account.name LIKE :name OR Client.name LIKE :clientName)');

            $search = '%' . $itemSearchData->getSeachString() . '%';
            $bindValues['name'] = $search;
            $bindValues['clientName'] = $search;
        }

        if ($bindValues !== []) {
            $query->bindValues($bindValues);
        }

        return $this->db->runQuery(QueryData::build($query), true);
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAccountsPassData(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(AccountHistoryModel::TABLE)
            ->cols(
                [
                    'id',
                    'name',
                    'pass',
                    '`key`',
                    'mPassHash',
                ]
            )
            ->where('BIT_LENGTH(pass) > 0')
            ->orderBy(['id ASC']);

        return $this->db->runQuery(QueryData::build($query));
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param int $accountId
     * @param EncryptedPassword $encryptedPassword
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassword(int $accountId, EncryptedPassword $encryptedPassword): bool
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(AccountHistoryModel::TABLE)
            ->cols([
                       'pass' => $encryptedPassword->getPass(),
                       'key' => $encryptedPassword->getKey(),
                       'mPassHash' => $encryptedPassword->getHash(),
                   ])
            ->where('id = :id')
            ->bindValues(['id' => $accountId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->runQuery($queryData)->getAffectedNumRows() === 1;
    }
}
