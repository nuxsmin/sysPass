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

namespace SP\Services\Account;


/**
 * Class AccountBulkRequest
 *
 * @package SP\Services\Account
 */
final class AccountBulkRequest
{
    /**
     * @var array
     */
    private $itemsId;
    /**
     * @var AccountRequest
     */
    private $accountRequest;
    /**
     * @var bool
     */
    private $deleteHistory = false;

    /**
     * AccountBulkRequest constructor.
     *
     * @param array          $itemsId
     * @param AccountRequest $accountRequest
     */
    public function __construct(array $itemsId, AccountRequest $accountRequest)
    {
        $this->itemsId = $itemsId;
        $this->accountRequest = $accountRequest;

        $this->setUp();
    }

    private function setUp()
    {
        $this->accountRequest->changeUserGroup = $this->accountRequest->userGroupId > 0;
        $this->accountRequest->changePermissions = true;
    }

    /**
     * @return bool
     */
    public function isDeleteHistory(): bool
    {
        return $this->deleteHistory;
    }

    /**
     * @param bool $deleteHistory
     */
    public function setDeleteHistory(bool $deleteHistory)
    {
        $this->deleteHistory = $deleteHistory;
    }

    /**
     * @return array
     */
    public function getItemsId(): array
    {
        return $this->itemsId;
    }

    /**
     * @param int $id
     *
     * @return AccountRequest
     */
    public function getAccountRequestForId(int $id): AccountRequest
    {
        $request = clone $this->accountRequest;
        $request->id = $id;

        return $request;
    }
}