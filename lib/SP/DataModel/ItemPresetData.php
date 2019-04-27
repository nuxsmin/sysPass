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

/**
 * Class ItemPresetData
 *
 * @package SP\DataModel
 */
class ItemPresetData extends DataModelBase implements HydratableInterface
{
    use SerializedModel;

    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $type;
    /**
     * @var int
     */
    public $userId;
    /**
     * @var int
     */
    public $userGroupId;
    /**
     * @var int
     */
    public $userProfileId;
    /**
     * @var int
     */
    public $fixed;
    /**
     * @var int
     */
    public $priority;
    /**
     * @var string
     */
    public $data;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id !== null ? (int)$this->id : null;
    }

    /**
     * @param int $id
     *
     * @return ItemPresetData
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId !== null ? (int)$this->userId : null;
    }

    /**
     * @param int $userId
     *
     * @return ItemPresetData
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->userGroupId !== null ? (int)$this->userGroupId : null;
    }

    /**
     * @param int $userGroupId
     *
     * @return ItemPresetData
     */
    public function setUserGroupId(int $userGroupId)
    {
        $this->userGroupId = $userGroupId;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserProfileId()
    {
        return $this->userProfileId !== null ? (int)$this->userProfileId : null;
    }

    /**
     * @param int $userProfileId
     *
     * @return ItemPresetData
     */
    public function setUserProfileId(int $userProfileId)
    {
        $this->userProfileId = $userProfileId;

        return $this;
    }

    /**
     * @return int
     */
    public function getFixed(): int
    {
        return (int)$this->fixed;
    }

    /**
     * @param int $fixed
     *
     * @return ItemPresetData
     */
    public function setFixed(int $fixed)
    {
        $this->fixed = $fixed;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return (int)$this->priority;
    }

    /**
     * @param int $priority
     *
     * @return ItemPresetData
     */
    public function setPriority(int $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData(string $data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return sha1($this->type . (int)$this->userId . (int)$this->userGroupId . (int)$this->userProfileId . (int)$this->priority);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }
}