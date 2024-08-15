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

namespace SP\Infrastructure\Account\Repositories;

use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Domain\Account\Ports\PublicLinkRepository;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\BaseRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;

use function SP\__u;

/**
 * Class PublicLink
 *
 * @template T of PublicLinkModel
 */
final class PublicLink extends BaseRepository implements PublicLinkRepository
{
    use RepositoryItemTrait;

    /**
     * Deletes an item
     *
     * @param int $id
     *
     * @return void
     * @throws ConstraintException
     * @throws QueryException
     */
    public function delete(int $id): void
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('PublicLink')
            ->where('id = :id')
            ->bindValue('id', $id);

        $this->db->runQuery(QueryData::build($query)->setOnErrorMessage(__u('Error while removing the link')));
    }

    /**
     * Returns all the items
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                       'PublicLink.id',
                       'PublicLink.itemId',
                       'PublicLink.hash',
                       'PublicLink.data',
                       'PublicLink.userId',
                       'PublicLink.typeId',
                       'PublicLink.notify',
                       'PublicLink.dateAdd',
                       'PublicLink.dateExpire',
                       'PublicLink.dateUpdate',
                       'PublicLink.countViews',
                       'PublicLink.maxCountViews',
                       'PublicLink.totalCountViews',
                       'PublicLink.useInfo',
                       'User.name AS userName',
                       'User.login AS userLogin',
                       'Account.name AS accountName',
                   ])
            ->from('PublicLink')
            ->join('INNER', 'User', 'User.id = PublicLink.userId')
            ->join('INNER', 'Account', 'Account.id = PublicLink.itemId')
            ->orderBy(['PublicLink.id']);

        return $this->db->runQuery(QueryData::build($query)->setMapClassName(PublicLinkModel::class));
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
        if (count($ids) === 0) {
            return 0;
        }

        $query = $this->queryFactory
            ->newDelete()
            ->from('PublicLink')
            ->where('id IN (:ids)', ['ids' => $ids]);

        return $this->db->runQuery(QueryData::build($query))->getAffectedNumRows();
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchDto $itemSearchData
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from('PublicLink')
            ->cols([
                       'PublicLink.id',
                       'PublicLink.itemId',
                       'PublicLink.hash',
                       'PublicLink.data',
                       'PublicLink.userId',
                       'PublicLink.typeId',
                       'PublicLink.notify',
                       'PublicLink.dateAdd',
                       'PublicLink.dateExpire',
                       'PublicLink.dateUpdate',
                       'PublicLink.countViews',
                       'PublicLink.maxCountViews',
                       'PublicLink.totalCountViews',
                       'PublicLink.useInfo',
                       'User.name AS userName',
                       'User.login AS userLogin',
                       'Account.name AS accountName',
                       'Client.name AS clientName',
                   ])
            ->join('INNER', 'User', 'User.id = PublicLink.userId')
            ->join('INNER', 'Account', 'Account.id = PublicLink.itemId')
            ->join('INNER', 'Client', 'Client.id = Account.clientId')
            ->orderBy(['PublicLink.dateExpire DESC'])
            ->limit($itemSearchData->getLimitCount())
            ->offset($itemSearchData->getLimitStart());

        if (!empty($itemSearchData->getSeachString())) {
            $query->where('User.login LIKE :login')
                  ->orWhere('Account.name LIKE :accountName')
                  ->orWhere('Client.name LIKE :clientName');

            $search = '%' . $itemSearchData->getSeachString() . '%';

            $query->bindValues([
                                   'login' => $search,
                                   'accountName' => $search,
                                   'clientName' => $search,
                               ]);
        }

        return $this->db->runQuery(QueryData::build($query)->setMapClassName(PublicLinkModel::class), true);
    }

    /**
     * Creates an item
     *
     * @param PublicLinkModel $publicLinkData
     *
     * @return QueryResult
     * @throws DuplicatedItemException
     * @throws QueryException
     * @throws ConstraintException
     */
    public function create(PublicLinkModel $publicLinkData): QueryResult
    {
        if ($this->checkDuplicatedOnAdd($publicLinkData->getItemId())) {
            throw new DuplicatedItemException(__u('Link already created'));
        }

        $query = $this->queryFactory
            ->newInsert()
            ->into('PublicLink')
            ->cols([
                       'itemId' => $publicLinkData->getItemId(),
                       'hash' => $publicLinkData->getHash(),
                       'data' => $publicLinkData->getData(),
                       'userId' => $publicLinkData->getUserId(),
                       'typeId' => $publicLinkData->getTypeId(),
                       'notify' => (int)$publicLinkData->isNotify(),
                       'dateExpire' => $publicLinkData->getDateExpire(),
                       'maxCountViews' => $publicLinkData->getMaxCountViews(),
                   ])
            ->col('dateAdd = UNIX_TIMESTAMP()');

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while creating the link'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param int $id
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDuplicatedOnAdd(int $id): bool
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols(['id'])
            ->from('PublicLink')
            ->where('itemId = :itemId')
            ->bindValue('itemId', $id);

        return $this->db->runQuery(QueryData::build($query))->getNumRows() === 1;
    }

    /**
     * Incrementar el contador de visitas de un enlace
     *
     * @param PublicLinkModel $publicLinkData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function addLinkView(PublicLinkModel $publicLinkData): bool
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('PublicLink')
            ->set('countViews', '(countViews + 1)')
            ->set('totalCountViews', '(totalCountViews + 1)')
            ->col('useInfo', $publicLinkData->getUseInfo())
            ->where('hash = :hash')
            ->bindValues(['hash' => $publicLinkData->getHash()]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the link'));

        return $this->db->runQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Updates an item
     *
     * @param PublicLinkModel $publicLinkData
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(PublicLinkModel $publicLinkData): bool
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('PublicLink')
            ->cols([
                       'itemId' => $publicLinkData->getItemId(),
                       'hash' => $publicLinkData->getHash(),
                       'data' => $publicLinkData->getData(),
                       'userId' => $publicLinkData->getUserId(),
                       'typeId' => $publicLinkData->getTypeId(),
                       'notify' => (int)$publicLinkData->isNotify(),
                       'dateExpire' => $publicLinkData->getDateExpire(),
                       'maxCountViews' => $publicLinkData->getMaxCountViews(),
                       'useInfo' => $publicLinkData->getUseInfo(),
                   ])
            ->where('id = :id')
            ->bindValues(['id' => $publicLinkData->getId()]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while updating the link'));

        return $this->db->runQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Refreshes a public link
     *
     * @param PublicLinkModel $publicLinkData
     *
     * @return bool
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function refresh(PublicLinkModel $publicLinkData): bool
    {
        $query = $this->queryFactory
            ->newUpdate()
            ->table('PublicLink')
            ->cols([
                       'hash' => $publicLinkData->getHash(),
                       'data' => $publicLinkData->getData(),
                       'dateExpire' => $publicLinkData->getDateExpire(),
                       'countViews' => 0,
                       'maxCountViews' => $publicLinkData->getMaxCountViews(),
                   ])
            ->where('id = :id')
            ->bindValues(['id' => $publicLinkData->getId()]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while renewing the link'));

        return $this->db->runQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getById(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                       'PublicLink.id',
                       'PublicLink.itemId',
                       'PublicLink.hash',
                       'PublicLink.data',
                       'PublicLink.userId',
                       'PublicLink.typeId',
                       'PublicLink.notify',
                       'PublicLink.dateAdd',
                       'PublicLink.dateExpire',
                       'PublicLink.dateUpdate',
                       'PublicLink.countViews',
                       'PublicLink.maxCountViews',
                       'PublicLink.totalCountViews',
                       'PublicLink.useInfo',
                       'User.name AS userName',
                       'User.login AS userLogin',
                       'Account.name AS accountName',
                   ])
            ->from('PublicLink')
            ->join('INNER', 'User', 'User.id = PublicLink.userId')
            ->join('INNER', 'Account', 'Account.id = PublicLink.itemId')
            ->where('PublicLink.id = :id')
            ->bindValue('id', $id);

        $queryData = QueryData::build($query)
                              ->setMapClassName(PublicLinkModel::class)
                              ->setOnErrorMessage(__u('Error while retrieving the link'));

        return $this->db->runQuery($queryData);
    }

    /**
     * @param $hash string
     *
     * @return QueryResult<T>
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByHash(string $hash): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                       'PublicLink.id',
                       'PublicLink.itemId',
                       'PublicLink.hash',
                       'PublicLink.data',
                       'PublicLink.userId',
                       'PublicLink.typeId',
                       'PublicLink.notify',
                       'PublicLink.dateAdd',
                       'PublicLink.dateExpire',
                       'PublicLink.dateUpdate',
                       'PublicLink.countViews',
                       'PublicLink.maxCountViews',
                       'PublicLink.totalCountViews',
                       'PublicLink.useInfo',
                       'User.name AS userName',
                       'User.login AS userLogin',
                       'Account.name AS accountName',
                   ])
            ->from('PublicLink')
            ->join('INNER', 'User', 'User.id = PublicLink.userId')
            ->join('INNER', 'Account', 'Account.id = PublicLink.itemId')
            ->where('PublicLink.hash = :hash')
            ->bindValue('hash', $hash);

        $queryData = QueryData::build($query)
                              ->setMapClassName(PublicLinkModel::class)
                              ->setOnErrorMessage(__u('Error while retrieving the link'));

        return $this->db->runQuery($queryData);
    }

    /**
     * Devolver el hash asociado a un elemento
     *
     * @param int $itemId
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getHashForItem(int $itemId): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                       'id',
                       'hash',
                       'userId',
                   ])
            ->from('PublicLink')
            ->where('itemId = :itemId')
            ->bindValue('itemId', $itemId);

        return $this->db->runQuery(QueryData::build($query)->setOnErrorMessage(__u('Error while retrieving the link')));
    }
}
