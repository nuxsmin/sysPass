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

namespace SP\Modules\Web\Controllers\Helpers\Account;

/**
 * Class AccountActionsDto
 *
 * @package SP\Modules\Web\Controllers\Helpers\Account
 */
final class AccountActionsDto
{
    /**
     * @var int
     */
    private $accountId;
    /**
     * @var int
     */
    private $accountHistoryId;
    /**
     * @var int
     */
    private $accountParentId;
    /**
     * @var int
     */
    private $publicLinkId;
    /**
     * @var int
     */
    private $publicLinkCreatorId;

    /**
     * AccountActionsDto constructor.
     *
     * @param int $accountId
     * @param int $accountHistoryId
     * @param int $accountParentId
     */
    public function __construct($accountId, $accountHistoryId = null, $accountParentId = null)
    {
        $this->accountId = $accountId;
        $this->accountHistoryId = $accountHistoryId;
        $this->accountParentId = $accountParentId;
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @return int
     */
    public function getAccountHistoryId()
    {
        return $this->accountHistoryId;
    }

    /**
     * @return int
     */
    public function getAccountParentId()
    {
        return $this->accountParentId;
    }

    /**
     * @return bool
     */
    public function isHistory()
    {
        return $this->accountHistoryId !== null && $this->accountHistoryId > 0;
    }

    /**
     * @return bool
     */
    public function isLinked()
    {
        return $this->accountParentId !== null && $this->accountParentId > 0;
    }

    /**
     * @return int
     */
    public function getPublicLinkId()
    {
        return $this->publicLinkId;
    }

    /**
     * @param int $publicLinkId
     */
    public function setPublicLinkId(int $publicLinkId)
    {
        $this->publicLinkId = $publicLinkId;
    }

    /**
     * @return int
     */
    public function getPublicLinkCreatorId()
    {
        return $this->publicLinkCreatorId;
    }

    /**
     * @param int $publicLinkCreatorId
     */
    public function setPublicLinkCreatorId(int $publicLinkCreatorId)
    {
        $this->publicLinkCreatorId = $publicLinkCreatorId;
    }
}