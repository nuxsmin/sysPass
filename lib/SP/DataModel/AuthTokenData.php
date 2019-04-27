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

namespace SP\DataModel;

use SP\Core\Crypt\Vault;

/**
 * Class AuthTokenData
 *
 * @package SP\DataModel
 */
class AuthTokenData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $vault;
    /**
     * @var int
     */
    public $userId;
    /**
     * @var string
     */
    public $token = '';
    /**
     * @var int
     */
    public $createdBy;
    /**
     * @var int
     */
    public $startDate;
    /**
     * @var int
     */
    public $actionId;
    /**
     * @var string
     */
    public $hash;

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = (int)$id;
    }

    /**
     * @return string
     */
    public function getVault()
    {
        return $this->vault;
    }

    /**
     * @param Vault $vault
     */
    public function setVault(Vault $vault)
    {
        $this->vault = serialize($vault);
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return (int)$this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = (int)$userId;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return int
     */
    public function getCreatedBy()
    {
        return (int)$this->createdBy;
    }

    /**
     * @param int $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = (int)$createdBy;
    }

    /**
     * @return int
     */
    public function getStartDate()
    {
        return (int)$this->startDate;
    }

    /**
     * @param int $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = (int)$startDate;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }

    /**
     * @return int
     */
    public function getActionId()
    {
        return (int)$this->actionId;
    }

    /**
     * @param int $actionId
     */
    public function setActionId($actionId)
    {
        $this->actionId = (int)$actionId;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }
}