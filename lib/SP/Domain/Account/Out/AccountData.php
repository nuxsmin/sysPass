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

namespace SP\Domain\Account\Out;

defined('APP_ROOT') || die();

use JsonSerializable;
use SP\Domain\Common\Out\DataModelBase;
use SP\Domain\Common\Out\DataModelInterface;
use SP\Http\Json;

/**
 * Class AccountData
 */
class AccountData extends DataModelBase implements JsonSerializable, DataModelInterface
{
    private int     $id;
    private ?int    $userId         = null;
    private ?int    $userGroupId    = null;
    private ?int    $userEditId     = null;
    private ?string $name           = null;
    private ?int    $clientId       = null;
    private ?int    $categoryId     = null;
    private ?string $login          = null;
    private ?string $url            = null;
    private ?string $pass           = null;
    private ?string $key            = null;
    private ?string $notes          = null;
    private ?int    $dateAdd        = 0;
    private ?int    $dateEdit       = 0;
    private ?int    $countView      = 0;
    private ?int    $countDecrypt   = 0;
    private ?int    $isPrivate      = 0;
    private ?int    $isPrivateGroup = 0;
    private ?int    $passDate       = 0;
    private ?int    $passDateChange = 0;
    private ?int    $parentId       = 0;

    public function __construct(int $accountId = 0, ?array $properties = [])
    {
        parent::__construct($properties);

        $this->id = $accountId;
    }

    public function getDateAdd(): ?int
    {
        return $this->dateAdd;
    }

    public function getDateEdit(): ?int
    {
        return $this->dateEdit;
    }

    public function getUserEditId(): ?int
    {
        return $this->userEditId;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function setPass(string $pass)
    {
        $this->pass = $pass;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserGroupId(): ?int
    {
        return $this->userGroupId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $data = get_object_vars($this);

        unset($data['accountPass'], $data['accountIV']);

        return Json::safeJson($data);
    }

    public function getCountView(): ?int
    {
        return $this->countView;
    }

    public function getCountDecrypt(): ?int
    {
        return $this->countDecrypt;
    }

    public function getIsPrivate(): ?int
    {
        return $this->isPrivate;
    }

    public function getPassDate(): ?int
    {
        return $this->passDate;
    }

    public function getPassDateChange(): ?int
    {
        return $this->passDateChange;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getIsPrivateGroup(): ?int
    {
        return $this->isPrivateGroup;
    }
}