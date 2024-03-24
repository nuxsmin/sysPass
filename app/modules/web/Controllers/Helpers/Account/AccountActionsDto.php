<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\Helpers\Account;

/**
 * Class AccountActionsDto
 *
 * @package SP\Modules\Web\Controllers\Helpers\Account
 */
final class AccountActionsDto
{
    private ?int $accountId;
    private ?int $accountHistoryId;
    private ?int $accountParentId;
    private ?int $publicLinkId = null;
    private ?int $publicLinkCreatorId = null;

    public function __construct(
        ?int $accountId,
        ?int $accountHistoryId = null,
        ?int $accountParentId = null)
    {
        $this->accountId = $accountId;
        $this->accountHistoryId = $accountHistoryId;
        $this->accountParentId = $accountParentId;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getAccountHistoryId(): ?int
    {
        return $this->accountHistoryId;
    }

    public function getAccountParentId(): ?int
    {
        return $this->accountParentId;
    }

    public function isHistory(): bool
    {
        return $this->accountHistoryId !== null && $this->accountHistoryId > 0;
    }

    public function isLinked(): bool
    {
        return $this->accountParentId !== null && $this->accountParentId > 0;
    }

    public function getPublicLinkId(): ?int
    {
        return $this->publicLinkId;
    }

    public function setPublicLinkId(int $publicLinkId): void
    {
        $this->publicLinkId = $publicLinkId;
    }

    public function getPublicLinkCreatorId(): ?int
    {
        return $this->publicLinkCreatorId;
    }

    public function setPublicLinkCreatorId(int $publicLinkCreatorId): void
    {
        $this->publicLinkCreatorId = $publicLinkCreatorId;
    }
}