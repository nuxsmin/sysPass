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

namespace SP\Domain\User\Dtos;

use SP\Domain\Common\Dtos\Dto;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Models\User;
use SP\Domain\User\Models\UserPreferences;

/**
 * Class UserDataDto
 */
final class UserDataDto extends Dto
{
    protected ?UserPreferences $preferences;

    /**
     * @throws SPException
     */
    public function __construct(private readonly ?User $user = null)
    {
        $this->preferences = $this->user?->hydrate(UserPreferences::class);
    }

    public function getLogin(): ?string
    {
        return $this->user?->getLogin();
    }

    public function getSsoLogin(): ?string
    {
        return $this->user?->getSsoLogin();
    }

    public function getName(): ?string
    {
        return $this->user?->getName();
    }

    public function getEmail(): ?string
    {
        return $this->user?->getEmail();
    }

    public function getUserGroupId(): int
    {
        return (int)$this->user?->getUserGroupId();
    }

    public function getUserProfileId(): int
    {
        return (int)$this->user?->getUserProfileId();
    }

    public function getIsAdminApp(): bool
    {
        return (bool)$this->user?->isAdminApp();
    }

    public function getIsAdminAcc(): bool
    {
        return (bool)$this->user?->isAdminAcc();
    }

    public function getIsDisabled(): bool
    {
        return (bool)$this->user?->isDisabled();
    }

    public function getIsChangePass(): bool
    {
        return (bool)$this->user?->isChangePass();
    }

    public function getIsChangedPass(): bool
    {
        return (bool)$this->user?->isChangedPass();
    }

    public function getIsLdap(): bool
    {
        return (bool)$this->user?->isLdap();
    }

    public function getIsMigrate(): bool
    {
        return (bool)$this->user?->isMigrate();
    }

    public function getPreferences(): ?UserPreferences
    {
        return $this->preferences;
    }

    public function getPass(): ?string
    {
        return $this->user?->getPass();
    }

    public function getMPass(): ?string
    {
        return $this->user?->getMPass();
    }

    public function getMKey(): ?string
    {
        return $this->user?->getMKey();
    }

    public function getLastUpdateMPass(): int
    {
        return $this->user?->getLastUpdateMPass();
    }

    public function getHashSalt(): ?string
    {
        return $this->user?->getHashSalt();
    }

    public function getId(): ?int
    {
        return $this->user?->getId();
    }

    public function getUserGroupName(): ?string
    {
        return $this->user?->offsetGet('userGroup.name');
    }

    public function getLastUpdate(): int
    {
        return (int)strtotime($this->user?->getLastUpdate());
    }
}
