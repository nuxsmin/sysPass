<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountSearchView as AccountSearchViewModel;
use SP\Domain\Account\Models\AccountView as AccountViewModel;
use SP\Domain\Account\Ports\AccountFilterBuilder;
use SP\Domain\Account\Ports\AccountRepository;
use SP\Domain\Core\Context\ContextInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\DatabaseInterface;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class AccountRepository
 *
 * @package Services
 */
final class Account extends BaseRepository implements AccountRepository
{
    use RepositoryItemTrait;

    public function __construct(
        DatabaseInterface                     $database,
        ContextInterface                      $session,
        QueryFactory                          $queryFactory,
        EventDispatcherInterface              $eventDispatcher,
        private readonly AccountFilterBuilder $accountFilterUser
    ) {
        parent::__construct($database, $session, $eventDispatcher, $queryFactory);
    }

    /**
     * Devolver el número total de cuentas
     */
    public function getTotalNumAccounts(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['SUM(n) AS num'])
            ->fromSubSelect('SELECT COUNT(*) AS n FROM Account UNION SELECT COUNT(*) AS n FROM AccountHistory', 'a');

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * @param int $accountId
     *
     * @return QueryResult
     */
    public function getPasswordForId(int $accountId): QueryResult
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
            ->bindValues(['id' => $accountId])
            ->limit(1);

