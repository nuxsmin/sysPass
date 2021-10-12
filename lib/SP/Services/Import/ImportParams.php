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

namespace SP\Services\Import;


/**
 * Class ImportParams
 *
 * @package SP\Services\Import
 */
final class ImportParams
{
    protected ?string $importPwd = null;
    protected ?string $importMasterPwd = null;
    protected int $defaultUser = 0;
    protected int $defaultGroup = 0;
    protected string $csvDelimiter = ';';

    public function getImportPwd(): ?string
    {
        return $this->importPwd;
    }

    public function setImportPwd(string $importPwd): void
    {
        $this->importPwd = $importPwd;
    }

    public function getDefaultGroup(): int
    {
        return $this->defaultGroup;
    }

    public function setDefaultGroup(int $defaultGroup): void
    {
        $this->defaultGroup = $defaultGroup;
    }

    public function getCsvDelimiter(): string
    {
        return $this->csvDelimiter;
    }

    public function setCsvDelimiter(string $csvDelimiter): void
    {
        $this->csvDelimiter = $csvDelimiter;
    }

    public function getImportMasterPwd(): ?string
    {
        return $this->importMasterPwd;
    }

    public function setImportMasterPwd(string $importMasterPwd): void
    {
        $this->importMasterPwd = $importMasterPwd;
    }

    public function getDefaultUser(): int
    {
        return $this->defaultUser;
    }

    public function setDefaultUser(int $defaultUser): void
    {
        $this->defaultUser = $defaultUser;
    }
}