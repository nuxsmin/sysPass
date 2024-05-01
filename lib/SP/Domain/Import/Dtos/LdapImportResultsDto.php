<?php
declare(strict_types=1);
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

/**
 * Class LdapImportResultsDto
 */
final class LdapImportResultsDto
{
    private int $syncedObjects = 0;
    private int $errorObjects  = 0;

    /**
     * @param int $totalObjects
     */
    public function __construct(private readonly int $totalObjects)
    {
    }


    public function addSyncedObject(): void
    {
        $this->syncedObjects++;
    }

    public function addErrorObject(): void
    {
        $this->errorObjects++;
    }

    public function getTotalObjects(): int
    {
        return $this->totalObjects;
    }

    public function getSyncedObjects(): int
    {
        return $this->syncedObjects;
    }

    public function getErrorObjects(): int
    {
        return $this->errorObjects;
    }
}
