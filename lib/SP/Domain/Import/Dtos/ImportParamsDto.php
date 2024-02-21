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

namespace SP\Domain\Import\Dtos;

use SP\Domain\Import\Ports\ImportParams;

/**
 * Class ImportParamsDto
 */
class ImportParamsDto implements ImportParams
{
    /**
     * @param int $defaultUser The default user to use as the owner of the imported items
     * @param int $defaultGroup The default group to use as the owner of the imported items
     * @param string|null $password The password used to encrypt the imported file
     * @param string|null $masterPassword The master password used for the items encrypted
     */
    public function __construct(
        private readonly int     $defaultUser,
        private readonly int     $defaultGroup,
        private readonly ?string $password = null,
        private readonly ?string $masterPassword = null
    ) {
    }


    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getDefaultGroup(): int
    {
        return $this->defaultGroup;
    }

    public function getMasterPassword(): ?string
    {
        return $this->masterPassword;
    }

    public function getDefaultUser(): int
    {
        return $this->defaultUser;
    }
}
