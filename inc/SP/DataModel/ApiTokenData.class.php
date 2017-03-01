<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 * Class ApiTokenData
 *
 * @package SP\DataModel
 */
class ApiTokenData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int
     */
    public $authtoken_id;
    /**
     * @var Vault
     */
    public $authtoken_vault;
    /**
     * @var int
     */
    public $authtoken_userId;
    /**
     * @var string
     */
    public $authtoken_token = '';
    /**
     * @var int
     */
    public $authtoken_createdBy;
    /**
     * @var int
     */
    public $authtoken_startDate;
    /**
     * @var int
     */
    public $authtoken_actionId;
    /**
     * @var string
     */
    public $authtoken_hash;

    /**
     * @return int
     */
    public function getAuthtokenId()
    {
        return (int)$this->authtoken_id;
    }

    /**
     * @param int $authtoken_id
     */
    public function setAuthtokenId($authtoken_id)
    {
        $this->authtoken_id = (int)$authtoken_id;
    }

    /**
     * @return Vault
     */
    public function getAuthtokenVault()
    {
        return $this->authtoken_vault;
    }

    /**
     * @param Vault $authtoken_vault
     */
    public function setAuthtokenVault(Vault $authtoken_vault)
    {
        $this->authtoken_vault = $authtoken_vault;
    }

    /**
     * @return int
     */
    public function getAuthtokenUserId()
    {
        return (int)$this->authtoken_userId;
    }

    /**
     * @param int $authtoken_userId
     */
    public function setAuthtokenUserId($authtoken_userId)
    {
        $this->authtoken_userId = (int)$authtoken_userId;
    }

    /**
     * @return string
     */
    public function getAuthtokenToken()
    {
        return $this->authtoken_token;
    }

    /**
     * @param string $authtoken_token
     */
    public function setAuthtokenToken($authtoken_token)
    {
        $this->authtoken_token = $authtoken_token;
    }

    /**
     * @return int
     */
    public function getAuthtokenCreatedBy()
    {
        return (int)$this->authtoken_createdBy;
    }

    /**
     * @param int $authtoken_createdBy
     */
    public function setAuthtokenCreatedBy($authtoken_createdBy)
    {
        $this->authtoken_createdBy = (int)$authtoken_createdBy;
    }

    /**
     * @return int
     */
    public function getAuthtokenStartDate()
    {
        return (int)$this->authtoken_startDate;
    }

    /**
     * @param int $authtoken_startDate
     */
    public function setAuthtokenStartDate($authtoken_startDate)
    {
        $this->authtoken_startDate = (int)$authtoken_startDate;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->authtoken_id;
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
    public function getAuthtokenActionId()
    {
        return (int)$this->authtoken_actionId;
    }

    /**
     * @param int $authtoken_actionId
     */
    public function setAuthtokenActionId($authtoken_actionId)
    {
        $this->authtoken_actionId = (int)$authtoken_actionId;
    }

    /**
     * @return string
     */
    public function getAuthtokenHash()
    {
        return $this->authtoken_hash;
    }

    /**
     * @param string $authtoken_hash
     */
    public function setAuthtokenHash($authtoken_hash)
    {
        $this->authtoken_hash = $authtoken_hash;
    }
}