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
 * Class AccountSearchData
 *
 * @package SP\DataModel
 */
class AccountSearchVData
{
    /**
     * @var int Id de la cuenta.
     */
    public $id = 0;
    /**
     * @var int Id del usuario principal de la cuenta.
     */
    public $userId = 0;
    /**
     * @var int Id del grupo principal de la cuenta.
     */
    public $userGroupId = 0;
    /**
     * @var int Id del usuario que editó la cuenta.
     */
    public $userEditId = 0;
    /**
     * @var string El nombre de la cuenta.
     */
    public $name = '';
    /**
     * @var int Id del cliente de la cuenta.
     */
    public $clientId = 0;
    /**
     * @var int Id de la categoría de la cuenta.
     */
    public $categoryId = 0;
    /**
     * @var string El nombre de usuario de la cuenta.
     */
    public $login = '';
    /**
     * @var string La URL de la cuenta.
     */
    public $url = '';
    /**
     * @var string Las nosta de la cuenta.
     */
    public $notes = '';
    /**
     * @var int
     */
    public $otherUserEdit = 0;
    /**
     * @var int
     */
    public $otherUserGroupEdit = 0;
    /**
     * @var int
     */
    public $isPrivate = 0;
    /**
     * @var int
     */
    public $isPrivateGroup = 0;
    /**
     * @var string
     */
    public $dateEdit;
    /**
     * @var int
     */
    public $passDate = 0;
    /**
     * @var int
     */
    public $passDateChange = 0;
    /**
     * @var int
     */
    public $parentId = 0;
    /**
     * @var string
     */
    public $categoryName = '';
    /**
     * @var string
     */
    public $clientName = '';
    /**
     * @var string
     */
    public $userGroupName = '';
    /**
     * @var string
     */
    public $userName = '';
    /**
     * @var string
     */
    public $userLogin = '';
    /**
     * @var string
     */
    public $userEditName = '';
    /**
     * @var string
     */
    public $userEditLogin = '';
    /**
     * @var int
     */
    public $num_files = 0;
    /**
     * @var string
     */
    public $publicLinkHash;
    /**
     * @var int
     */
    public $publicLinkDateExpire;
    /**
     * @var int
     */
    public $publicLinkTotalCountViews;

    /**
     * AccountData constructor.
     *
     * @param int $accountId
     */
    public function __construct($accountId = 0)
    {
        $this->id = (int)$accountId;
    }

    /**
     * @return int
     */
    public function getUserEditId()
    {
        return (int)$this->userEditId;
    }

    /**
     * @param int $userEditId
     */
    public function setUserEditId($userEditId)
    {
        $this->userEditId = (int)$userEditId;
    }

    /**
     * @return int|null
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
     * @return int
     */
    public function getUserGroupId()
    {
        return (int)$this->userGroupId;
    }

    /**
     * @param int $userGroupId
     */
    public function setUserGroupId($userGroupId)
    {
        $this->userGroupId = (int)$userGroupId;
    }

    /**
     * @return int
     */
    public function getOtherUserEdit()
    {
        return (int)$this->otherUserEdit;
    }

    /**
     * @param bool $otherUserEdit
     */
    public function setOtherUserEdit($otherUserEdit)
    {
        $this->otherUserEdit = (int)$otherUserEdit;
    }

    /**
     * @return int
     */
    public function getOtherUserGroupEdit()
    {
        return (int)$this->otherUserGroupEdit;
    }

    /**
     * @param bool $otherUserGroupEdit
     */
    public function setOtherUserGroupEdit($otherUserGroupEdit)
    {
        $this->otherUserGroupEdit = (int)$otherUserGroupEdit;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return (int)$this->categoryId;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = (int)$categoryId;
    }

    /**
     * @return int
     */
    public function getClientId()
    {
        return (int)$this->clientId;
    }

    /**
     * @param int $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = (int)$clientId;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }

    /**
     * @return int
     */
    public function getIsPrivate()
    {
        return (int)$this->isPrivate;
    }

    /**
     * @param int $isPrivate
     */
    public function setIsPrivate($isPrivate)
    {
        $this->isPrivate = (int)$isPrivate;
    }

    /**
     * @return int
     */
    public function getPassDate()
    {
        return (int)$this->passDate;
    }

    /**
     * @param int $passDate
     */
    public function setPassDate($passDate)
    {
        $this->passDate = (int)$passDate;
    }

    /**
     * @return int
     */
    public function getPassDateChange()
    {
        return (int)$this->passDateChange;
    }

    /**
     * @param int $passDateChange
     */
    public function setPassDateChange($passDateChange)
    {
        $this->passDateChange = (int)$passDateChange;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return (int)$this->parentId;
    }

    /**
     * @param int $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = (int)$parentId;
    }

    /**
     * @return int
     */
    public function getIsPrivateGroup()
    {
        return (int)$this->isPrivateGroup;
    }

    /**
     * @param int $isPrivateGroup
     */
    public function setIsPrivateGroup($isPrivateGroup)
    {
        $this->isPrivateGroup = (int)$isPrivateGroup;
    }

    /**
     * @return string
     */
    public function getUserEditName()
    {
        return $this->userEditName;
    }

    /**
     * @return string
     */
    public function getUserEditLogin()
    {
        return $this->userEditLogin;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * @return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * @return string
     */
    public function getUserGroupName()
    {
        return $this->userGroupName;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return string
     */
    public function getUserLogin()
    {
        return $this->userLogin;
    }

    /**
     * @return int
     */
    public function getNumFiles()
    {
        return (int)$this->num_files;
    }

    /**
     * @param int $num_files
     */
    public function setNumFiles($num_files)
    {
        $this->num_files = (int)$num_files;
    }

    /**
     * @return string
     */
    public function getDateEdit()
    {
        return $this->dateEdit;
    }

    /**
     * @param string $dateEdit
     */
    public function setDateEdit($dateEdit)
    {
        $this->dateEdit = $dateEdit;
    }

    /**
     * @return int
     */
    public function getPublicLinkDateExpire(): int
    {
        return (int)$this->publicLinkDateExpire;
    }

    /**
     * @param int $publicLinkDateExpire
     */
    public function setPublicLinkDateExpire(int $publicLinkDateExpire)
    {
        $this->publicLinkDateExpire = $publicLinkDateExpire;
    }

    /**
     * @return int
     */
    public function getPublicLinkTotalCountViews(): int
    {
        return (int)$this->publicLinkTotalCountViews;
    }

    /**
     * @param int $publicLinkTotalCountViews
     */
    public function setPublicLinkTotalCountViews(int $publicLinkTotalCountViews)
    {
        $this->publicLinkTotalCountViews = $publicLinkTotalCountViews;
    }

    /**
     * @return string
     */
    public function getPublicLinkHash()
    {
        return $this->publicLinkHash;
    }

    /**
     * @param string $publicLinkHash
     */
    public function setPublicLinkHash(string $publicLinkHash)
    {
        $this->publicLinkHash = $publicLinkHash;
    }
}