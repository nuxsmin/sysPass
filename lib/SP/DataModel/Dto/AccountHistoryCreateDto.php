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

namespace SP\DataModel\Dto;


/**
 * Class AccountHistoryCreateDto
 *
 * @package SP\DataModel\Dto
 */
class AccountHistoryCreateDto
{
    /**
     * @var int
     */
    private $accountId;
    /**
     * @var bool
     */
    private $isModify;
    /**
     * @var bool
     */
    private $isDelete;
    /**
     * @var string
     */
    private $masterPassHash;

    /**
     * AccountHistoryCreateDto constructor.
     *
     * @param int    $accountId
     * @param bool   $isModify
     * @param bool   $isDelete
     * @param string $masterPassHash
     */
    public function __construct(int $accountId, bool $isModify, bool $isDelete, string $masterPassHash)
    {
        $this->accountId = $accountId;
        $this->isModify = $isModify;
        $this->isDelete = $isDelete;
        $this->masterPassHash = $masterPassHash;
    }

    /**
     * @return int
     */
    public function getAccountId(): int
    {
        return $this->accountId;
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
}