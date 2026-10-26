<?php
declare(strict_types=1);
/**
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

namespace SP\Infrastructure\User\Repositories;

use Exception;
use JsonException;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountToUser as AccountToUserModel;
use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Domain\Client\Models\Client as ClientModel;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Models\UserGroup as UserGroupModel;
use SP\Domain\User\Models\UserPreferences;
use SP\Domain\User\Models\UserProfile as UserProfileModel;
use SP\Domain\User\Models\UserToUserGroup as UserToUserGroupModel;
use SP\Domain\User\Ports\UserRepository;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class User
 *
 * @template T of UserModel
 */
final class User extends BaseRepository implements UserRepository
{
    /**
     * Updates an item
     *
     * @param UserModel $user
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function update(UserModel $user): int
    {
        if ($this->checkDuplicatedOnUpdate($user)) {
            throw DuplicatedItemException::error(__u('Duplicated user login/email'));
        }

        $query = $this->queryFactory
            ->newUpdate()
            ->table(UserModel::TABLE)
            ->cols($user->toArray(null, ['id', 'hashSalt']))
            ->set('lastUpdate', 'NOW()')
            ->where('id = :id', ['id' => $user->getId()])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the user'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param UserModel $user
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnUpdate(UserModel $user): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(UserModel::TABLE)
            ->where('id <> :id')
            ->where(
                'UPPER(:login) = UPPER(login) 
                OR (UPPER(:ssoLogin) = UPPER(ssoLogin) AND ssoLogin IS NOT NULL AND ssoLogin <> \'\'
                OR (UPPER(:email) = UPPER(email) AND email IS NOT NULL AND email <> \'\''
            )
            ->bindValues(
                [
                    'id' => $user->getId(),
                    'login' => $user->getLogin(),
                    'ssoLogin' => $user->getSsoLogin(),
                    'email' => $user->getEmail(),
                ]
            );

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Updates a user's pass
     *
     * @param UserModel $user
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updatePassById(UserModel $user): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(UserModel::TABLE)
            ->cols($user->toArray(['pass', 'isChangePass', 'isChangedPass', 'isMigrate']))
            ->set('lastUpdate', 'NOW()')
            ->set('hashSalt', '')
            ->where('id = :id', ['id' => $user->getId()])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the password'));

        return $this->db->runQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(UserModel::TABLE)
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the user'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult<T>
     * @throws QueryException
     * @throws ConstraintException
     * @throws Exception
     */
    public function getById(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserModel::getColsWithPreffix(UserModel::TABLE))
            ->cols(UserGroupModel::getColsWithPreffix(UserGroupModel::TABLE))
            ->from(UserModel::TABLE)
            ->innerJoin(
                UserGroupModel::TABLE,
                sprintf('%s.id = %s.userGroupId', UserGroupModel::TABLE, UserModel::TABLE)
            )
            ->where('User.id = :id')
            ->bindValues(['id' => $id])
            ->limit(1);

