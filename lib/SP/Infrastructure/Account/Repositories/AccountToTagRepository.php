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

use SP\Domain\Account\In\AccountToTagRepositoryInterface;
use SP\Domain\Account\Services\AccountRequest;
use SP\Infrastructure\Common\Repositories\Repository;
use SP\Infrastructure\Common\Repositories\RepositoryItemTrait;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use function SP\__u;

/**
 * Class AccountToTagRepository
 *
 * @package SP\Infrastructure\Account\Repositories
 */
final class AccountToTagRepository extends Repository implements AccountToTagRepositoryInterface
{
    use RepositoryItemTrait;

    /**
     * Devolver las etiquetas de una cuenta
     *
     * @param  int  $id
     *
     * @return QueryResult
     */
    public function getTagsByAccountId(int $id): QueryResult
    {
        $query = $this->queryFactory
            ->newSelect()
            ->cols([
                'Tag.id',
                'Tag.name',
            ])
            ->from('AccountToTag')
            ->join('INNER', 'Tag', 'Tag.id == AccountToTag.tagId')
            ->where('AccountToTag.accountId = :accountId')
            ->bindValues(['accountId' => $id])
            ->orderBy(['Tag.name ASC']);

        return $this->db->doSelect(QueryData::build($query));
    }

    /**
     * Eliminar las etiquetas de una cuenta
     *
     * @param  int  $id
     *
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByAccountId(int $id): bool
    {
        $query = $this->queryFactory
            ->newDelete()
            ->from('AccountToTag')
            ->where('accountId = :accountId')
            ->bindValues([
                'accountId' => $id,
            ]);

        $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while removing the account\'s tags'));

        return $this->db->doQuery($queryData)->getAffectedNumRows() === 1;
    }

    /**
     * Actualizar las etiquetas de una cuenta
     *
     * @param  AccountRequest  $accountRequest
     *
     * @return void
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(AccountRequest $accountRequest): void
    {
        foreach ($accountRequest->tags as $tag) {
            $query = $this->queryFactory
                ->newInsert()
                ->into('AccountToTag')
                ->cols([
                    'accountId' => $accountRequest->id,
                    'tagId'     => $tag,
                ]);

            $queryData = QueryData::build($query)->setOnErrorMessage(__u('Error while adding the account\'s tags'));

            $this->db->doQuery($queryData);
        }
    }
}
