<?php
/*
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

use SP\Domain\User\Services\UserMasterPassStatus;

/**
 * Class UserMasterPassDto
 */
final readonly class UserMasterPassDto
{
    /**
     * UserPassResponse constructor.
     */
    public function __construct(
        private UserMasterPassStatus $userMasterPassStatus,
        private ?string              $clearMasterPass = null,
        private ?string              $cryptMasterPass = null,
        private ?string              $cryptSecuredKey = null
    ) {
    }

    public function getUserMasterPassStatus(): UserMasterPassStatus
    {
        return $this->userMasterPassStatus;
    }

    public function getCryptMasterPass(): ?string
    {
        return $this->cryptMasterPass;
    }

    public function getCryptSecuredKey(): ?string
    {
        return $this->cryptSecuredKey;
    }

    public function getClearMasterPass(): ?string
    {
        return $this->clearMasterPass;
    }
}