        return $this->db->runQuery(QueryData::buildWithMapper($query, UserModel::class));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $ids): QueryResult
    {
        if (count($ids) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(UserModel::TABLE)
            ->where('id IN (:ids)', ['ids' => $ids]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while deleting the users'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult
     * @throws QueryException
     * @throws ConstraintException
     * @throws Exception
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserModel::TABLE)
            ->innerJoin(
                UserGroupModel::TABLE,
                sprintf('%s.id = %s.userGroupId', UserGroupModel::TABLE, UserModel::TABLE)
            )
            ->innerJoin(
                UserProfileModel::TABLE,
                sprintf('%s.id = %s.userProfileId', UserProfileModel::TABLE, UserModel::TABLE)
            )
            ->cols(UserModel::getCols(['hash']))
            ->cols(UserGroupModel::getColsWithPreffix(UserGroupModel::TABLE))
            ->cols(UserProfileModel::getColsWithPreffix(UserProfileModel::TABLE))
            ->orderBy(['name'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!$this->context->getUserData()->isAdminApp) {
            $query->where(sprintf('%s.isAdminApp = 0', UserModel::TABLE));
        }

        if (!empty($itemSearchData->getSeachString())) {
            $query->where(sprintf('%s.name LIKE :name OR %s.login LIKE :login', UserModel::TABLE, UserModel::TABLE));

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['name' => $search, 'login' => $search]);
        }

        $queryData = QueryData::build($query)->setMapClassName(UserModel::class);

        return $this->db->runQuery($queryData, true);
    }

    /**
     * Creates an item
     *
     * @param UserModel $user
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function create(UserModel $user): QueryResult
    {
        if ($this->checkDuplicatedOnAdd($user)) {
            throw DuplicatedItemException::error(__u('Duplicated user login/email'));
        }

        $query = $this->queryFactory
            ->newInsert()
            ->into(UserModel::TABLE)
            ->cols($user->toArray(null, ['id', 'hashSalt']))
            ->set('hashSalt', '');

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the user'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param UserModel $user
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnAdd(UserModel $user): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(UserModel::TABLE)
            ->where(
                'UPPER(:login) = UPPER(login) 
                OR (UPPER(:ssoLogin) = UPPER(ssoLogin) AND ssoLogin IS NOT NULL AND ssoLogin <> \'\'
                OR (UPPER(:email) = UPPER(email) AND email IS NOT NULL AND email <> \'\''
            )
            ->bindValues(
                [
                    'login' => $user->getLogin(),
                    'ssoLogin' => $user->getSsoLogin(),
                    'email' => $user->getEmail(),
                ]
            );

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * @param $login string
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function getByLogin(string $login): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserModel::getColsWithPreffix(UserModel::TABLE))
            ->cols(UserGroupModel::getColsWithPreffix(UserGroupModel::TABLE))
            ->from(UserModel::TABLE)
            ->innerJoin(
                UserGroupModel::TABLE,
                sprintf('%s.id = %s.userGroupId', UserGroupModel::TABLE, UserModel::TABLE)
            )
            ->where(sprintf('%s.login = :login', UserModel::TABLE))
            ->orWhere(sprintf('%s.ssoLogin = :login', UserModel::TABLE))
            ->bindValues(['login' => $login])
            ->limit(1);

        return $this->db->runQuery(QueryData::buildWithMapper($query, UserModel::class));
    }

    /**
     * Returns items' basic information
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(UserModel::TABLE)
            ->cols(UserModel::getCols())
            ->orderBy(['name']);

        return $this->db->runQuery(QueryData::buildWithMapper($query, UserModel::class));
    }

    /**
     * Updates user's master password
     *
     * @param int $id
     * @param string $pass
     * @param string $key
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateMasterPassById(int $id, string $pass, string $key): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(UserModel::TABLE)
            ->cols(['pass' => $pass, 'key' => $key])
            ->set('lastUpdateMPass', 'UNIX_TIMESTAMP()')
            ->set('isMigrate', 0)
            ->set('isChangedPass', 0)
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        return $this->db->runQuery(QueryData::build($query))->getAffectedNumRows();
    }

    /**
     * Actualiza el último inicio de sesión del usuario en la BBDD.
     *
     * @param $id int El id del usuario
     *
     * @return int
     * @throws QueryException
     * @throws ConstraintException
     */
    public function updateLastLoginById(int $id): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(UserModel::TABLE)
            ->set('lastLogin', 'NOW()')
            ->set('loginCount', 'loginCount + 1')
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        return $this->db->runQuery(QueryData::build($query))->getAffectedNumRows();
    }

    /**
     * @param string $login
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function checkExistsByLogin(string $login): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(UserModel::TABLE)
            ->where('UPPER(login) = UPPER(:login)')
            ->where('UPPER(ssoLogin) = UPPER(:login)')
            ->bindValues(['login' => $login]);

        return $this->db->runQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * @param UserModel $user
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function updateOnLogin(UserModel $user): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(UserModel::TABLE)
            ->cols([
                       'pass' => $user->getPass(),
                       'name' => $user->getName(),
                       'email' => $user->getEmail(),
                       'isLdap' => $user->isLdap()
                   ])
            ->set('hashSalt', '')
            ->set('lastLogin', 'NOW()')
            ->set('lastUpdate', 'NOW()')
            ->set('loginCount', 'loginCount + 1')
            ->where('UPPER(login) = UPPER(:login)')
            ->where('UPPER(ssoLogin) = UPPER(:login)')
            ->bindValues(['login' => $user->getLogin()])
            ->limit(1);

        return $this->db->runQuery(QueryData::build($query))->getAffectedNumRows();
    }

    /**
     * Updates an user's pass
     *
     * @param int $id
     * @param UserPreferences $userPreferences
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     * @throws JsonException
     *
     * TODO: Handle serialized model migration
     */
    public function updatePreferencesById(int $id, UserPreferences $userPreferences): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(UserModel::TABLE)
            ->cols(['preferences' => $userPreferences->toJson()])
            ->where('id = :id', ['id' => $id])
            ->limit(1);

        return $this->db->runQuery(QueryData::build($query))->getAffectedNumRows();
    }

    /**
     * Obtener el email de los usuarios de un grupo
     *
     * @param int $groupId
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function getUserEmailForGroup(int $groupId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserModel::getColsWithPreffix(UserModel::TABLE))
            ->from(UserModel::TABLE)
            ->innerJoin(
                UserGroupModel::TABLE,
                sprintf('%s.id = %s.userGroupId', UserGroupModel::TABLE, UserModel::TABLE)
            )
            ->leftJoin(
                UserToUserGroupModel::TABLE,
                sprintf('%s.userId = %s.id', UserToUserGroupModel::TABLE, UserModel::TABLE)
            )
            ->where(sprintf('%s.email IS NOT NULL', UserModel::TABLE))
            ->where(
                sprintf(
                    '(%s.userGroupId = :userGroupId OR %s.userGroupId = :userGroupId)',
                    UserModel::TABLE,
                    UserToUserGroupModel::TABLE
                )
            )
            ->where(sprintf('%s.isDisabled = 0', UserModel::TABLE))
            ->orderBy([sprintf('%s.login', UserModel::TABLE)])
            ->bindValues(['userGroupId' => $groupId]);

        return $this->db->runQuery(QueryData::build($query)->setMapClassName(UserModel::class));
    }

    /**
     * Obtener el email de los usuarios
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmail(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserModel::getCols())
            ->from(UserModel::TABLE)
            ->where('email IS NOT NULL')
            ->where('isDisabled = 0')
            ->orderBy(['login']);

        return $this->db->runQuery(QueryData::build($query)->setMapClassName(UserModel::class));
    }

    /**
     * Return the email of the given user's id
     *
     * @param array<int> $ids
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getUserEmailById(array $ids): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(UserModel::getCols())
            ->from(UserModel::TABLE)
            ->where('id IN (:ids)', ['ids' => $ids])
            ->where('email IS NOT NULL')
            ->where('isDisabled = 0')
            ->orderBy(['login']);

        return $this->db->runQuery(QueryData::build($query)->setMapClassName(UserModel::class));
    }

    /**
     * Returns the usage of the given user's id
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function getUsageForUser(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['Items.ref', 'Items.name', 'Items.id'])
            ->fromSubSelect(
                $this->queryFactory
                    ->newSelect()
                    ->from(AccountModel::TABLE)
                    ->innerJoin(
                        ClientModel::TABLE,
                        sprintf('%s.id = %s.clientId', ClientModel::TABLE, AccountModel::TABLE)
                    )
                    ->where(
                        sprintf(
                            '%s.userId = :userId OR %s.userEditId = :userId',
                            AccountModel::TABLE,
                            AccountModel::TABLE
                        )
                    )
                    ->cols(
                        [
                            sprintf('%s.id as id', AccountModel::TABLE),
                            sprintf(
                                'CONCAT(%s.name, "(", %s.name, ")") AS name',
                                AccountModel::TABLE,
                                ClientModel::TABLE
                            ),
                            '"Account" AS ref'
                        ]
                    )
                    ->unionAll()
                    ->from(AccountToUserModel::TABLE)
                    ->innerJoin(
                        AccountModel::TABLE,
                        sprintf('%s.id = %s.accountId', AccountModel::TABLE, AccountToUserModel::TABLE)
                    )
                    ->innerJoin(
                        ClientModel::TABLE,
                        sprintf('%s.id = %s.clientId', ClientModel::TABLE, AccountModel::TABLE)
                    )
                    ->where(sprintf('%s.userId = :userId', AccountToUserModel::TABLE))
                    ->cols(
                        [
                            sprintf('%s.accountId as id', AccountToUserModel::TABLE),
                            sprintf(
                                'CONCAT(%s.name, "(", %s.name, ")") AS name',
                                AccountModel::TABLE,
                                ClientModel::TABLE
                            ),
                            '"Account" AS ref'
                        ]
                    )
                    ->unionAll()
                    ->from(UserToUserGroupModel::TABLE)
                    ->innerJoin(
                        UserGroupModel::TABLE,
                        sprintf(
                            '%s.id = %s.userGroupId',
                            UserGroupModel::TABLE,
                            UserToUserGroupModel::TABLE
                        )
                    )
                    ->where(sprintf('%s.userId = :userId', UserToUserGroupModel::TABLE))
                    ->cols(
                        [
                            sprintf('%s.userGroupId AS id', UserToUserGroupModel::TABLE),
                            sprintf('%s.name AS name', UserGroupModel::TABLE),
                            '"UserGroup" AS ref'
                        ]
                    )
                    ->unionAll()
                    ->from(PublicLinkModel::TABLE)
                    ->innerJoin(
                        AccountModel::TABLE,
                        sprintf(
                            '%s.itemId = %s.id',
                            PublicLinkModel::TABLE,
                            AccountModel::TABLE
                        )
                    )
                    ->innerJoin(
                        ClientModel::TABLE,
                        sprintf('%s.id = %s.clientId', ClientModel::TABLE, AccountModel::TABLE)
                    )
                    ->where(sprintf('%s.userId = :userId', PublicLinkModel::TABLE))
                    ->cols(
                        [
                            sprintf('%s.id AS id', PublicLinkModel::TABLE),
                            sprintf(
                                'CONCAT(%s.name, "(", %s.name, ")") AS name',
                                AccountModel::TABLE,
                                ClientModel::TABLE
                            ),
                            '"PublicLink" AS ref'
                        ]
                    ),
                'Items'
            )
            ->orderBy(['Items.ref'])
            ->bindValues(['userId' => $id]);

        return $this->db->runQuery(QueryData::build($query));
    }
}
