<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Repositories\Account;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemData;
use SP\Repositories\Repository;
use SP\Repositories\RepositoryItemTrait;
use SP\Services\Account\AccountRequest;
use SP\Storage\Database\QueryData;
use SP\Storage\Database\QueryResult;

/**
 * Class AccountToTagRepository
 *
 * @package SP\Repositories\Account
 */
final class AccountToTagRepository extends Repository
{
    use RepositoryItemTrait;

    /**
     * Devolver las etiquetas de una cuenta
     *
     * @param int $id
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getTagsByAccountId($id)
    {
        $query = /** @lang SQL */
            'SELECT T.id, T.name
                FROM AccountToTag AT
                INNER JOIN Tag T ON AT.tagId = T.id
                WHERE AT.accountId = ?
                ORDER BY T.name';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(ItemData::class);

        return $this->db->doSelect($queryData);
    }

    /**
     * @param AccountRequest $accountRequest
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(AccountRequest $accountRequest)
    {
        $this->deleteByAccountId($accountRequest->id);
        $this->add($accountRequest);
    }

    /**
     * Eliminar las etiquetas de una cuenta
     *
     * @param int $id
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function deleteByAccountId($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM AccountToTag WHERE accountId = ?');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error while removing the account\'s tags'));

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

    /**
     * Actualizar las etiquetas de una cuenta
     *
     * @param AccountRequest $accountRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(AccountRequest $accountRequest)
    {
        $query = /** @lang SQL */
            'INSERT INTO AccountToTag (accountId, tagId) VALUES ' . $this->getParamsFromArray($accountRequest->tags, '(?,?)');

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setOnErrorMessage(__u('Error while adding the account\'s tags'));

        foreach ($accountRequest->tags as $tag) {
            $queryData->addParam($accountRequest->id);
            $queryData->addParam($tag);
        }

        return $this->db->doQuery($queryData)->getAffectedNumRows();
    }

}