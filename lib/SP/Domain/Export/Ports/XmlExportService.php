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

namespace SP\Domain\Export\Ports;

use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\File\Ports\DirectoryHandlerService;
use SP\Infrastructure\File\FileException;

/**
 * Interface XmlExportServiceInterface
 */
interface XmlExportService
{
    /**
     * Export the accounts and related objects into a XML file
     *
     * @param DirectoryHandlerService $exportPath The path where to store the exported file
     * @param string|null $password The password to encrypt the exported data
     *
     * @return string The exported file
     * @throws ServiceException
     * @throws FileException
     */
    public function export(DirectoryHandlerService $exportPath, ?string $password = null): string;

    /**
     * @return string The path to the archive file
     * @throws CheckException
     * @throws FileException
     */
    public function createArchiveFor(string $file): string;
}
