<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\User\Models;

use SP\Domain\Common\Attributes\Hydratable;
use SP\Domain\Common\Models\ItemWithIdAndNameModel;
use SP\Domain\Common\Models\Model;
use SP\Domain\Common\Models\SerializedModel;

/**
 * Class User
 */
#[Hydratable('preferences', [UserPreferences::class])]
class User extends Model implements ItemWithIdAndNameModel
{
    use SerializedModel;

    public const TABLE = 'User';

    protected ?int    $id              = null;
    protected ?string $pass            = null;
    protected ?string $hashSalt        = null;
    protected ?string $mPass           = null;
    protected ?string $mKey            = null;
    protected ?int    $lastUpdateMPass = null;
    protected ?string $login           = null;
    protected ?string $ssoLogin        = null;
    protected ?string $name            = null;
    protected ?string $email           = null;
    protected ?string $notes           = null;
    protected ?int    $userGroupId     = null;
    protected ?int    $userProfileId   = null;
    protected ?bool   $isAdminApp      = null;
    protected ?bool   $isAdminAcc      = null;
    protected ?bool   $isDisabled      = null;
    protected ?bool   $isChangePass    = null;
    protected ?bool   $isChangedPass   = null;
    protected ?bool   $isLdap          = null;
    protected ?int    $loginCount      = null;
    protected ?string $lastLogin       = null;
    protected ?string $lastUpdate      = null;
    protected ?bool   $isMigrate       = null;
    protected ?string $preferences     = null;

    public function getLoginCount(): ?int
    {
        return $this->loginCount;
    }

    public function getLastLogin(): ?string
    {
        return $this->lastLogin;
    }

    public function getLastUpdate(): ?string
    {
        return $this->lastUpdate;
    }

    public function isMigrate(): ?bool
    {
        return $this->isMigrate;
    }

    public function getPreferences(): ?string
    {
        return $this->preferences;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getUserGroupId(): ?int
    {
        return $this->userGroupId;
    }

    public function getUserProfileId(): ?int
    {
        return $this->userProfileId;
    }

    public function isAdminApp(): ?bool
    {
        return $this->isAdminApp;
    }

    public function isAdminAcc(): ?bool
    {
        return $this->isAdminAcc;
    }

    public function isDisabled(): ?bool
    {
        return $this->isDisabled;
    }

    public function isChangePass(): ?bool
    {
        return $this->isChangePass;
    }

    public function isLdap(): ?bool
    {
        return $this->isLdap;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isChangedPass(): ?bool
    {
        return $this->isChangedPass;
    }

    public function getSsoLogin(): ?string
    {
        return $this->ssoLogin;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function getHashSalt(): ?string
    {
        return $this->hashSalt;
    }

    public function getMPass(): ?string
    {
        return $this->mPass;
    }

    public function getMKey(): ?string
    {
        return $this->mKey;
    }

    public function getLastUpdateMPass(): ?int
    {
        return $this->lastUpdateMPass;
    }
}