        return $this->db->doSelect(QueryData::build($query)->setMapClassName(AccountModel::class));
    }

    /**
     * @param int $accountId
     *
     * @return QueryResult
     */
    public function getPasswordHistoryForId(int $accountId): QueryResult
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
            ->where('AccountHistory.accountId = :accountId')
            ->bindValues(['accountId' => $accountId]);

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * Incrementa el contador de vista de clave de una cuenta en la BBDD
     *
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementDecryptCounter(int $accountId): QueryResult
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->set('countDecrypt', '(countDecrypt + 1)')
            ->where('id = :id')
            ->bindValues(['id' => $accountId]);

        return $this->db->doQuery(QueryData::build($query));
    }

    /**
     * Crea una nueva cuenta en la BBDD
     *
     * @param AccountModel $account
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(AccountModel $account): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into('Account')
            ->cols($account->toArray(null, ['countDecrypt', 'countView', 'dateAdd', 'dateEdit', 'id']))
            ->set('dateAdd', 'NOW()')
            ->set('passDate', 'UNIX_TIMESTAMP()');

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the account'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Create an account from deleted
     *
     * @param AccountModel $account
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function createRemoved(AccountModel $account): QueryResult
    {
        $query = $this->queryFactory
            ->newInsert()
            ->into('Account')
            ->cols($account->toArray(null, ['id']));

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the account'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param int $accountId
     * @param AccountModel $account
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function editPassword(int $accountId, AccountModel $account): QueryResult
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->cols($account->toArray(['pass', 'key', 'userEditId', 'passDateChange']))
            ->set('dateEdit', 'NOW()')
            ->set('passDate', 'UNIX_TIMESTAMP()')
            ->where('id = :id')
            ->bindValues(['id' => $accountId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Actualiza la clave de una cuenta en la BBDD.
     *
     * @param int $accountId
     * @param EncryptedPassword $encryptedPassword
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassword(int $accountId, EncryptedPassword $encryptedPassword): QueryResult
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->cols(['pass' => $encryptedPassword->getPass(), 'key' => $encryptedPassword->getKey()])
            ->where('id = :id')
            ->bindValues(['id' => $accountId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Restaurar una cuenta desde el histórico.
     *
     * @param int $accountId
     * @param AccountModel $account
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function restoreModified(int $accountId, AccountModel $account): QueryResult
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->cols(
                $account->toArray(
                    null,
                    [
                        'passDate',
                        'dateAdd',
                        'countDecrypt',
                        'countView',
                        'dateEdit',
                        'id',
                    ]
                )
            )
            ->set('dateEdit', 'NOW()')
            ->where('id = :id')
            ->bindValues(['id' => $accountId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error on restoring the account'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Elimina los datos de una cuenta en la BBDD.
     *
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $accountId): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('Account')
            ->where('id = :id')
            ->bindValues(['id' => $accountId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the account'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Updates an item
     *
     * @param int $accountId
     * @param AccountModel $account
     * @param bool $changeOwner
     * @param bool $changeUserGroup
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(int $accountId, AccountModel $account, bool $changeOwner, bool $changeUserGroup): QueryResult
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->where('id = :id')
            ->cols(
                $account->toArray(
                    null,
                    [
                        'passDate',
                        'dateAdd',
                        'key',
                        'pass',
                        'countDecrypt',
                        'countView',
                        'dateEdit',
                        'userGroupId',
                        'userId',
                        'id',
                    ]
                )
            )
            ->set('dateEdit', 'NOW()')
            ->bindValues(['id' => $accountId]);

        if ($changeUserGroup) {
            $query->col('userGroupId', $account->getUserGroupId());
        }

        if ($changeOwner) {
            $query->col('userId', $account->getUserId());
        }

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the account'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Updates an item for bulk action
     *
     * @param int $accountId
     * @param AccountModel $account
     * @param bool $changeOwner
     * @param bool $changeUserGroup
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateBulk(
        int          $accountId,
        AccountModel $account,
        bool         $changeOwner,
        bool         $changeUserGroup
    ): QueryResult {
        $optional = ['clientId', 'categoryId', 'passDateChange'];

        if ($changeOwner) {
            $optional[] = 'userId';
        }

        if ($changeUserGroup) {
            $optional[] = 'userGroupId';
        }

        $cols = array_filter($account->toArray($optional), static fn($value) => !empty($value));

        if (count($cols) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->cols(
                array_merge(
                    $cols,
                    ['userEditId' => $account->getUserEditId(),]
                )
            )
            ->set('dateEdit', 'NOW()')
            ->where('id = :id')
            ->bindValues(['id' => $accountId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the account'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Returns the item for given id with referential data
     *
     * @param int $accountId
     *
     * @return QueryResult
     */
    public function getByIdEnriched(int $accountId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('account_data_v')
            ->cols(AccountViewModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $accountId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, AccountViewModel::class)
                              ->setOnErrorMessage(__u('Error while retrieving account\'s data'));

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $accountId
     *
     * @return QueryResult
     */
    public function getById(int $accountId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('Account')
            ->cols(AccountModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $accountId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, AccountModel::class)
                              ->setOnErrorMessage(__u('Error while retrieving account\'s data'));

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
            ->cols(AccountModel::getCols(['pass', 'key']));

        return $this->db->doSelect(QueryData::buildWithMapper($query, AccountModel::class));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $accountsId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $accountsId): QueryResult
    {
        if (count($accountsId) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from('Account')
            ->where('id IN (:ids)', ['ids' => $accountsId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the accounts'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('account_search_v')
            ->cols(AccountSearchViewModel::getCols())
            ->orderBy(['name ASC', 'clientName ASC'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('name LIKE :name')
                  ->orWhere('clientName LIKE :clientName')
                  ->orWhere('categoryName LIKE :categoryName')
                  ->orWhere('userName LIKE :userName')
                  ->orWhere('userGroupName LIKE :userGroupName');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues([
                                   'name' => $search,
                                   'clientName' => $search,
                                   'categoryName' => $search,
                                   'userName' => $search,
                                   'userGroupName' => $search,
                               ]);
        }

        return $this->db->doSelect(QueryData::buildWithMapper($query, AccountSearchViewModel::class), true);
    }

    /**
     * Incrementa el contador de visitas de una cuenta en la BBDD
     *
     * @param int $accountId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function incrementViewCounter(int $accountId): QueryResult
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('Account')
            ->set('countView', '(countView + 1)')
            ->where('id = :id')
            ->bindValues(['id' => $accountId]);

        return $this->db->doQuery(QueryData::build($query));
    }

    /**
     * Obtener los datos de una cuenta.
     *
     * @param int $accountId
     *
     * @return QueryResult
     */
    public function getDataForLink(int $accountId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('Account')
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
            ->join('INNER', 'Client', 'Account.clientId = Client.id')
            ->join('INNER', 'Category', 'Account.categoryId = Category.id')
            ->where('Account.id = :id')
            ->bindValues(['id' => $accountId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while retrieving account\'s data'));

        return $this->db->doSelect($queryData);
    }

    /**
     * @param int|null $accountId
     *
     * @return QueryResult
     */
    public function getForUser(?int $accountId = null): QueryResult
    {
        $query = $this->accountFilterUser
            ->buildFilter()
            ->cols([
                       'Account.id',
                       'Account.name',
                       'Client.name AS clientName',
                   ])
            ->join('INNER', 'Client', 'Account.clientId = Client.id')
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
     * @param int $accountId
     *
     * @return QueryResult
     */
    public function getLinked(int $accountId): QueryResult
    {
        $query = $this->accountFilterUser
            ->buildFilter()
            ->cols([
                       'Account.id',
                       'Account.name',
                       'Client.name AS clientName',
                   ])
            ->join('INNER', 'Client', 'Account.clientId = Client.id')
            ->where('Account.parentId = :parentId')
            ->bindValues(['parentId' => $accountId])
            ->orderBy(['Account.name ASC']);

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * Obtener los datos relativos a la clave de todas las cuentas.
     *
     * @return QueryResult
     */
    public function getAccountsPassData(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('Account')
            ->cols([
                       'id',
                       'name',
                       'pass',
                       'key',
                   ])
            ->where('BIT_LENGTH(pass) > 0');

        return $this->db->doSelect(QueryData::buildWithMapper($query, AccountModel::class));
    }
}
