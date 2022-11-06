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

namespace SP\DataModel\Dto;


use SP\Domain\Account\Out\AccountData;

/**
 * Class AccountHistoryCreateDto
 *
 * @package SP\DataModel\Dto
 */
class AccountHistoryCreateDto
{
    private bool        $isModify;
    private bool        $isDelete;
    private string      $masterPassHash;
    private AccountData $accountData;

    /**
     * AccountHistoryCreateDto constructor.
     *
     * @param  \SP\Domain\Account\Out\AccountData  $accountData
     * @param  bool  $isModify
     * @param  bool  $isDelete
     * @param  string  $masterPassHash
     */
    public function __construct(AccountData $accountData, bool $isModify, bool $isDelete, string $masterPassHash)
    {
        $this->accountData = $accountData;
        $this->isModify = $isModify;
        $this->isDelete = $isDelete;
        $this->masterPassHash = $masterPassHash;
    }

    /**
     * @return bool
     */
    public function isModify(): bool
    {
        return $this->isModify;
    }

    /**
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isDelete;
    }

    /**
     * @return string
     */
    public function getMasterPassHash(): string
    {
        return $this->masterPassHash;
    }

    public function getAccountData(): AccountData
    {
        return $this->accountData;
    }
}