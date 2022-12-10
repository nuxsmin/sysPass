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

namespace SP\Tests\Stubs;

use Closure;
use SP\DataModel\ItemSearchData;
use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Models\Account;
use SP\Domain\Account\Ports\AccountRepositoryInterface;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class AccountRepositoryStub
 */
class AccountRepositoryStub implements AccountRepositoryInterface
{

    public function getTotalNumAccounts(): QueryResult
    {
        return new QueryResult();
    }

    public function getPasswordForId(int $accountId): QueryResult
    {
        return new QueryResult();
    }

    public function getPasswordHistoryForId(int $accountId): QueryResult
    {
        return new QueryResult();
    }

    public function incrementDecryptCounter(int $accountId): QueryResult
    {
        return new QueryResult();
    }

    public function editPassword(int $accountId, Account $account): QueryResult
    {
        return new QueryResult();
    }

    public function updatePassword(int $accountId, EncryptedPassword $encryptedPassword): QueryResult
    {
        return new QueryResult();
    }

    public function restoreModified(int $accountId, Account $account): QueryResult
    {
        return new QueryResult();
    }

    public function updateBulk(int $accountId, Account $account, bool $changeOwner, bool $changeUserGroup): QueryResult
    {
        return new QueryResult();
    }

    public function incrementViewCounter(int $accountId): QueryResult
    {
        return new QueryResult();
    }

    public function getDataForLink(int $accountId): QueryResult
    {
        return new QueryResult();
    }

    public function getForUser(?int $accountId = null): QueryResult
    {
        return new QueryResult();
    }

    public function getLinked(int $accountId): QueryResult
    {
        return new QueryResult();
    }

    public function getAccountsPassData(): QueryResult
    {
        return new QueryResult();
    }

    public function create(Account $account): QueryResult
    {
        return new QueryResult();
    }

    public function delete(int $accountId): QueryResult
    {
        return new QueryResult();
    }

    public function update(int $accountId, Account $account, bool $changeOwner, bool $changeUserGroup): QueryResult
    {
        return new QueryResult();
    }

    public function getByIdEnriched(int $accountId): QueryResult
    {
        return new QueryResult();
    }

    public function getById(int $accountId): QueryResult
    {
        return new QueryResult();
    }

    public function getAll(): QueryResult
    {
        return new QueryResult();
    }

    public function deleteByIdBatch(array $accountsId): QueryResult
    {
        return new QueryResult();
    }

    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return new QueryResult();
    }

    public function transactionAware(Closure $closure, object $newThis): mixed
    {
        return $closure->call($newThis);
    }

    public function createRemoved(Account $account): QueryResult
    {
        return new QueryResult();
    }
}
