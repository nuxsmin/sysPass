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

namespace SP\Infrastructure\Auth\Repositories;

use Exception;
use SP\DataModel\ItemSearchData;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Auth\Ports\AuthTokenRepository;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class AuthTokenRepository
 *
 * @template T of AuthTokenModel
 */
final class AuthToken extends Repository implements AuthTokenRepository
{
    use RepositoryItemTrait;

    public const TABLE = 'AuthToken';

    /**
     * @param int $id
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('id = :id')
            ->bindValues(['id' => $id]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Returns the item for given id
     *
     * @param int $authTokenId
     *
     * @return QueryResult<T>
     */
    public function getById(int $authTokenId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(AuthTokenModel::getCols())
            ->where('id = :id')
            ->bindValues(['id' => $authTokenId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, AuthTokenModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(AuthTokenModel::getCols());

        return $this->db->doSelect(QueryData::buildWithMapper($query, AuthTokenModel::class));
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $authTokensId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByIdBatch(array $authTokensId): QueryResult
    {
        if (count($authTokensId) === 0) {
            return new QueryResult();
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from(self::TABLE)
            ->where('id IN (:ids)', ['ids' => $authTokensId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->innerJoin('User', 'AuthToken.userid = User.id')
            ->cols([
                       'AuthToken.id',
                       'AuthToken.userId',
                       'AuthToken.actionId',
                       'AuthToken.token',
                       'User.name',
                       'User.login'
                   ])
            ->orderBy(['name ASC', 'clientName ASC'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('User.login  LIKE :userLogin OR User.name LIKE :userName');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues(['userLogin' => $search, 'userName' => $search]);
        }

        return $this->db->doSelect(QueryData::build($query), true);
    }

    /**
     * Creates an item
     *
     * @param AuthTokenModel $authToken
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function create(AuthTokenModel $authToken): QueryResult
    {
        if ($this->checkDuplicatedOnAdd($authToken)) {
            throw new DuplicatedItemException(__u('Authorization already exist'));
        }

        $query = $this->queryFactory
            ->newInsert()
            ->into(self::TABLE)
            ->cols($authToken->toArray(null, ['id', 'startDate']))
            ->set('startDate', 'UNIX_TIMESTAMP()');

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData);
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param AuthTokenModel $authToken
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnAdd(AuthTokenModel $authToken): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(self::TABLE)
            ->where('userId = :userId')
            ->orWhere('token = :token')
            ->where('actionId = :actionId')
            ->bindValues(
                [
                    'userId' => $authToken->getUserId(),
                    'token' => $authToken->getToken(),
                    'actionId' => $authToken->getActionId()
                ]
            );

        return $this->db->doQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Obtener el token de la API de un usuario
     *
     * @param int $userId
     * @return QueryResult<T>
     */
    public function getTokenByUserId(int $userId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(AuthTokenModel::getCols())
            ->where('userId = :userId')
            ->where('token <> \'\'')
            ->bindValues(['userId' => $userId])
            ->limit(1);

        $queryData = QueryData::buildWithMapper($query, AuthTokenModel::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * Updates an item
     *
     * @param AuthTokenModel $authToken
     * @return bool
     * @throws ConstraintException
     * @throws DuplicatedItemException
     * @throws QueryException
     */
    public function update(AuthTokenModel $authToken): bool
    {
        if ($this->checkDuplicatedOnUpdate($authToken)) {
            throw new DuplicatedItemException(__u('Authorization already exist'));
        }

        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols($authToken->toArray(null, ['id', 'startDate']))
            ->set('startDate', 'UNIX_TIMESTAMP()')
            ->where('id = :id')
            ->limit(1)
            ->bindValues(['id' => $authToken->getId()]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param AuthTokenModel $authToken
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnUpdate(AuthTokenModel $authToken): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from(self::TABLE)
            ->where('(userId = :userId OR token = :token)')
            ->where('actionId = :actionId')
            ->where('id <> :id')
            ->bindValues(
                [
                    'id' => $authToken->getId(),
                    'userId' => $authToken->getUserId(),
                    'token' => $authToken->getToken(),
                    'actionId' => $authToken->getActionId()
                ]
            );

        return $this->db->doQuery(QueryData::build($query))->getNumRows() > 0;
    }

    /**
     * Regenerar el hash de los tokens de un usuario
     *
     * @param int $userId
     * @param string $token
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function refreshTokenByUserId(int $userId, string $token): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->col('token', $token)
            ->set('startDate', 'UNIX_TIMESTAMP()')
            ->where('userId = :userId', ['userId' => $userId]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Regenerar el hash de los tokens de un usuario
     *
     * @param int $userId
     * @param string $vault
     * @param string $hash
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function refreshVaultByUserId(int $userId, string $vault, string $hash): int
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table(self::TABLE)
            ->cols([
                       'vault' => $vault,
                       'hash' => $hash
                   ])
            ->set('startDate', 'UNIX_TIMESTAMP()')
            ->where('userId = :userId', ['userId' => $userId])
            ->where('vault IS NOT NULL');

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Internal error'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Devolver los datos de un token
     *
     * @param $actionId int El id de la accion
     * @param $token    string El token de seguridad
     *
     * @return QueryResult<T>
     */
    public function getTokenByToken(int $actionId, string $token): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from(self::TABLE)
            ->cols(AuthTokenModel::getCols())
            ->where('actionId = :actionId')
            ->where('token = :token')
            ->bindValues(['actionId' => $actionId, 'token' => $token])
            ->limit(1);

        return $this->db->doSelect(QueryData::buildWithMapper($query, AuthTokenModel::class));
    }
}
