<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\User;

/**
 * Class UserPassResponse
 *
 * @package SP\DataModel\Dto
 */
final class UserPassResponse
{
    private int $status;
    private ?string $cryptMasterPass = null;
    private ?string $cryptSecuredKey = null;
    private ?string $clearMasterPass;

    /**
     * UserPassResponse constructor.
     */
    public function __construct(int $status, ?string $clearUserMPass = null)
    {
        $this->status = $status;
        $this->clearMasterPass = $clearUserMPass;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getCryptMasterPass(): ?string
    {
        return $this->cryptMasterPass;
    }

    public function setCryptMasterPass(string $cryptMasterPass): void
    {
        $this->cryptMasterPass = $cryptMasterPass;
    }

    public function getCryptSecuredKey(): ?string
    {
        return $this->cryptSecuredKey;
    }

    public function setCryptSecuredKey(string $cryptSecuredKey): void
    {
        $this->cryptSecuredKey = $cryptSecuredKey;
    }

    public function getClearMasterPass(): ?string
    {
        return $this->clearMasterPass;
    }
}