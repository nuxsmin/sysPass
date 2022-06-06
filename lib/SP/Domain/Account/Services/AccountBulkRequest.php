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

namespace SP\Domain\Account\Services;


/**
 * Class AccountBulkRequest
 *
 * @package SP\Domain\Account\Services
 */
final class AccountBulkRequest
{
    private array          $itemsId;
    private AccountRequest $accountRequest;
    private bool           $deleteHistory = false;

    /**
     * AccountBulkRequest constructor.
     *
     * @param  int[]  $itemsId
     * @param  AccountRequest  $accountRequest
     */
    public function __construct(array $itemsId, AccountRequest $accountRequest)
    {
        $this->itemsId = $itemsId;
        $this->accountRequest = $accountRequest;

        $this->setUp();
    }

    private function setUp(): void
    {
        $this->accountRequest->changeUserGroup = $this->accountRequest->userGroupId > 0;
        $this->accountRequest->changePermissions = true;
    }

    public function isDeleteHistory(): bool
    {
        return $this->deleteHistory;
    }

    public function setDeleteHistory(bool $deleteHistory): void
    {
        $this->deleteHistory = $deleteHistory;
    }

    public function getItemsId(): array
    {
        return $this->itemsId;
    }

    public function getAccountRequestForId(int $id): AccountRequest
    {
        $request = clone $this->accountRequest;
        $request->id = $id;

        return $request;
    }
}