<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Infrastructure\Account\Repositories;

use Aura\SqlQuery\QueryFactory;
use SP\Core\Context\ContextInterface;
use SP\Core\Events\EventDispatcherInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Account\Ports\AccountFilterUserInterface;
use SP\Domain\Account\Ports\AccountRepositoryInterface;
use SP\Domain\Account\Services\AccountPasswordRequest;
use SP\Domain\Common\Adapters\SimpleModel;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use function SP\__u;
use function SP\logger;

/**
 * Class AccountRepository
 *
 * @package Services
 */
final class AccountRepository extends Repository implements AccountRepositoryInterface
{
    use RepositoryItemTrait;

    private AccountFilterUserInterface $accountFilterUser;

    public function __construct(
        DatabaseInterface $database,
        ContextInterface $session,
        QueryFactory $queryFactory,
        EventDispatcherInterface $eventDispatcher,
        AccountFilterUserInterface $accountFilterUser
    ) {
        parent::__construct($database, $session, $eventDispatcher, $queryFactory);

        $this->accountFilterUser = $accountFilterUser;
    }

    /**
     * Devolver el número total de cuentas
     */
    public function getTotalNumAccounts(): SimpleModel
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['SUM(n) AS num'])
            ->fromSubSelect('SELECT COUNT(*) AS n FROM Account UNION SELECT COUNT(*) AS n FROM AccountHistory', 'a');

        return $this->db->doSelect(QueryData::build($query))->getData();
    }

    /**
     * @param  int  $id
     *
     * @return QueryResult
     */
    public function getPasswordForId(int $id): QueryResult
    {
        $query = $this->accountFilterUser
            ->buildFilter()
            ->cols([
                'Account.id,',
                'Account.name',
                'Account.login',
                'Account.pass',
                'Account.key',
                'Account.parentId',
            ])
            ->where('Account.id = :id')
            ->bindValues(['id' => $id])
            ->limit(1);

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * @param  int  $id
     *
     * @return QueryResult
     */
    public function getPasswordHistoryForId(int $id): QueryResult
    {
        $query = $this->accountFilterUser
            ->buildFilterHistory()
            ->cols([
                'AccountHistory.id,',
                'AccountHistory.name',
                'AccountHistory.login',
                'AccountHistory.pass',
                'AccountHistory.key',
                'AccountHistory.parentId',
                'AccountHistory.mPassHash',
            ])
            ->where('AccountHistory.id = :id')
            ->bindValues(['id' => $id]);

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @param  int  $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementDecryptCounter(int $id): bool
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->set('countDecrypt', '(countDecrypt + 1)')
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        return $this->db->doQuery(QueryData::build($query))->getAffectedNumRows() === 1;
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param  \SP\Domain\Account\Dtos\AccountRequest  $accountRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(AccountRequest $accountRequest): int
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into('Account')
            ->cols([
                'clientId'       => $accountRequest->clientId,
                'categoryId'     => $accountRequest->categoryId,
                'name'           => $accountRequest->name,
                'login'          => $accountRequest->login,
                'url'            => $accountRequest->url,
                'pass'           => $accountRequest->pass,
                'key'            => $accountRequest->key,
                'notes'          => $accountRequest->notes,
                'userId'         => $accountRequest->userId,
                'userGroupId'    => $accountRequest->userGroupId,
                'userEditId'     => $accountRequest->userId,
                'isPrivate'      => $accountRequest->isPrivate,
                'isPrivateGroup' => $accountRequest->isPrivateGroup,
                'passDateChange' => $accountRequest->passDateChange,
                'parentId'       => $accountRequest->parentId,
            ])
            ->set('dateAdd', 'NOW()')
            ->set('passDate', 'UNIX_TIMESTAMP()');

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the account'));

        return $this->db->doQuery($queryData)->getLastId();
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param  \SP\Domain\Account\Dtos\AccountRequest  $accountRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function editPassword(AccountRequest $accountRequest): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->cols([
                'pass'           => $accountRequest->pass,
                'key'            => $accountRequest->key,
                'userEditId'     => $accountRequest->userEditId,
                'passDateChange' => $accountRequest->passDateChange,
            ])
            ->set('dateEdit', 'NOW()')
            ->set('passDate', 'UNIX_TIMESTAMP()')
            ->where('id = :id')
            ->bindValues(['id' => $accountRequest->id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param  AccountPasswordRequest  $request
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassword(AccountPasswordRequest $request): bool
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->cols(
                [
                    'pass' => $request->getEncryptedPassword()->getPass(),
                    'key'  => $request->getEncryptedPassword()->getKey(),
                ]
            )
            ->where('id = :id')
            ->bindValues(['id' => $request->getId()]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param  \SP\DataModel\AccountHistoryData  $accountHistoryData
     * @param  int  $userId  User's Id
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function editRestore(AccountHistoryData $accountHistoryData, int $userId): bool
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->cols([
                'clientId'       => $accountHistoryData->getClientId(),
                'categoryId'     => $accountHistoryData->getCategoryId(),
                'name'           => $accountHistoryData->getName(),
                'login'          => $accountHistoryData->getLogin(),
                'url'            => $accountHistoryData->getUrl(),
                'notes'          => $accountHistoryData->getNotes(),
                'userGroupId'    => $accountHistoryData->getUserGroupId(),
                'userEditId'     => $userId,
                'pass'           => $accountHistoryData->getPass(),
                'key'            => $accountHistoryData->getKey(),
                'passDate'       => $accountHistoryData->getPassDate(),
                'passDateChange' => $accountHistoryData->getPassDateChange(),
                'parentId'       => $accountHistoryData->getParentId(),
                'isPrivate'      => $accountHistoryData->getIsPrivate(),
                'isPrivateGroup' => $accountHistoryData->getIsPrivateGroup(),
            ])
            ->set('dateEdit', 'NOW()')
            ->where('id = :id')
            ->bindValues(['id' => $accountHistoryData->getAccountId()]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error on restoring the account'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param  int  $id
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function delete(int $id): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('Account')
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the account'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Updates an item
     *
     * @param  AccountRequest  $accountRequest
     *
     * @return int
     * @throws SPException
     */
    public function update(AccountRequest $accountRequest): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->where('id = :id')
            ->cols([
                'clientId'       => $accountRequest->clientId,
                'categoryId'     => $accountRequest->categoryId,
                'name'           => $accountRequest->name,
                'login'          => $accountRequest->login,
                'url'            => $accountRequest->url,
                'notes'          => $accountRequest->notes,
                'userEditId'     => $accountRequest->userEditId,
                'passDateChange' => $accountRequest->passDateChange,
                'isPrivate'      => $accountRequest->isPrivate,
                'isPrivateGroup' => $accountRequest->isPrivateGroup,
                'parentId'       => $accountRequest->parentId,
            ])
            ->set('dateEdit', 'NOW()')
            ->bindValues(['id' => $accountRequest->id]);

        if ($accountRequest->changeUserGroup) {
            $query->col('userGroupId', $accountRequest->userGroupId);
        }

        if ($accountRequest->changeOwner) {
            $query->col('userId', $accountRequest->userId);
        }

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the account'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Updates an item for bulk action
     *
     * @param  \SP\Domain\Account\Dtos\AccountRequest  $itemData
     *
     * @return int
     * @throws SPException
     */
    public function updateBulk(AccountRequest $itemData): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->cols([
                'userEditId' => $itemData->userEditId,
            ])
            ->set('dateEdit', 'NOW()')
            ->where('id = :id')
            ->bindValues(['id' => $itemData->id]);

        $optional = ['clientId', 'categoryId', 'userId', 'userGroupId', 'passDateChange'];

        $optionalCount = 0;

        foreach ($optional as $field) {
            if (!empty($itemData->{$field})) {
                $query->col($field, $itemData->{$field});
                $optionalCount++;
            } else {
                logger(sprintf('Field \'%s\' not found in $itemData', $field), 'ERROR');
            }
        }

        if ($optionalCount === 0) {
            return 0;
        }

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the account'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Returns the item for given id
     *
     * @param  int  $id
     *
     * @return QueryResult
     */
    public function getById(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('account_data_v')
            ->cols([
                'id',
                'name',
                'categoryId',
                'userId',
                'clientId',
                'userGroupId',
                'userEditId',
                'login',
                'url',
                'notes',
                'countView',
                'countDecrypt',
                'dateAdd',
                'dateEdit',
                'otherUserEdit',
                'otherUserGroupEdit',
                'isPrivate',
                'isPrivateGroup',
                'passDate',
                'passDateChange',
                'parentId',
                'categoryName',
                'clientName',
                'userGroupName',
                'userName',
                'userLogin',
                'userEditName',
                'userEditLogin',
                'publicLinkHash',
            ])
            ->where('id = :id')
            ->bindValues(['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while retrieving account\'s data'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('Account')
            ->cols([
                'id',
                'name',
                'categoryId',
                'userId',
                'clientId',
                'userGroupId',
                'userEditId',
                'login',
                'url',
                'notes',
                'countView',
                'countDecrypt',
                'dateAdd',
                'dateEdit',
                'otherUserEdit',
                'otherUserGroupEdit',
                'isPrivate',
                'isPrivateGroup',
                'passDate',
                'passDateChange',
                'parentId',
            ]);

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param  array  $ids
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from('Account')
            ->where(sprintf('id IN (%s)', $this->buildParamsFromArray($ids)), ...$ids);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the accounts'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Searches for items by a given filter
     *
     * @param  ItemSearchData  $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('account_search_v')
            ->cols([
                'id',
                'name',
                'clientName',
                'categoryName',
                'userName',
                'userGroupName',

            ])
            ->orderBy(['name ASC', 'clientName ASC'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('name LIKE :name')
                ->orWhere('clientName LIKE :clientName')
                ->orWhere('categoryName LIKE :categoryName')
                ->orWhere('userName LIKE :userName')
                ->orWhere('userGroupName LIKE :userGroupName');

            $search = '%'.$itemSearchData->getSeachString().'%';

            $query->bindValues([
                'name'          => $search,
                'clientName'    => $search,
                'categoryName'  => $search,
                'userName'      => $search,
                'userGroupName' => $search,
            ]);
        }

        return $this->db->doSelect(QueryData::build($query), true);
    }

    /**
     * Incrementa el contador de visitas de una cuenta en la BBDD
     *
     * @param  int  $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementViewCounter(int $id): bool
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->set('countView', '(countView + 1)')
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        return $this->db->doQuery(QueryData::build($query))->getAffectedNumRows() === 1;
    }

    /**
     * Obtener los datos de una cuenta.
     *
     * @param  int  $id
     *
     * @return QueryResult
     */
    public function getDataForLink(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('Account')
            ->join('INNER', 'Client', 'Account.clientId = Client.id')
            ->join('INNER', 'Category', 'Account.categoryId = Category.id')
            ->cols([
                'Account.name',
                'Account.login',
                'Account.pass',
                'Account.key',
                'Account.url',
                'Account.notes',
                'Client.name AS clientName',
                'Category.name AS categoryName',
            ])
            ->where('Account.id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while retrieving account\'s data'));

        return $this->db->doSelect($queryData);
    }

    /**
     * @param  int|null  $accountId
     *
     * @return QueryResult
     */
    public function getForUser(?int $accountId = null): QueryResult
    {
        $query = $this->accountFilterUser
            ->buildFilter()
            ->cols(
                [
                    'Account.id',
                    'Account.name',
                    'C.name AS clientName',
                ]
            )
            ->join('LEFT', 'Client AS C', 'Account.clientId = C.id')
            ->orderBy(['Account.name ASC']);

        if ($accountId) {
            $query
                ->where('Account.id <> :id')
                ->where('Account.parentId = 0 OR Account.parentId IS NULL')
                ->bindValues(['id' => $accountId]);
        }

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * @param  int  $accountId
     *
     * @return QueryResult
     */
    public function getLinked(int $accountId): QueryResult
    {
        $query = $this->accountFilterUser
            ->buildFilter()
            ->cols(
                [
                    'Account.id',
                    'Account.name',
                    'Client.name AS clientName',
                ]
            )
            ->join('INNER', 'Client', 'Account.clientId = Client.id')
            ->where('Account.parentId = :parentId')
            ->bindValues(['parentId' => $accountId])
            ->orderBy(['Account.name ASC']);

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return \SP\Infrastructure\Database\QueryResult
     */
    public function getAccountsPassData(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('Account')
            ->cols(
                [
                    'id',
                    'name',
                    'pass',
                    'key',
                ]
            )
            ->where('BIT_LENGTH(pass) > 0');

        return $this->db->doSelect(QueryData::build($query));
    }
}
